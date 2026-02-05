<?php
session_start();
header("Content-Type: application/json");
require_once __DIR__ . "/db/cavitemed_db.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$sql = "
    SELECT id, medicine_name, batch_number, expiry_date
    FROM medicine
    WHERE is_archived = 0
    ORDER BY medicine_name ASC
";

$result = $conn->query($sql);

$medicines = [];
while ($row = $result->fetch_assoc()) {
    $medicines[] = $row;
}

echo json_encode($medicines);
