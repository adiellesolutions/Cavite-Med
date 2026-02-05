<?php
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

// ---------- Generate MRN (simple example) ----------
// Format: YYYY-000001 (resets per year)
// You can change this to your preferred MRN format.
$year = date("Y");
$mrn = null;

try {
    // Make sure $conn is your mysqli connection from db_connection.php
    if (!isset($conn) || !($conn instanceof mysqli)) {
        respond(false, "Database connection not found. Check db_connection.php.");
    }

    // Get current max MRN for this year
    $mrnPrefix = $year . "-";
    $stmt_mrn = $conn->prepare("SELECT mrn FROM patients WHERE mrn LIKE CONCAT(?, '%') ORDER BY mrn DESC LIMIT 1");
    if (!$stmt_mrn) respond(false, "MRN query prepare failed: " . $conn->error);

    $stmt_mrn->bind_param("s", $mrnPrefix);
    if (!$stmt_mrn->execute()) respond(false, "MRN query execute failed: " . $stmt_mrn->error);

    $res = $stmt_mrn->get_result();
    if ($row = $res->fetch_assoc()) {
        // existing: YYYY-000123
        $last = $row['mrn'];
        $parts = explode("-", $last);
        $num = isset($parts[1]) ? (int)$parts[1] : 0;
        $num++;
        $mrn = $year . "-" . str_pad((string)$num, 6, "0", STR_PAD_LEFT);
    } else {
        $mrn = $year . "-000001";
    }
    $stmt_mrn->close();

    // ---------- Insert Patient ----------
    // NOTE: includes status (since your table has it); created_at/updated_at auto.
    $sql = "INSERT INTO patients (
        mrn,
        first_name, last_name, middle_name, preferred_name,
        marital_status, occupation, preferred_language,
        date_of_birth, gender, blood_type,
        phone, email,
        address_line, city, state, zip_code,
        status,
        created_by
    ) VALUES (
        ?, ?, ?, ?, ?,
        ?, ?, ?,
        ?, ?, ?,
        ?, ?,
        ?, ?, ?, ?,
        ?,
        ?
    )";

    $stmt = $conn->prepare($sql);
    if (!$stmt) respond(false, "Insert prepare failed: " . $conn->error);

    // 19 values total:
    // 18 strings + 1 int (created_by)
    // mrn (s)
    // first_name..status (all strings)
    // created_by (i)
    $stmt->bind_param(
        "ssssssssssssssssssi",
        $mrn,
        $first_name, $last_name, $middle_name, $preferred_name,
        $marital_status, $occupation, $preferred_language,
        $date_of_birth, $gender, $blood_type,
        $phone, $email,
        $address_line, $city, $state, $zip_code,
        $status,
        $created_by
    );

    if (!$stmt->execute()) {
        // Duplicate MRN or other errors
        respond(false, "Insert failed: " . $stmt->error);
    }

    $new_id = $stmt->insert_id;
    $stmt->close();

    respond(true, "Patient registered successfully.", [
        "patient_id" => $new_id,
        "mrn" => $mrn
    ]);

} catch (Throwable $e) {
    respond(false, "Server error: " . $e->getMessage());
}
