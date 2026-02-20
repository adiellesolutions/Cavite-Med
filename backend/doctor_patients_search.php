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

// search query
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$like = '%' . $q . '%';

try {

  /**
   * ✅ Patient list based on COMPLETED VISITS
   * - patients.status is active/inactive only
   * - visits.status is waiting/in_progress/completed/etc
   *
   * This returns patients that have at least 1 completed visit,
   * and gets their latest completed visit (MAX visit_id).
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
  AND (p.first_name LIKE ? OR p.last_name LIKE ? OR p.mrn LIKE ?)
  ORDER BY v.visit_datetime DESC, p.last_name, p.first_name
  LIMIT 50
";

  $stmt = $conn->prepare($sql);
  if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);

  $stmt->bind_param("sss", $like, $like, $like);
  $stmt->execute();

  $res = $stmt->get_result();
  $patients = [];

  while ($row = $res->fetch_assoc()) {
    $patients[] = $row;
  }

  echo json_encode(["ok" => true, "patients" => $patients]);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => $e->getMessage()]);
}
exit;
