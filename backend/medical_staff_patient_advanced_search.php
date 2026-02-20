<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  echo json_encode(["ok" => false, "error" => "Unauthorized"]);
  exit;
}

require_once __DIR__ . "/db/cavitemed_db.php"; // provides $conn (mysqli)

if (!isset($conn) || !($conn instanceof mysqli)) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "Database connection not found"]);
  exit;
}

function likeParam($v) {
  $v = trim((string)$v);
  if ($v === "") return null;
  return "%" . $v . "%";
}

$user_id = (int)$_SESSION['user_id'];

try {
  // -------------------------
  // Get user's health_center_id (scope)
  // -------------------------
  $st = $conn->prepare("SELECT health_center_id FROM users WHERE user_id=? LIMIT 1");
  if (!$st) {
    echo json_encode(["ok"=>false, "error"=>"Prepare user center failed: ".$conn->error]);
    exit;
  }
  $st->bind_param("i", $user_id);
  $st->execute();
  $st->bind_result($user_health_center_id);
  $st->fetch();
  $st->close();

  $user_health_center_id = $user_health_center_id ? (int)$user_health_center_id : null;

  if (!$user_health_center_id) {
    http_response_code(403);
    echo json_encode(["ok" => false, "error" => "User is not assigned to a health center."]);
    exit;
  }

  // Get filters
  $name         = isset($_POST['name']) ? trim($_POST['name']) : "";
  $mrn          = isset($_POST['mrn']) ? trim($_POST['mrn']) : "";
  $dob          = isset($_POST['dob']) ? trim($_POST['dob']) : "";
  $phone        = isset($_POST['phone']) ? trim($_POST['phone']) : "";
  $doctor_id    = isset($_POST['doctor_id']) ? trim($_POST['doctor_id']) : "";
  $health_center_id = isset($_POST['health_center_id']) ? trim($_POST['health_center_id']) : ""; // will be ignored/validated
  $visit_from   = isset($_POST['visit_from']) ? trim($_POST['visit_from']) : "";
  $visit_to     = isset($_POST['visit_to']) ? trim($_POST['visit_to']) : "";

  // Optional: if frontend sends health_center_id, enforce it must match user's
  if ($health_center_id !== "" && (int)$health_center_id !== $user_health_center_id) {
    echo json_encode(["ok" => true, "results" => []]);
    exit;
  }

  // Base query: patients (ALWAYS scoped)
  $sql = "
    SELECT
      p.patient_id,
      CONCAT(p.first_name, ' ', p.last_name) AS full_name,
      p.mrn,
      p.phone,
      p.date_of_birth AS dob
    FROM patients p
    WHERE 1=1
      AND p.health_center_id = ?
  ";

  $types = "i";
  $params = [$user_health_center_id];

  // Name filter (first or last)
  if ($name !== "") {
    $sql .= " AND (p.first_name LIKE ? OR p.last_name LIKE ? OR CONCAT(p.first_name,' ',p.last_name) LIKE ?) ";
    $types .= "sss";
    $lp = likeParam($name);
    $params[] = $lp;
    $params[] = $lp;
    $params[] = $lp;
  }

  // MRN
  if ($mrn !== "") {
    $sql .= " AND p.mrn LIKE ? ";
    $types .= "s";
    $params[] = likeParam($mrn);
  }

  // DOB exact
  if ($dob !== "") {
    $sql .= " AND p.date_of_birth = ? ";
    $types .= "s";
    $params[] = $dob;
  }

  // Phone
  if ($phone !== "") {
    $sql .= " AND p.phone LIKE ? ";
    $types .= "s";
    $params[] = likeParam($phone);
  }

  // Visit-based filters (doctor/date range) require joining patient_visits
  $needVisitsJoin = ($doctor_id !== "" || $visit_from !== "" || $visit_to !== "");

  if ($needVisitsJoin) {
    $sql = "
      SELECT DISTINCT
        p.patient_id,
        CONCAT(p.first_name, ' ', p.last_name) AS full_name,
        p.mrn,
        p.phone,
        p.date_of_birth AS dob
      FROM patients p
      INNER JOIN patient_visits v ON v.patient_id = p.patient_id
      WHERE 1=1
        AND p.health_center_id = ?
    ";

    // Re-apply patient filters again when we rebuilt SQL
    $types = "i";
    $params = [$user_health_center_id];

    if ($name !== "") {
      $sql .= " AND (p.first_name LIKE ? OR p.last_name LIKE ? OR CONCAT(p.first_name,' ',p.last_name) LIKE ?) ";
      $types .= "sss";
      $lp = likeParam($name);
      $params[] = $lp;
      $params[] = $lp;
      $params[] = $lp;
    }
    if ($mrn !== "") {
      $sql .= " AND p.mrn LIKE ? ";
      $types .= "s";
      $params[] = likeParam($mrn);
    }
    if ($dob !== "") {
      $sql .= " AND p.date_of_birth = ? ";
      $types .= "s";
      $params[] = $dob;
    }
    if ($phone !== "") {
      $sql .= " AND p.phone LIKE ? ";
      $types .= "s";
      $params[] = likeParam($phone);
    }

    // Doctor filter
    if ($doctor_id !== "") {
      $sql .= " AND v.doctor_id = ? ";
      $types .= "i";
      $params[] = (int)$doctor_id;
    }

    // Visit date range
    if ($visit_from !== "") {
      $sql .= " AND DATE(v.visit_datetime) >= ? ";
      $types .= "s";
      $params[] = $visit_from;
    }
    if ($visit_to !== "") {
      $sql .= " AND DATE(v.visit_datetime) <= ? ";
      $types .= "s";
      $params[] = $visit_to;
    }
  }

  $sql .= " ORDER BY full_name LIMIT 50 ";

  $stmt = $conn->prepare($sql);
  if (!$stmt) {
    echo json_encode(["ok"=>false, "error"=>"Prepare failed: ".$conn->error]);
    exit;
  }

  $stmt->bind_param($types, ...$params);

  $stmt->execute();
  $res = $stmt->get_result();

  $results = [];
  while ($row = $res->fetch_assoc()) {
    $results[] = $row;
  }

  echo json_encode(["ok" => true, "results" => $results]);
} catch (Throwable $e) {
  echo json_encode(["ok" => false, "error" => "Server error: " . $e->getMessage()]);
}