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

function toNull($v) {
  $v = trim((string)($v ?? ""));
  return $v === "" ? null : $v;
}

$patient_id = (int)($_POST['patient_id'] ?? 0);
if ($patient_id <= 0) {
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => "Invalid patient_id"]);
  exit;
}

/* =========================
   PERSONAL / PATIENTS TABLE
   ========================= */
$first_name = post("first_name");
$middle_name = toNull(post("middle_name"));
$last_name = post("last_name");
$preferred_name = toNull(post("preferred_name"));

$marital_status = toNull(post("marital_status"));
$occupation = toNull(post("occupation"));
$preferred_language = toNull(post("preferred_language"));

$date_of_birth = post("date_of_birth");
$gender = post("gender");
$blood_type = toNull(post("blood_type"));
$status = post("status") ?? "active";

$phone = post("phone");
$email = toNull(post("email"));

$address_line = post("address_line");
$city = post("city");
$state = post("state");
$zip_code = post("zip_code");

// required checks (patients)
if (!$first_name || !$last_name || !$date_of_birth || !$gender || !$phone || !$address_line || !$city || !$state || !$zip_code) {
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => "Missing required fields"]);
  exit;
}

/* =========================
   MEDICAL PROFILE TABLE
   (names from your HTML)
   ========================= */
$allergies = toNull(post("allergies"));
$chronic_conditions = toNull(post("chronic_conditions"));
$current_medications = toNull(post("current_medications"));
$immunization_status = post("immunization_status") ?? "unknown";

/* =========================
   INSURANCE TABLE
   ========================= */
$coverage_type = post("coverage_type") ?? "primary";     // primary/secondary
$provider_name = toNull(post("provider_name"));
$policy_number = toNull(post("policy_number"));
$group_number = toNull(post("group_number"));
$effective_date = toNull(post("effective_date"));        // yyyy-mm-dd or null
$subscriber_name = toNull(post("subscriber_name"));
$relationship = post("relationship") ?? "self";           // self/spouse/child...
$verified_status = post("verified_status") ?? "unverified";

/* =========================
   EMERGENCY CONTACT TABLE
   ========================= */
$ec_full_name = toNull(post("ec_full_name"));
$ec_relationship = toNull(post("ec_relationship"));
$ec_phone = toNull(post("ec_phone"));
$ec_email = toNull(post("ec_email"));
$ec_address = toNull(post("ec_address"));
$ec_is_primary = isset($_POST["ec_is_primary"]) ? 1 : 0;  // checkbox

