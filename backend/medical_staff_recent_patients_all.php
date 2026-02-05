<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medical_staff') {
  http_response_code(401);
  echo json_encode(["ok"=>false, "error"=>"Unauthorized"]);
  exit;
}

require_once __DIR__ . "/db/cavitemed_db.php"; // keeps your existing file

$user_id = (int)$_SESSION['user_id'];

try {
  // Recent patients for this user + last visit info
  $stmt = $pdo->prepare("
    SELECT
      r.patient_id,
      r.last_viewed_at,
      p.mrn,
      CONCAT(p.first_name, ' ', p.last_name) AS full_name,

      v.visit_datetime AS last_visit_date,
      v.visit_type AS last_visit_type,
      d.full_name AS last_doctor_name,
      hc.center_name AS last_location_name

    FROM user_patient_recent r
    JOIN patients p ON p.patient_id = r.patient_id

    LEFT JOIN patient_visits v
      ON v.patient_id = p.patient_id
     AND v.visit_datetime = (
       SELECT MAX(v2.visit_datetime)
       FROM patient_visits v2
       WHERE v2.patient_id = p.patient_id
     )

    LEFT JOIN users d ON d.user_id = v.doctor_id
    LEFT JOIN health_centers hc ON hc.id = v.health_center_id

    WHERE r.user_id = :uid
    ORDER BY r.last_viewed_at DESC
    LIMIT 200
  ");

  $stmt->execute([":uid" => $user_id]);
  $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode(["ok"=>true, "recent"=>$recent]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(["ok"=>false, "error"=>"Server error"]);
}
