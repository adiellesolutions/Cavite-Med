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

  $pid = (int)$patient_id;

  /* ===================================================== */
  /* 1️⃣ PATIENT (MAIN TABLE) */
  /* ===================================================== */

  $stmt = $conn->prepare("
    SELECT
      patient_id,
      mrn,
      first_name,
      last_name,
      middle_name,
      preferred_name,
      marital_status,
      occupation,
      preferred_language,
      date_of_birth,
      gender,
      blood_type,
      phone,
      email,
      address_line,
      city,
      state,
      zip_code,
      status,
      created_at
    FROM patients
    WHERE patient_id = ?
    LIMIT 1
  ");
  if (!$stmt) throw new Exception($conn->error);

  $stmt->bind_param("i", $pid);
  $stmt->execute();
  $patient = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$patient) {
    http_response_code(404);
    echo json_encode(["ok" => false, "error" => "Patient not found"]);
    exit;
  }

  /* ===================================================== */
  /* 2️⃣ MEDICAL PROFILE */
  /* ===================================================== */

  $stmt = $conn->prepare("
    SELECT
      allergies,
      chronic_conditions,
      current_medications,
      immunization_status
    FROM patient_medical_profile
    WHERE patient_id = ?
    LIMIT 1
  ");
  if (!$stmt) throw new Exception($conn->error);

  $stmt->bind_param("i", $pid);
  $stmt->execute();
  $medical = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$medical) {
    $medical = [
      "allergies" => null,
      "chronic_conditions" => null,
      "current_medications" => null,
      "immunization_status" => "unknown"
    ];
  }

  /* ===================================================== */
  /* 3️⃣ INSURANCE (PRIMARY FIRST) */
  /* ===================================================== */

  $stmt = $conn->prepare("
    SELECT
      coverage_type,
      provider_name,
      policy_number,
      group_number,
      effective_date,
      subscriber_name,
      relationship,
      verified_status
    FROM patient_insurance
    WHERE patient_id = ?
    ORDER BY coverage_type = 'primary' DESC
    LIMIT 1
  ");
  if (!$stmt) throw new Exception($conn->error);

  $stmt->bind_param("i", $pid);
  $stmt->execute();
  $insurance = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$insurance) {
    $insurance = [
      "coverage_type" => "primary",
      "provider_name" => null,
      "policy_number" => null,
      "group_number" => null,
      "effective_date" => null,
      "subscriber_name" => null,
      "relationship" => "self",
      "verified_status" => "unverified"
    ];
  }

  /* ===================================================== */
  /* 4️⃣ EMERGENCY CONTACT (PRIMARY FIRST) */
  /* ===================================================== */

  $stmt = $conn->prepare("
    SELECT
      full_name,
      relationship,
      phone,
      email,
      address,
      is_primary
    FROM patient_emergency_contacts
    WHERE patient_id = ?
    ORDER BY is_primary DESC
    LIMIT 1
  ");
  if (!$stmt) throw new Exception($conn->error);

  $stmt->bind_param("i", $pid);
  $stmt->execute();
  $emergency = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$emergency) {
    $emergency = [
      "full_name" => null,
      "relationship" => null,
      "phone" => null,
      "email" => null,
      "address" => null,
      "is_primary" => 0
    ];
  }

  /* ===================================================== */
  /* FINAL JSON RESPONSE */
  /* ===================================================== */

  echo json_encode([
    "ok" => true,
    "patient" => $patient,
    "medical" => $medical,
    "insurance" => $insurance,
    "emergency" => $emergency
  ]);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    "ok" => false,
    "error" => "Server error"
  ]);
}
