<?php
require_once "db/cavitemed_db.php";

header("Content-Type: application/json");

/* Total Archived Suppliers */
$supplierCount = $conn->query("
    SELECT COUNT(*) as total 
    FROM suppliers 
    WHERE is_archived = 1
")->fetch_assoc()['total'] ?? 0;

/* Total Archived Medicines */
$medicineResult = $conn->query("
    SELECT COUNT(*) as total,
           IFNULL(SUM(current_stock * unit_price),0) as total_value
    FROM medicine
    WHERE is_archived = 1
");

$medicineData = $medicineResult->fetch_assoc();

echo json_encode([
    "suppliers" => (int)$supplierCount,
    "medicines" => (int)($medicineData['total'] ?? 0),
    "total_value" => (float)($medicineData['total_value'] ?? 0)
]);
