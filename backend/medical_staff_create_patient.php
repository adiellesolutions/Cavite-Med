<?php
error_reporting(0);
ini_set('display_errors', 0);
// backend/medical_staff_create_patient.php
session_start();
header('Content-Type: application/json');

// ✅ Adjust this path to your DB connection file
// Example: require_once __DIR__ . "/db_connection.php";
require_once __DIR__ . "/db/cavitemed_db.php"; // <-- change if needed

// ---------- Helpers ----------
function respond($ok, $message, $extra = []) {
    echo json_encode(array_merge([
        "success" => $ok,
        "message" => $message
    ], $extra));
    exit;
}

// ---------- Auth ----------
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? "") !== "medical_staff") {
    respond(false, "Unauthorized.");
}
$created_by = (int)$_SESSION['user_id'];

// ---------- Input (POST) ----------
$first_name = trim($_POST['first_name'] ?? "");
$last_name  = trim($_POST['last_name'] ?? "");

$middle_name = trim($_POST['middle_name'] ?? "");
$middle_name = ($middle_name === "") ? null : $middle_name;

$preferred_name = trim($_POST['preferred_name'] ?? "");
$preferred_name = ($preferred_name === "") ? null : $preferred_name;

$marital_status = trim($_POST['marital_status'] ?? "");
$marital_status = ($marital_status === "") ? null : $marital_status;

$occupation = trim($_POST['occupation'] ?? "");
$occupation = ($occupation === "") ? null : $occupation;

$preferred_language = trim($_POST['preferred_language'] ?? "");
$preferred_language = ($preferred_language === "") ? null : $preferred_language;

$date_of_birth = trim($_POST['date_of_birth'] ?? "");
$gender = trim($_POST['gender'] ?? "");

$blood_type = trim($_POST['blood_type'] ?? "");
$blood_type = ($blood_type === "") ? null : $blood_type;

$phone = trim($_POST['phone'] ?? "");

$email = trim($_POST['email'] ?? "");
$email = ($email === "") ? null : $email;

$address_line = trim($_POST['address_line'] ?? "");
$city = trim($_POST['city'] ?? "");
$state = trim($_POST['state'] ?? "");
$zip_code = trim($_POST['zip_code'] ?? "");

// Optional: let form send status, else default active
$status = trim($_POST['status'] ?? "");
$status = ($status === "") ? "active" : $status;

// ---------- Validate required ----------
if ($first_name === "" || $last_name === "" || $date_of_birth === "" || $gender === "" || $phone === "" ||
    $address_line === "" || $city === "" || $state === "" || $zip_code === "") {
    respond(false, "Please complete all required fields.");
}

// Basic email validation if provided
if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, "Invalid email format.");
}

// Gender validation
$allowed_genders = ["male", "female", "other"];
if (!in_array($gender, $allowed_genders, true)) {
    respond(false, "Invalid gender value.");
}

// Blood type validation (if provided)
$allowed_blood = ["A+","A-","B+","B-","AB+","AB-","O+","O-"];
if ($blood_type !== null && !in_array($blood_type, $allowed_blood, true)) {
    respond(false, "Invalid blood type value.");
}

// Status validation
$allowed_status = ["active", "inactive"];
if (!in_array($status, $allowed_status, true)) {
    respond(false, "Invalid status value.");
}


// ---------- Get staff health_center_id ----------
$st = $conn->prepare("SELECT health_center_id FROM users WHERE user_id=? LIMIT 1");
if (!$st) respond(false, "Prepare failed: " . $conn->error);

$st->bind_param("i", $created_by);
if (!$st->execute()) respond(false, "Execute failed: " . $st->error);

$st->bind_result($health_center_id);
$st->fetch();
$st->close();

$health_center_id = $health_center_id ? (int)$health_center_id : null;

if (!$health_center_id) {
    respond(false, "Your account is not assigned to any health center. Please contact admin.");
}

    $conn->begin_transaction();
// ---------- Generate MRN (simple example) ----------
// Format: YYYY-000001 (resets per year)
// You can change this to your preferred MRN format.
$year = date("Y");

// Insert or increment atomically
$stmt = $conn->prepare("
    INSERT INTO mrn_counter (year, last_number)
    VALUES (?, 1)
    ON DUPLICATE KEY UPDATE last_number = last_number + 1
");
$stmt->bind_param("i", $year);
$stmt->execute();
$stmt->close();

// Get the updated number
$stmt2 = $conn->prepare("SELECT last_number FROM mrn_counter WHERE year=?");
$stmt2->bind_param("i", $year);
$stmt2->execute();
$stmt2->bind_result($num);
$stmt2->fetch();
$stmt2->close();

$mrn = $year . "-" . str_pad($num, 6, "0", STR_PAD_LEFT);
    // ---------- Insert Patient ----------
    // NOTE: includes status (since your table has it); created_at/updated_at auto.


// ---------- Insert Patient ----------
$sql = "INSERT INTO patients (
    mrn,
    first_name, last_name, middle_name, preferred_name,
    marital_status, occupation, preferred_language,
    date_of_birth, gender, blood_type,
    phone, email,
    address_line, city, state, zip_code,
    status,
    health_center_id,
    created_by
) VALUES (
    ?, ?, ?, ?, ?,
    ?, ?, ?,
    ?, ?, ?,
    ?, ?,
    ?, ?, ?, ?,
    ?,
    ?,
    ?
)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    $conn->rollback();
    respond(false, "Insert prepare failed: " . $conn->error);
}

$stmt->bind_param(
    "ssssssssssssssssssii",
    $mrn,
    $first_name, $last_name, $middle_name, $preferred_name,
    $marital_status, $occupation, $preferred_language,
    $date_of_birth, $gender, $blood_type,
    $phone, $email,
    $address_line, $city, $state, $zip_code,
    $status,
    $health_center_id,
    $created_by
);

if (!$stmt->execute()) {
    $conn->rollback();
    respond(false, "Insert failed: " . $stmt->error);
}

$new_id = (int)$stmt->insert_id;
$stmt->close();


// ✅ IMPORTANT: create empty medical profile row (updated_by must be a valid users.user_id)
$prof = $conn->prepare("
    INSERT INTO patient_medical_profile
      (patient_id, allergies, chronic_conditions, current_medications, immunization_status, updated_by)
    VALUES
      (?, NULL, NULL, NULL, 'unknown', ?)
");
if (!$prof) {
    $conn->rollback();
    respond(false, "Profile prepare failed: " . $conn->error);
}

$prof->bind_param("ii", $new_id, $created_by);

if (!$prof->execute()) {
    $conn->rollback();
    respond(false, "Profile insert failed: " . $prof->error);
}

$prof->close();

$conn->commit();

respond(true, "Patient registered successfully.", [
    "patient_id" => $new_id,
    "mrn" => $mrn
]);