<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(["ok" => false, "error" => "Unauthorized"]);
  exit;
}

require_once "db/cavitemed_db.php";

$doctor_id = (int)$_SESSION['user_id'];
$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;

/*
  MODE:
  - if patient_id > 0 => history for that patient
  - else => recent certificates created by this doctor
*/

// ✅ use certificate_id DESC (safe even if created_at doesn't exist)
if ($patient_id > 0) {
  $sql = "
    SELECT
      mc.certificate_id,
      mc.certificate_number,
      mc.template_type,
      mc.diagnosis,
      mc.restriction_level,
      mc.leave_from,
      mc.leave_to,
      mc.follow_up_date,
      mc.include_digital_stamp,
      mc.include_qr_code,

      p.first_name,
      p.last_name,
      p.mrn,

      pd.file_path
    FROM medical_certificates mc
    LEFT JOIN patients p
      ON p.patient_id = mc.patient_id
    LEFT JOIN patient_documents pd
      ON pd.certificate_id = mc.certificate_id
    WHERE mc.patient_id = ?
    ORDER BY mc.certificate_id DESC
    LIMIT 50
  ";

  $stmt = $conn->prepare($sql);
  if (!$stmt) {
    http_response_code(500);
    echo json_encode(["ok" => false, "error" => "Prepare failed: " . $conn->error]);
    exit;
  }
  $stmt->bind_param("i", $patient_id);

} else {
  $sql = "
    SELECT
      mc.certificate_id,
      mc.certificate_number,
      mc.template_type,
      mc.diagnosis,
      mc.restriction_level,
      mc.leave_from,
      mc.leave_to,
      mc.follow_up_date,
      mc.include_digital_stamp,
      mc.include_qr_code,

      p.first_name,
      p.last_name,
      p.mrn,

      pd.file_path
    FROM medical_certificates mc
    LEFT JOIN patients p
      ON p.patient_id = mc.patient_id
    LEFT JOIN patient_documents pd
      ON pd.certificate_id = mc.certificate_id
    WHERE mc.created_by = ?
    ORDER BY mc.certificate_id DESC
    LIMIT 10
  ";

  $stmt = $conn->prepare($sql);
  if (!$stmt) {
    http_response_code(500);
    echo json_encode(["ok" => false, "error" => "Prepare failed: " . $conn->error]);
    exit;
  }
  $stmt->bind_param("i", $doctor_id);
}

if (!$stmt->execute()) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "Execute failed: " . $stmt->error]);
  exit;
}

$res = $stmt->get_result();
$items = [];

while ($row = $res->fetch_assoc()) {
  $row['download_url'] = null;
  if (!empty($row['file_path'])) {
    $row['download_url'] = "/CAVITE-MED/" . ltrim($row['file_path'], "/");
  }

  // nice display name for UI
  $row['patient_name'] = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));

  $items[] = $row;
}

echo json_encode(["ok" => true, "items" => $items]);
