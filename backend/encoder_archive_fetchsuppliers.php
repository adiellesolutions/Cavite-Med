<?php
session_start();
header("Content-Type: application/json");
require_once __DIR__ . "/db/cavitemed_db.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["data" => [], "total" => 0]);
    exit;
}

$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = max(1, (int)($_GET['limit'] ?? 10));
$offset = ($page - 1) * $limit;

/* TOTAL COUNT */
$countStmt = $conn->prepare("
    SELECT COUNT(*) as total
    FROM suppliers
    WHERE is_archived = 1
");
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['total'] ?? 0;

/* FETCH DATA */
$stmt = $conn->prepare("
    SELECT
        id,
        supplier_name,
        supplier_type,
        contact_person,
        contact_number,
        email,
        address
    FROM suppliers
    WHERE is_archived = 1
    ORDER BY supplier_name ASC
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
    "total" => (int)$total
]);
