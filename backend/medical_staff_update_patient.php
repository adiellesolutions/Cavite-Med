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

function post($k) {
  return isset($_POST[$k]) ? trim((string)$_POST[$k]) : null;
}

$patient_id = (int)($_POST['patient_id'] ?? 0);
if ($patient_id <= 0) {
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => "Invalid patient_id"]);
  exit;
}

$first_name = post("first_name");
$middle_name = post("middle_name");
$last_name = post("last_name");
$preferred_name = post("preferred_name");

$marital_status = post("marital_status");
$occupation = post("occupation");
$preferred_language = post("preferred_language");

$date_of_birth = post("date_of_birth");
$gender = post("gender");
$blood_type = post("blood_type");
$status = post("status");

$phone = post("phone");
$email = post("email");

$address_line = post("address_line");
$city = post("city");
$state = post("state");
$zip_code = post("zip_code");

// required checks
if (!$first_name || !$last_name || !$date_of_birth || !$gender || !$phone || !$address_line || !$city || !$state || !$zip_code) {
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => "Missing required fields"]);
  exit;
}

// normalize empty strings to NULL for nullable fields
$toNull = function($v) {
  $v = trim((string)($v ?? ""));
  return $v === "" ? null : $v;
};

$middle_name = $toNull($middle_name);
$preferred_name = $toNull($preferred_name);
$marital_status = $toNull($marital_status);
$occupation = $toNull($occupation);
$preferred_language = $toNull($preferred_language);
$email = $toNull($email);
$blood_type = $toNull($blood_type);

try {
  $sql = "
    UPDATE patients
    SET
      first_name = ?,
      middle_name = ?,
      last_name = ?,
      preferred_name = ?,
      marital_status = ?,
      occupation = ?,
      preferred_language = ?,
      date_of_birth = ?,
      gender = ?,
      blood_type = ?,
      phone = ?,
      email = ?,
      address_line = ?,
      city = ?,
      state = ?,
      zip_code = ?,
      status = ?,
      updated_at = CURRENT_TIMESTAMP
    WHERE patient_id = ?
    LIMIT 1
  ";

  $stmt = $conn->prepare($sql);
  if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);

  $stmt->bind_param(
    "sssssssssssssssssi",
    $first_name,
    $middle_name,
    $last_name,
    $preferred_name,
    $marital_status,
    $occupation,
    $preferred_language,
    $date_of_birth,
    $gender,
    $blood_type,
    $phone,
    $email,
    $address_line,
    $city,
    $state,
    $zip_code,
    $status,
    $patient_id
  );

  $stmt->execute();

  if ($stmt->affected_rows < 0) {
    throw new Exception("Update failed");
  }

  $stmt->close();

  echo json_encode([
    "ok" => true,
    "patient_id" => $patient_id
  ]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "Server error: " . $e->getMessage()]);
}
