<?php
header('Content-Type: application/json');
require_once __DIR__ . "/db/cavitemed_db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$user_id = $_POST['user_id'] ?? null;

if (!is_numeric($user_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

/* ===== DEFAULT PASSWORD ===== */
$defaultPassword = password_hash("cavmed2025", PASSWORD_DEFAULT);

$stmt = $conn->prepare("
    UPDATE users
    SET 
        password = ?,
        failed_attempts = 0,
        must_change_password = 1
    WHERE user_id = ?
      AND deleted_at IS NULL
");

$stmt->bind_param("si", $defaultPassword, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Password reset failed']);
exit;
