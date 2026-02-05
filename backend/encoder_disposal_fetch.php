<?php
session_start();
require_once __DIR__ . "/db/cavitemed_db.php";

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit;
}

$sql = "
    SELECT
        dr.id,
        m.medicine_name,
        m.barcode,
        dr.batch_number,
        dr.expiry_date,
        dr.quantity,
        dr.total_value,
        dr.disposal_method,
        dr.disposal_date
    FROM disposal_records dr
    JOIN medicine m ON dr.medicine_id = m.id
    ORDER BY dr.disposal_date DESC
";

$result = $conn->query($sql);

$records = [];

while ($row = $result->fetch_assoc()) {
    $records[] = $row;
}

echo json_encode($records);
