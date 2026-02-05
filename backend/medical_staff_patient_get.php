<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(["ok" => false, "error" => "Unauthorized"]);
  exit;
}

require_once __DIR__ . "/cavitemed_db.php";

$patient_id = isset($_GET['patient_id']) ? trim($_GET['patient_id']) : '';
if ($patient_id === '' || !ctype_digit($patient_id)) {
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => "Invalid patient_id"]);
  exit;
}

try {
  $stmt = $pdo->prepare("
    SELECT
      patient_id,
      mrn,
      first_name,
      last_name,
      CONCAT(first_name, ' ', last_name) AS full_name,
      date_of_birth,
      gender,
      blood_type,
      phone,
      email,
      address,
      city,
      state,
      zip_code,
      created_at
    FROM patients
    WHERE patient_id = :pid
    LIMIT 1
  ");
  $stmt->execute([":pid" => $patient_id]);
  $patient = $stmt->fetch();

  if (!$patient) {
    http_response_code(404);
    echo json_encode(["ok" => false, "error" => "Patient not found"]);
    exit;
  }

  echo json_encode(["ok" => true, "patient" => $patient]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "Server error"]);
}
