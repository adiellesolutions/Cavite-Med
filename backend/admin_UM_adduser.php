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

$full_name = trim($_POST['full_name'] ?? '');
$username  = trim($_POST['username'] ?? '');
$role      = $_POST['role'] ?? '';
$status    = $_POST['status'] ?? 'active';

if ($full_name === '' || $username === '' || $role === '') {
    echo json_encode(['success' => false, 'message' => 'Required fields missing']);
    exit;
}

$email          = $_POST['email'] ?? null;
$contact_number = $_POST['contact_number'] ?? null;
$position       = $_POST['position'] ?? null;
$clinic         = $_POST['clinic'] ?? null;

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
    (full_name, username, password, role, email, contact_number, position, clinic, status, profile_picture)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "ssssssssss",
    $full_name,
    $username,
    $password,
    $role,
    $email,
    $contact_number,
    $position,
    $clinic,
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