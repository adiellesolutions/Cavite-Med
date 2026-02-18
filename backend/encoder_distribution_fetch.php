<?php
session_start();
require_once __DIR__ . '/db/cavitemed_db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$result = $conn->query("
    SELECT 
        d.id,
        d.health_center_id,
        d.medicine_id,
        d.quantity,
        d.status,
        d.remarks,
        d.created_at,
        h.center_name,
        m.medicine_name,
        u.full_name
    FROM distribution d
    JOIN health_centers h ON d.health_center_id = h.id
    JOIN medicine m ON d.medicine_id = m.id
    JOIN users u ON d.created_by = u.user_id
    ORDER BY d.created_at DESC
");

$distributions = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $distributions[] = $row;
    }
}

echo json_encode([
    'success' => true,
    'data' => $distributions
]);
