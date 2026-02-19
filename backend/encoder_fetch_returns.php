<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . "/db/cavitemed_db.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$health_center_id = $_SESSION['health_center_id'] ?? 0;

$sql = "
    SELECT 
        r.quantity,
        r.reason,
        r.created_at,
        m.medicine_name,
        u.full_name AS returned_by
    FROM stock_returns r
    JOIN medicine m ON r.medicine_id = m.id
    JOIN users u ON r.returned_by = u.user_id
    WHERE r.health_center_id = ?
    ORDER BY r.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $health_center_id);
$stmt->execute();
$result = $stmt->get_result();

$returns = [];

while ($row = $result->fetch_assoc()) {
    $returns[] = $row;
}

echo json_encode([
    'success' => true,
    'returns' => $returns
]);
