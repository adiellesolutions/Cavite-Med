<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(["ok" => false, "error" => "Unauthorized"]);
  exit;
}

require_once __DIR__ . "/db/cavitemed_db.php";

if (!isset($conn) || !($conn instanceof mysqli)) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "Database connection not found"]);
  exit;
}

/* =========================
   HELPERS
========================= */
function post($k) {
  return isset($_POST[$k]) ? trim((string)$_POST[$k]) : null;
}

function toNull($v) {
  $v = trim((string)($v ?? ""));
  return $v === "" ? null : $v;
}

/* =========================
   VALIDATE PATIENT
========================= */
$patient_id = (int)($_POST['patient_id'] ?? 0);

if ($patient_id <= 0) {
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => "Invalid patient_id"]);
  exit;
}

/* =========================
   PATIENTS TABLE (REQUIRED)
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

if (!$first_name || !$last_name || !$date_of_birth || !$gender || !$phone || !$address_line || !$city || !$state || !$zip_code) {
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => "Missing required fields"]);
  exit;
}

/* =========================
   OPTIONAL SECTIONS
========================= */

/* MEDICAL */
$allergies = toNull(post("allergies"));
$chronic_conditions = toNull(post("chronic_conditions"));
$current_medications = toNull(post("current_medications"));
$immunization_status = post("immunization_status") ?? "unknown";

/* INSURANCE */
$coverage_type = post("coverage_type") ?? "primary";
$provider_name = toNull(post("provider_name"));
$policy_number = toNull(post("policy_number"));
$group_number = toNull(post("group_number"));
$effective_date = toNull(post("effective_date"));
$subscriber_name = toNull(post("subscriber_name"));
$relationship = post("relationship") ?? "self";
$verified_status = post("verified_status") ?? "unverified";

/* EMERGENCY */
$ec_full_name = toNull(post("ec_full_name"));
$ec_relationship = toNull(post("ec_relationship"));
$ec_phone = toNull(post("ec_phone"));
$ec_email = toNull(post("ec_email"));
$ec_address = toNull(post("ec_address"));
$ec_is_primary = isset($_POST["ec_is_primary"]) ? 1 : 0;

