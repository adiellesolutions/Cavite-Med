<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(["ok" => false, "error" => "Unauthorized"]);
  exit;
}

require_once __DIR__ . "/db/cavitemed_db.php"; // provides $conn (mysqli)

$patient_id = isset($_GET['patient_id']) ? trim($_GET['patient_id']) : '';
if ($patient_id === '' || !ctype_digit($patient_id)) {
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => "Invalid patient_id"]);
  exit;
}

try {
  // NOTE: your table columns are address_line (not address)
  $sql = "
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
      address_line,
      city,
      state,
      zip_code,
      created_at
    FROM patients
    WHERE patient_id = ?
    LIMIT 1
  ";

  $stmt = $conn->prepare($sql);
  if (!$stmt) {
    throw new Exception("Prepare failed: " . $conn->error);
  }

  $pid = (int)$patient_id;
  $stmt->bind_param("i", $pid);

  if (!$stmt->execute()) {
    throw new Exception("Execute failed: " . $stmt->error);
  }

  $result = $stmt->get_result();
  $patient = $result->fetch_assoc();

  if (!$patient) {
    http_response_code(404);
    echo json_encode(["ok" => false, "error" => "Patient not found"]);
    exit;
  }

  echo json_encode(["ok" => true, "patient" => $patient]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "Server error"]);
}
