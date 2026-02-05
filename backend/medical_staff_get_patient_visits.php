<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(["ok" => false, "error" => "Unauthorized"]);
  exit;
}

require_once __DIR__ . "/db/cavitemed_db.php"; // $conn (mysqli)

if (!isset($conn) || !($conn instanceof mysqli)) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "Database connection not found"]);
  exit;
}

$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
if ($patient_id <= 0) {
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => "Missing patient_id"]);
  exit;
}

try {
  $visits = [];

  $sql = "
    SELECT
      v.visit_id,
      v.visit_type,
      v.visit_datetime,
      v.status,
      v.notes,
      u.full_name AS doctor_name,
      hc.center_name AS location_name
    FROM patient_visits v
    LEFT JOIN users u ON u.user_id = v.doctor_id
    LEFT JOIN health_centers hc ON hc.id = v.health_center_id
    WHERE v.patient_id = ?
    ORDER BY v.visit_datetime DESC
    LIMIT 50
  ";

  $stmt = $conn->prepare($sql);
  if (!$stmt) throw new Exception("Prepare visits failed: " . $conn->error);

  $stmt->bind_param("i", $patient_id);
  $stmt->execute();
  $res = $stmt->get_result();
  $visits = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
  $stmt->close();

  echo json_encode(["ok" => true, "visits" => $visits]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "Server error: " . $e->getMessage()]);
}
