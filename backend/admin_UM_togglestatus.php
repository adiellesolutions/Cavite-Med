<?php
header('Content-Type: application/json');
require_once __DIR__ . "/db/cavitemed_db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$user_id = $_POST['user_id'] ?? null;
$status  = $_POST['status'] ?? null;

if (!is_numeric($user_id) || !in_array($status, ['active', 'inactive'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

$stmt = $conn->prepare("
    UPDATE users
    SET status = ?
    WHERE user_id = ? AND deleted_at IS NULL
");

$stmt->bind_param("si", $status, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Update failed']);
exit;
