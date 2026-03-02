<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . "/db/cavitemed_db.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$sql = "
    SELECT 
        r.quantity,
        r.reason,
        r.created_at,
        m.medicine_name,
        u.full_name AS returned_by,
        h.center_name AS health_center_name
    FROM stock_returns r
    JOIN medicine m ON r.medicine_id = m.id
    JOIN users u ON r.returned_by = u.user_id
    JOIN health_centers h ON r.health_center_id = h.id
    ORDER BY r.created_at DESC
";

$result = $conn->query($sql);

$returns = [];

while ($row = $result->fetch_assoc()) {
    $returns[] = $row;
}

echo json_encode([
    'success' => true,
    'returns' => $returns
]);