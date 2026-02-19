<?php
header('Content-Type: application/json');
require_once __DIR__ . "/db/cavitemed_db.php";

$user_id = $_GET['user_id'] ?? null;

if (!is_numeric($user_id)) {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        u.user_id,
        u.full_name,
        u.username,
        u.email,
        u.role,
        u.position,
        u.contact_number,
        u.status,
        u.health_center_id,
        hc.center_name
    FROM users u
    LEFT JOIN health_centers hc 
        ON u.health_center_id = hc.id
    WHERE u.user_id = ? 
    AND u.deleted_at IS NULL
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