try {

  $conn->begin_transaction();

  /* =========================
     1️⃣ UPDATE PATIENT
  ========================= */
  $stmt = $conn->prepare("
    UPDATE patients
    SET
      first_name = ?, middle_name = ?, last_name = ?, preferred_name = ?,
      marital_status = ?, occupation = ?, preferred_language = ?,
      date_of_birth = ?, gender = ?, blood_type = ?,
      phone = ?, email = ?, address_line = ?, city = ?, state = ?, zip_code = ?,
      status = ?, updated_at = CURRENT_TIMESTAMP
    WHERE patient_id = ?
    LIMIT 1
  ");

  $stmt->bind_param(
    "sssssssssssssssssi",
    $first_name, $middle_name, $last_name, $preferred_name,
    $marital_status, $occupation, $preferred_language,
    $date_of_birth, $gender, $blood_type,
    $phone, $email, $address_line, $city, $state, $zip_code,
    $status, $patient_id
  );

  $stmt->execute();
  $stmt->close();

  /* =========================
     2️⃣ MEDICAL (CONDITIONAL)
  ========================= */
  $hasMedical =
    $allergies !== null ||
    $chronic_conditions !== null ||
    $current_medications !== null ||
    $immunization_status !== "unknown";

  if ($hasMedical) {

    $stmt = $conn->prepare("SELECT patient_id FROM patient_medical_profile WHERE patient_id = ?");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($exists) {
      $stmt = $conn->prepare("
        UPDATE patient_medical_profile
        SET allergies = ?, chronic_conditions = ?, current_medications = ?, immunization_status = ?
        WHERE patient_id = ?
      ");
      $stmt->bind_param("ssssi", $allergies, $chronic_conditions, $current_medications, $immunization_status, $patient_id);
    } else {
      $stmt = $conn->prepare("
        INSERT INTO patient_medical_profile
        (patient_id, allergies, chronic_conditions, current_medications, immunization_status)
        VALUES (?, ?, ?, ?, ?)
      ");
      $stmt->bind_param("issss", $patient_id, $allergies, $chronic_conditions, $current_medications, $immunization_status);
    }

    $stmt->execute();
    $stmt->close();
  }

  /* =========================
     3️⃣ INSURANCE (CONDITIONAL)
  ========================= */
  $hasInsurance =
    $provider_name !== null ||
    $policy_number !== null ||
    $group_number !== null ||
    $effective_date !== null ||
    $subscriber_name !== null;

  if ($hasInsurance) {

    $stmt = $conn->prepare("
      SELECT insurance_id FROM patient_insurance
      WHERE patient_id = ?
      ORDER BY coverage_type = 'primary' DESC
      LIMIT 1
    ");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row) {
      $insurance_id = (int)$row['insurance_id'];

      $stmt = $conn->prepare("
        UPDATE patient_insurance
        SET coverage_type=?, provider_name=?, policy_number=?, group_number=?,
            effective_date=?, subscriber_name=?, relationship=?, verified_status=?
        WHERE insurance_id=?
      ");

      $stmt->bind_param(
        "ssssssssi",
        $coverage_type, $provider_name, $policy_number, $group_number,
        $effective_date, $subscriber_name, $relationship, $verified_status,
        $insurance_id
      );
    } else {
      $stmt = $conn->prepare("
        INSERT INTO patient_insurance
        (patient_id, coverage_type, provider_name, policy_number, group_number,
         effective_date, subscriber_name, relationship, verified_status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
      ");

      $stmt->bind_param(
        "issssssss",
        $patient_id, $coverage_type, $provider_name, $policy_number,
        $group_number, $effective_date, $subscriber_name,
        $relationship, $verified_status
      );
    }

    $stmt->execute();
    $stmt->close();
  }

  /* =========================
   4️⃣ EMERGENCY (OPTIONAL 2 CONTACTS)
========================= */

// Contact 1
$ec1_full_name = toNull(post("ec1_full_name"));
$ec1_relationship = toNull(post("ec1_relationship"));
$ec1_phone = toNull(post("ec1_phone"));
$ec1_email = toNull(post("ec1_email"));
$ec1_address = toNull(post("ec1_address"));
$ec1_is_primary = isset($_POST["ec1_is_primary"]) ? 1 : 0;

// Contact 2
$ec2_full_name = toNull(post("ec2_full_name"));
$ec2_relationship = toNull(post("ec2_relationship"));
$ec2_phone = toNull(post("ec2_phone"));
$ec2_email = toNull(post("ec2_email"));
$ec2_address = toNull(post("ec2_address"));
$ec2_is_primary = isset($_POST["ec2_is_primary"]) ? 1 : 0;

// Allow only one primary
if ($ec1_is_primary && $ec2_is_primary) {
  $ec2_is_primary = 0;
}

// 🔥 Reset existing primary in DB
if ($ec1_is_primary || $ec2_is_primary) {
  $stmt = $conn->prepare("
    UPDATE patient_emergency_contacts
    SET is_primary = 0
    WHERE patient_id = ?
  ");
  $stmt->bind_param("i", $patient_id);
  $stmt->execute();
  $stmt->close();
}


// Get existing contacts (max 2)
$stmt = $conn->prepare("
  SELECT contact_id
  FROM patient_emergency_contacts
  WHERE patient_id = ?
  ORDER BY contact_id ASC
  LIMIT 2
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$existing = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Helper to insert/update
function saveEmergency($conn, $patient_id, $data, $contact_id = null) {

  // If completely empty → skip
  if (
    $data['full_name'] === null &&
    $data['phone'] === null &&
    $data['relationship'] === null &&
    $data['email'] === null &&
    $data['address'] === null
  ) {
    return;
  }

  if ($contact_id) {
    $stmt = $conn->prepare("
      UPDATE patient_emergency_contacts
      SET full_name=?, relationship=?, phone=?, email=?, address=?, is_primary=?
      WHERE contact_id=?
    ");

    $stmt->bind_param(
      "sssssii",
      $data['full_name'],
      $data['relationship'],
      $data['phone'],
      $data['email'],
      $data['address'],
      $data['is_primary'],
      $contact_id
    );
  } else {
    $stmt = $conn->prepare("
      INSERT INTO patient_emergency_contacts
      (patient_id, full_name, relationship, phone, email, address, is_primary)
      VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
      "isssssi",
      $patient_id,
      $data['full_name'],
      $data['relationship'],
      $data['phone'],
      $data['email'],
      $data['address'],
      $data['is_primary']
    );
  }

  $stmt->execute();
  $stmt->close();
}

// Save Contact 1
saveEmergency($conn, $patient_id, [
  'full_name' => $ec1_full_name,
  'relationship' => $ec1_relationship,
  'phone' => $ec1_phone,
  'email' => $ec1_email,
  'address' => $ec1_address,
  'is_primary' => $ec1_is_primary
], $existing[0]['contact_id'] ?? null);

// Save Contact 2
saveEmergency($conn, $patient_id, [
  'full_name' => $ec2_full_name,
  'relationship' => $ec2_relationship,
  'phone' => $ec2_phone,
  'email' => $ec2_email,
  'address' => $ec2_address,
  'is_primary' => $ec2_is_primary
], $existing[1]['contact_id'] ?? null);
$conn->commit();


  echo json_encode(["ok" => true, "patient_id" => $patient_id]);

} catch (Throwable $e) {

  if ($conn) $conn->rollback();

  http_response_code(500);
  echo json_encode([
    "ok" => false,
    "error" => "Server error: " . $e->getMessage()
  ]);
}
