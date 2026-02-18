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

/* ✅ ADDED: created_by for emergency FK */
$created_by = (int)($_SESSION['user_id'] ?? 0);
if ($created_by <= 0) {
  http_response_code(401);
  echo json_encode(["ok" => false, "error" => "Invalid session user_id"]);
  exit;
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

/* EMERGENCY (your original single-contact vars - KEEP) */
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

  if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);

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

    $stmt = $conn->prepare("SELECT patient_id FROM patient_medical_profile WHERE patient_id = ? LIMIT 1");
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
     ✅ FIX: avoid NOT NULL policy_number errors
  ========================= */
  $hasInsurance =
    $provider_name !== null ||
    $policy_number !== null ||
    $group_number !== null ||
    $effective_date !== null ||
    $subscriber_name !== null;

  // ✅ If may insurance info pero policy_number blank and DB requires NOT NULL → use empty string instead of NULL
  if ($hasInsurance && $policy_number === null) {
    $policy_number = ""; // important for NOT NULL columns
  }

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
   ✅ FIX: supports BOTH ec_* and ec1_/ec2_ inputs
   ✅ FIX: created_by FK on insert
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

  /* ✅ ADDED: if your modal sends only ec_* (single contact),
     map it to ec1_* so this emergency block will still save */
  if (
    $ec1_full_name === null && $ec1_phone === null && $ec1_relationship === null &&
    $ec1_email === null && $ec1_address === null
  ) {
    // use ec_* as contact 1
    $ec1_full_name = $ec_full_name;
    $ec1_relationship = $ec_relationship;
    $ec1_phone = $ec_phone;
    $ec1_email = $ec_email;
    $ec1_address = $ec_address;
    $ec1_is_primary = $ec_is_primary;
  }

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
  function saveEmergency($conn, $patient_id, $created_by, $data, $contact_id = null) {

    // If completely empty → skip (optional)
    $allEmpty =
      $data['full_name'] === null &&
      $data['phone'] === null &&
      $data['relationship'] === null &&
      $data['email'] === null &&
      $data['address'] === null;

    if ($allEmpty) return;

    // ✅ ADDED: if may laman but missing full_name, stop with clear error (DB requires NOT NULL)
    if ($data['full_name'] === null) {
      throw new Exception("Emergency contact full name is required if you fill any emergency contact fields.");
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
      // ✅ ADDED: created_by to satisfy FK constraint
      $stmt = $conn->prepare("
        INSERT INTO patient_emergency_contacts
        (patient_id, full_name, relationship, phone, email, address, is_primary, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
      ");

      $stmt->bind_param(
        "isssssii",
        $patient_id,
        $data['full_name'],
        $data['relationship'],
        $data['phone'],
        $data['email'],
        $data['address'],
        $data['is_primary'],
        $created_by
      );
    }

    $stmt->execute();
    $stmt->close();
  }

  // Save Contact 1
  saveEmergency($conn, $patient_id, $created_by, [
    'full_name' => $ec1_full_name,
    'relationship' => $ec1_relationship,
    'phone' => $ec1_phone,
    'email' => $ec1_email,
    'address' => $ec1_address,
    'is_primary' => $ec1_is_primary
  ], $existing[0]['contact_id'] ?? null);

  // Save Contact 2
  saveEmergency($conn, $patient_id, $created_by, [
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
