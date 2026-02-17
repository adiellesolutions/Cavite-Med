<?php
session_start();
require_once __DIR__ . '/db/cavitemed_db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$result = $conn->query("
    SELECT * 
    FROM health_centers
    ORDER BY center_name ASC
");

$healthCenters = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $healthCenters[] = $row;
    }
}

echo json_encode([
    'success' => true,
    'data' => $healthCenters
]);
