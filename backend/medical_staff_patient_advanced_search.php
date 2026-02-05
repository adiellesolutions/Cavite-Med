<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  echo json_encode(["ok" => false, "error" => "Unauthorized"]);
  exit;
}

require_once __DIR__ . "/db/cavitemed_db.php"; // provides $conn (mysqli)

function likeParam($v) {
  $v = trim((string)$v);
  if ($v === "") return null;
  return "%" . $v . "%";
}

try {
  // Get filters
  $name         = isset($_POST['name']) ? trim($_POST['name']) : "";
  $mrn          = isset($_POST['mrn']) ? trim($_POST['mrn']) : "";
  $dob          = isset($_POST['dob']) ? trim($_POST['dob']) : "";
  $phone        = isset($_POST['phone']) ? trim($_POST['phone']) : "";
  $doctor_id    = isset($_POST['doctor_id']) ? trim($_POST['doctor_id']) : "";
  $health_center_id = isset($_POST['health_center_id']) ? trim($_POST['health_center_id']) : "";
  $visit_from   = isset($_POST['visit_from']) ? trim($_POST['visit_from']) : "";
  $visit_to     = isset($_POST['visit_to']) ? trim($_POST['visit_to']) : "";

  // Base query: patients
  $sql = "
    SELECT
      p.patient_id,
      CONCAT(p.first_name, ' ', p.last_name) AS full_name,
      p.mrn,
      p.phone,
      p.date_of_birth AS dob
    FROM patients p
    WHERE 1=1
  ";

  $types = "";
  $params = [];

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

  // Visit-based filters (doctor/location/date range) require joining patient_visits
  $needVisitsJoin = ($doctor_id !== "" || $health_center_id !== "" || $visit_from !== "" || $visit_to !== "");

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
    ";

    // Re-apply patient filters again when we rebuilt SQL
    $types = "";
    $params = [];

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

    // Location filter
    if ($health_center_id !== "") {
      $sql .= " AND v.health_center_id = ? ";
      $types .= "i";
      $params[] = (int)$health_center_id;
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

  if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
  }

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
