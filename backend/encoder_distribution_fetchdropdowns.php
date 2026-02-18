<?php
session_start();
require_once __DIR__ . '/db/cavitemed_db.php';

header('Content-Type: application/json');


/* =========================
   Fetch Health Centers
========================= */
$healthCenters = [];
$hcQuery = $conn->query("
    SELECT id, center_name
    FROM health_centers
    WHERE is_archived = 0
    ORDER BY center_name ASC
");

if ($hcQuery) {
    while ($row = $hcQuery->fetch_assoc()) {
        $healthCenters[] = $row;
    }
}

/* =========================
   Fetch Medicines
   Only available stock
========================= */
$medicines = [];
$medQuery = $conn->query("
    SELECT id, medicine_name, barcode
    FROM medicine
    WHERE current_stock > 0
      AND is_archived = 0
      AND status != 'expired'
    ORDER BY medicine_name ASC
");

if ($medQuery) {
    while ($row = $medQuery->fetch_assoc()) {
        $medicines[] = $row;
    }
}

echo json_encode([
    'success' => true,
    'health_centers' => $healthCenters,
    'medicines' => $medicines
]);
