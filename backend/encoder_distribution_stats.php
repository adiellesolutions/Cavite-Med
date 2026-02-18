<?php
session_start();
require_once __DIR__ . '/db/cavitemed_db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$sql = "
    SELECT 
        SUM(status = 'distributed') AS distributed,
        SUM(status = 'pending') AS pending,
        SUM(status = 'cancelled') AS cancelled,
        SUM(status = 'returned') AS returned
    FROM distribution
";

$result = $conn->query($sql);
$row = $result->fetch_assoc();

echo json_encode([
    'success' => true,
    'distributed' => intval($row['distributed']),
    'pending' => intval($row['pending']),
    'cancelled' => intval($row['cancelled']),
    'returned' => intval($row['returned'])
]);