try {
  $conn->begin_transaction();

  /* =========================
     1) UPDATE patients
     ========================= */
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
  if (!$stmt) throw new Exception("Prepare failed (patients): " . $conn->error);

  // 17 strings + 1 int
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
  $stmt->close();

  /* =========================
     2) UPSERT patient_medical_profile
     ========================= */
  $stmt = $conn->prepare("SELECT patient_id FROM patient_medical_profile WHERE patient_id = ? LIMIT 1");
  if (!$stmt) throw new Exception("Prepare failed (medical select): " . $conn->error);

  $stmt->bind_param("i", $patient_id);
  $stmt->execute();
  $hasMedical = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if ($hasMedical) {
    $stmt = $conn->prepare("
      UPDATE patient_medical_profile
      SET allergies = ?, chronic_conditions = ?, current_medications = ?, immunization_status = ?
      WHERE patient_id = ?
      LIMIT 1
    ");
    if (!$stmt) throw new Exception("Prepare failed (medical update): " . $conn->error);

    $stmt->bind_param("ssssi", $allergies, $chronic_conditions, $current_medications, $immunization_status, $patient_id);
    $stmt->execute();
    $stmt->close();
  } else {
    $stmt = $conn->prepare("
      INSERT INTO patient_medical_profile (patient_id, allergies, chronic_conditions, current_medications, immunization_status)
      VALUES (?, ?, ?, ?, ?)
    ");
    if (!$stmt) throw new Exception("Prepare failed (medical insert): " . $conn->error);

    $stmt->bind_param("issss", $patient_id, $allergies, $chronic_conditions, $current_medications, $immunization_status);
    $stmt->execute();
    $stmt->close();
  }

  /* =========================
     3) UPSERT patient_insurance
     - update the best existing row (primary first), else insert
     ========================= */
  $stmt = $conn->prepare("
    SELECT insurance_id
    FROM patient_insurance
    WHERE patient_id = ?
    ORDER BY coverage_type = 'primary' DESC
    LIMIT 1
  ");
  if (!$stmt) throw new Exception("Prepare failed (insurance select): " . $conn->error);

  $stmt->bind_param("i", $patient_id);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if ($row && isset($row['insurance_id'])) {
    $insurance_id = (int)$row['insurance_id'];

    $stmt = $conn->prepare("
      UPDATE patient_insurance
      SET
        coverage_type = ?,
        provider_name = ?,
        policy_number = ?,
        group_number = ?,
        effective_date = ?,
        subscriber_name = ?,
        relationship = ?,
        verified_status = ?
      WHERE insurance_id = ?
      LIMIT 1
    ");
    if (!$stmt) throw new Exception("Prepare failed (insurance update): " . $conn->error);

    $stmt->bind_param(
      "ssssssssi",
      $coverage_type,
      $provider_name,
      $policy_number,
      $group_number,
      $effective_date,
      $subscriber_name,
      $relationship,
      $verified_status,
      $insurance_id
    );
    $stmt->execute();
    $stmt->close();
  } else {
    $stmt = $conn->prepare("
      INSERT INTO patient_insurance
      (patient_id, coverage_type, provider_name, policy_number, group_number, effective_date, subscriber_name, relationship, verified_status)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) throw new Exception("Prepare failed (insurance insert): " . $conn->error);

    $stmt->bind_param(
      "issssssss",
      $patient_id,
      $coverage_type,
      $provider_name,
      $policy_number,
      $group_number,
      $effective_date,
      $subscriber_name,
      $relationship,
      $verified_status
    );
    $stmt->execute();
    $stmt->close();
  }

  /* =========================
     4) UPSERT patient_emergency_contacts
     - update primary contact if exists, else insert
     ========================= */
  $stmt = $conn->prepare("
    SELECT contact_id
    FROM patient_emergency_contacts
    WHERE patient_id = ?
    ORDER BY is_primary DESC
    LIMIT 1
  ");
  if (!$stmt) throw new Exception("Prepare failed (emergency select): " . $conn->error);

  $stmt->bind_param("i", $patient_id);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if ($row && isset($row['contact_id'])) {
    $contact_id = (int)$row['contact_id'];

    $stmt = $conn->prepare("
      UPDATE patient_emergency_contacts
      SET
        full_name = ?,
        relationship = ?,
        phone = ?,
        email = ?,
        address = ?,
        is_primary = ?
      WHERE contact_id = ?
      LIMIT 1
    ");
    if (!$stmt) throw new Exception("Prepare failed (emergency update): " . $conn->error);

    $stmt->bind_param(
      "sssssii",
      $ec_full_name,
      $ec_relationship,
      $ec_phone,
      $ec_email,
      $ec_address,
      $ec_is_primary,
      $contact_id
    );
    $stmt->execute();
    $stmt->close();
  } else {
    $stmt = $conn->prepare("
      INSERT INTO patient_emergency_contacts
      (patient_id, full_name, relationship, phone, email, address, is_primary)
      VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) throw new Exception("Prepare failed (emergency insert): " . $conn->error);

    $stmt->bind_param(
      "isssssi",
      $patient_id,
      $ec_full_name,
      $ec_relationship,
      $ec_phone,
      $ec_email,
      $ec_address,
      $ec_is_primary
    );
    $stmt->execute();
    $stmt->close();
  }

  $conn->commit();

  echo json_encode([
    "ok" => true,
    "patient_id" => $patient_id
  ]);
} catch (Throwable $e) {
  if ($conn && $conn->errno === 0) {
    // ok
  }
  if ($conn) $conn->rollback();

  http_response_code(500);
  echo json_encode([
    "ok" => false,
    "error" => "Server error: " . $e->getMessage()
  ]);
}
