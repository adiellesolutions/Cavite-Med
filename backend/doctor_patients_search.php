<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(["ok" => false, "error" => "Unauthorized"]);
  exit;
}

/**
 * ✅ FIX PATH (choose correct one)
 * If your db file is: backend/db/cavitemed_db.php
 */
require_once __DIR__ . "/db/cavitemed_db.php";

if (!isset($conn) || !($conn instanceof mysqli)) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "Database connection not found"]);
  exit;
}

$user_id = (int)$_SESSION['user_id'];

// search query
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$like = '%' . $q . '%';

try {
  // -------------------------
  // Get user's health_center_id
  // -------------------------
  $st = $conn->prepare("SELECT health_center_id FROM users WHERE user_id=? LIMIT 1");
  if (!$st) throw new Exception("Prepare user center failed: " . $conn->error);

  $st->bind_param("i", $user_id);
  $st->execute();
  $st->bind_result($health_center_id);
  $st->fetch();
  $st->close();

  $health_center_id = $health_center_id ? (int)$health_center_id : null;

  if (!$health_center_id) {
    http_response_code(403);
    echo json_encode(["ok" => false, "error" => "User is not assigned to a health center."]);
    exit;
  }

  /**
   * ✅ Patient list based on COMPLETED VISITS
   * Returns patients that have at least 1 completed/for_dispense visit,
   * and gets their latest visit (MAX visit_id).
   */
  $sql = "
    SELECT
      p.patient_id,
      p.mrn,
      p.first_name,
      p.last_name,
      p.date_of_birth,
      p.gender,
      p.blood_type,
      v.visit_id,
      v.visit_datetime,
      v.status
    FROM patients p
    INNER JOIN (
      SELECT patient_id, MAX(visit_id) AS last_visit_id
      FROM patient_visits
      GROUP BY patient_id
    ) lv ON lv.patient_id = p.patient_id
    INNER JOIN patient_visits v ON v.visit_id = lv.last_visit_id
    WHERE v.status IN ('for_dispense', 'completed')
      AND p.health_center_id = ?   -- ✅ scope by center
      AND (p.first_name LIKE ? OR p.last_name LIKE ? OR p.mrn LIKE ?)
    ORDER BY v.visit_datetime DESC, p.last_name, p.first_name
    LIMIT 50
  ";

  $stmt = $conn->prepare($sql);
  if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);

  // ✅ added health_center_id first
  $stmt->bind_param("isss", $health_center_id, $like, $like, $like);
  $stmt->execute();

  $res = $stmt->get_result();
  $patients = [];

  while ($row = $res->fetch_assoc()) {
    $patients[] = $row;
  }

  $stmt->close();

  echo json_encode(["ok" => true, "patients" => $patients]);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => $e->getMessage()]);
}
exit;