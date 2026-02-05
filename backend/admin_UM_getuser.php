<?php
header('Content-Type: application/json');
require_once __DIR__ . "/db/cavitemed_db.php";

$user_id = $_GET['user_id'] ?? null;

if (!is_numeric($user_id)) {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $conn->prepare("
    SELECT user_id, full_name, username, email, role, position,
           contact_number, clinic, status
    FROM users
    WHERE user_id = ? AND deleted_at IS NULL
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user) {
    echo json_encode(['success' => true, 'user' => $user]);
} else {
    echo json_encode(['success' => false]);
}
