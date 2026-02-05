<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . "/db/cavitemed_db.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$new     = $_POST['new_password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

/* ===== BASIC VALIDATION ===== */
if (strlen($new) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters']);
    exit;
}

if ($new !== $confirm) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
    exit;
}

/* ===== BLOCK DEFAULT PASSWORD ===== */
if ($new === 'cavmed2025') {
    echo json_encode(['success' => false, 'message' => 'Default password is not allowed']);
    exit;
}

$hash = password_hash($new, PASSWORD_DEFAULT);

/* ===== UPDATE PASSWORD + CLEAR FORCE FLAG ===== */
$stmt = $conn->prepare("
    UPDATE users
    SET 
        password = ?,
        failed_attempts = 0,
        must_change_password = 0
    WHERE user_id = ?
");

$stmt->bind_param("si", $hash, $_SESSION['user_id']);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Update failed']);
    exit;
}

/* ===== CLEAR SESSION FLAG ===== */
unset($_SESSION['force_change_password']);

echo json_encode([
    'success' => true,
    'role' => $_SESSION['role']
]);
exit;
