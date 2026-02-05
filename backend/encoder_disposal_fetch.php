<?php
session_start();
header("Content-Type: application/json");
require_once __DIR__ . "/db/cavitemed_db.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["data" => [], "total" => 0]);
    exit;
}

$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = max(1, (int)($_GET['limit'] ?? 8));
$offset = ($page - 1) * $limit;

/* TOTAL COUNT */
$countResult = $conn->query("SELECT COUNT(*) AS total FROM disposal_records");
$total = (int)($countResult->fetch_assoc()['total'] ?? 0);

/* DATA */
$stmt = $conn->prepare("
    SELECT
        dr.id,
        dr.medicine_id,
        m.medicine_name,
        m.barcode,
        dr.batch_number,
        dr.expiry_date,
        dr.quantity,
        dr.total_value,
        dr.disposal_method,
        dr.disposal_date,
        dr.notes
    FROM disposal_records dr
    JOIN medicine m 
        ON dr.medicine_id = m.id
    WHERE
        dr.is_archived = 0
        AND m.is_archived = 0
    ORDER BY dr.disposal_date DESC
    LIMIT ? OFFSET ?
");


$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode([
    "data" => $data,
    "total" => $total
]);
