<?php
session_start();
require_once __DIR__ . '/db/cavitemed_db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$distribution_id = intval($_POST['distribution_id']);

if ($distribution_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

/* ============================
   Get Distribution Details
============================ */
$result = $conn->query("
    SELECT medicine_id, quantity, status
    FROM distribution
    WHERE id = $distribution_id
");

if (!$result || $result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Not found']);
    exit;
}

$row = $result->fetch_assoc();

if ($row['status'] === 'cancelled') {
    echo json_encode(['success' => false, 'message' => 'Already restored']);
    exit;
}

$medicine_id = intval($row['medicine_id']);
$quantity = intval($row['quantity']);

/* ============================
   Restore Stock
============================ */
$conn->query("
    UPDATE medicine
    SET current_stock = current_stock + $quantity
    WHERE id = $medicine_id
");

/* ============================
   Update Distribution Status
============================ */
$conn->query("
    UPDATE distribution
    SET status = 'cancelled'
    WHERE id = $distribution_id
");

/* ============================
   Log Transaction
============================ */
$conn->query("
    INSERT INTO inventory_transactions
    (medicine_id, transaction_type, quantity, remarks, performed_by)
    VALUES
    ($medicine_id, 'adjustment', $quantity, 'Distribution restored', $user_id)
");

echo json_encode(['success' => true]);
