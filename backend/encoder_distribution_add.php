<?php
session_start();
require_once __DIR__ . '/db/cavitemed_db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_POST['action'] !== 'create') {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

$user_id = $_SESSION['user_id'];
$health_center_id = intval($_POST['health_center_id']);
$medicine_id = intval($_POST['medicine_id']);
$quantity = intval($_POST['quantity']);
$remarks = $conn->real_escape_string($_POST['remarks'] ?? '');

if ($health_center_id <= 0 || $medicine_id <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

/* =============================
   Check Available Stock
============================= */
$stockQuery = $conn->query("
    SELECT current_stock 
    FROM medicine 
    WHERE id = $medicine_id
");

$row = $stockQuery->fetch_assoc();
$current_stock = intval($row['current_stock']);

if ($quantity > $current_stock) {
    echo json_encode(['success' => false, 'message' => 'Quantity exceeds stock']);
    exit;
}

/* =============================
   Insert Distribution
============================= */
$conn->query("
    INSERT INTO distribution 
    (health_center_id, medicine_id, quantity, status, remarks, created_by)
    VALUES
    ($health_center_id, $medicine_id, $quantity, 'distributed', '$remarks', $user_id)
");

/* =============================
   Deduct Stock
============================= */
$conn->query("
    UPDATE medicine 
    SET current_stock = current_stock - $quantity
    WHERE id = $medicine_id
");

/* =============================
   Log Transaction
============================= */
$conn->query("
    INSERT INTO inventory_transactions
    (medicine_id, transaction_type, quantity, remarks, performed_by)
    VALUES
    ($medicine_id, 'distribute', $quantity, '$remarks', $user_id)
");

echo json_encode(['success' => true]);
