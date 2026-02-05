<?php
session_start();
require_once __DIR__ . "/db/cavitemed_db.php";

// 🔐 Security
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request");
}

// =======================
// COLLECT + SANITIZE
// =======================
$medicine_id        = (int) ($_POST['medicine_id'] ?? 0);
$supplier_id        = (int) ($_POST['supplier_id'] ?? 0);
$barcode            = trim($_POST['barcode'] ?? '');
$medicine_name      = trim($_POST['medicine_name'] ?? '');
$medicine_type      = $_POST['medicine_type'] ?? '';
$category           = trim($_POST['category'] ?? '');
$funding_source     = $_POST['funding_source'] ?? '';
$batch_number       = trim($_POST['batch_number'] ?? '');
$manufacturing_date = $_POST['manufacturing_date'] ?? '';
$expiry_date        = $_POST['expiry_date'] ?? '';
$unit_of_measure    = $_POST['unit_of_measure'] ?? '';
$current_stock      = (int) ($_POST['current_stock'] ?? 0);
$reorder_point      = (int) ($_POST['reorder_point'] ?? 0);
$unit_price         = (float) ($_POST['unit_price'] ?? 0);
$notes              = $_POST['notes'] ?? null;

// =======================
// VALIDATION
// =======================
if (
    !$medicine_id ||
    !$supplier_id ||
    !$barcode ||
    !$medicine_name ||
    !$medicine_type ||
    !$category ||
    !$funding_source ||
    !$batch_number ||
    !$manufacturing_date ||
    !$expiry_date ||
    !$unit_of_measure
) {
    die("Missing required fields");
}

// =======================
// AUTO STATUS
// =======================
$status = 'in_stock';

if ($current_stock <= 0) {
    $status = 'out_of_stock';
} elseif ($current_stock <= $reorder_point) {
    $status = 'low_stock';
}

if ($expiry_date < date('Y-m-d')) {
    $status = 'expired';
}

// =======================
// UPDATE
// =======================
$sql = "
    UPDATE medicine SET
        supplier_id = ?,
        barcode = ?,
        medicine_name = ?,
        medicine_type = ?,
        category = ?,
        funding_source = ?,
        batch_number = ?,
        expiry_date = ?,
        unit_of_measure = ?,
        manufacturing_date = ?,
        current_stock = ?,
        reorder_point = ?,
        unit_price = ?,
        notes = ?,
        status = ?
    WHERE id = ?
";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "isssssssssiidssi",
    $supplier_id,
    $barcode,
    $medicine_name,
    $medicine_type,
    $category,
    $funding_source,
    $batch_number,
    $expiry_date,
    $unit_of_measure,
    $manufacturing_date,
    $current_stock,
    $reorder_point,
    $unit_price,
    $notes,
    $status,
    $medicine_id
);

if (!$stmt->execute()) {
    die("Update failed: " . $stmt->error);
}

// =======================
// SUCCESS
// =======================
header("Location: ../pages/encoder_inventory.php?updated=1");
exit;
