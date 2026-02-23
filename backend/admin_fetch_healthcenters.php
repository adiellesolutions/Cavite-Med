<?php
require_once "db/cavitemed_db.php";
header("Content-Type: application/json");

$result = $conn->query("
    SELECT id, center_name
    FROM health_centers
    WHERE status = 'active'
    ORDER BY center_name ASC
");

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode([
    "data" => $data
]);