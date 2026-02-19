<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

require_once __DIR__ . "/db/cavitemed_db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

/* ===== REQUIRED FIELDS ===== */
$full_name = trim($_POST['full_name'] ?? '');
$username  = trim($_POST['username'] ?? '');
$role      = $_POST['role'] ?? '';
$status    = $_POST['status'] ?? 'active';

if ($full_name === '' || $username === '' || $role === '') {
    echo json_encode(['success' => false, 'message' => 'Required fields missing']);
    exit;
}

/* ===== OPTIONAL FIELDS ===== */
$email          = $_POST['email'] ?? null;
$contact_number = $_POST['contact_number'] ?? null;
$position       = $_POST['position'] ?? null;

/* ===== HEALTH CENTER ===== */
$health_center_id = isset($_POST['health_center_id']) && $_POST['health_center_id'] !== ''
    ? (int) $_POST['health_center_id']
    : null;

/* Admin does not require health center */
if ($role !== 'admin' && empty($health_center_id)) {
    echo json_encode(['success' => false, 'message' => 'Health center is required for this role']);
    exit;
}

/* Convert empty string to NULL */
if ($health_center_id === '' || $role === 'admin') {
    $health_center_id = null;
}

/* ===== DEFAULT PASSWORD ===== */
$password = password_hash("cavmed2025", PASSWORD_DEFAULT);

/* ===== PROFILE UPLOAD ===== */
$profilePath = null;

if (!empty($_FILES['profile_picture']['name'])) {
    $allowed = ['image/jpeg', 'image/png'];

    if (!in_array($_FILES['profile_picture']['type'], $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Invalid image type']);
        exit;
    }

    if ($_FILES['profile_picture']['size'] > 2 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Image too large']);
        exit;
    }

    $uploadDir = __DIR__ . "/../uploads/profile/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
    $fileName = uniqid("profile_") . "." . $ext;
    $profilePath = "uploads/profile/" . $fileName;

    if (!move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadDir . $fileName)) {
        echo json_encode(['success' => false, 'message' => 'Upload failed']);
        exit;
    }
}

/* ===== INSERT ===== */
$stmt = $conn->prepare("
    INSERT INTO users
    (full_name, username, password, role, email, contact_number, position, health_center_id, status, profile_picture)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "sssssssiss",
    $full_name,
    $username,
    $password,
    $role,
    $email,
    $contact_number,
    $position,
    $health_center_id,
    $status,
    $profilePath
);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Username already exists']);
exit;
?>
