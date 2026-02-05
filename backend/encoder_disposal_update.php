<?php
session_start();
header("Content-Type: application/json");
require_once __DIR__ . "/db/cavitemed_db.php";

ini_set('display_errors', 0);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$id         = (int)($data['id'] ?? 0);
$medicineId = (int)($data['medicine_id'] ?? 0);
$newQty     = (int)($data['quantity'] ?? 0);
$notes      = isset($data['notes']) && trim($data['notes']) !== ''
    ? trim($data['notes'])
    : null;

if ($id <= 0 || $medicineId <= 0 || $newQty <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid input"]);
    exit;
}

$conn->begin_transaction();

try {

    /* 1️⃣ LOCK EXISTING DISPOSAL RECORD */
    $stmt = $conn->prepare("
        SELECT quantity
        FROM disposal_records
        WHERE id = ? AND medicine_id = ?
        FOR UPDATE
    ");
    $stmt->bind_param("ii", $id, $medicineId);
    $stmt->execute();
    $old = $stmt->get_result()->fetch_assoc();

    if (!$old) {
        throw new Exception("Disposal record not found");
    }

    $oldQty = (int)$old['quantity'];

    /* 2️⃣ LOCK MEDICINE ROW */
    $stmt = $conn->prepare("
        SELECT current_stock, reorder_point
        FROM medicine
        WHERE id = ? AND is_archived = 0
        FOR UPDATE
    ");
    $stmt->bind_param("i", $medicineId);
    $stmt->execute();
    $medicine = $stmt->get_result()->fetch_assoc();

    if (!$medicine) {
        throw new Exception("Medicine not found");
    }

    /* 3️⃣ CALCULATE STOCK DIFFERENCE */
    $diff = $newQty - $oldQty; // + = deduct more, - = return stock

    if ($diff > 0 && $diff > $medicine['current_stock']) {
        throw new Exception("Quantity exceeds available stock");
    }

    $newStock = $medicine['current_stock'] - $diff;

    /* 4️⃣ UPDATE DISPOSAL RECORD */
    $stmt = $conn->prepare("
        UPDATE disposal_records
        SET
            batch_number = ?,
            expiry_date = ?,
            quantity = ?,
            total_value = ?,
            disposal_method = ?,
            disposal_date = ?,
            notes = ?
        WHERE id = ?
    ");
    $stmt->bind_param(
        "ssidsssi",
        $data['batch_number'],
        $data['expiry_date'],
        $newQty,
        $data['total_value'],
        $data['disposal_method'],
        $data['disposal_date'],
        $data['notes'],
        $id
    );

    if (!$stmt->execute()) {
        throw new Exception("Failed to update disposal record");
    }

    /* 5️⃣ UPDATE MEDICINE STOCK + STATUS */
    $status = "in_stock";
    if ($newStock <= 0) {
        $status = "out_of_stock";
    } elseif ($newStock <= $medicine['reorder_point']) {
        $status = "low_stock";
    }

    $stmt = $conn->prepare("
        UPDATE medicine
        SET current_stock = ?, status = ?
        WHERE id = ?
    ");
    $stmt->bind_param("isi", $newStock, $status, $medicineId);
    $stmt->execute();

    /* 6️⃣ LOG INVENTORY ADJUSTMENT */
    if ($diff !== 0) {
        $remarks = "Edited disposal (Δ {$diff})";

        $stmt = $conn->prepare("
            INSERT INTO inventory_transactions
            (
                medicine_id,
                transaction_type,
                quantity,
                remarks,
                performed_by
            )
            VALUES (?, 'adjustment', ?, ?, ?)
        ");
        $stmt->bind_param(
            "iisi",
            $medicineId,
            abs($diff),
            $remarks,
            $_SESSION['user_id']
        );
        $stmt->execute();
    }

    $conn->commit();

    echo json_encode([
        "success" => true,
        "remaining_stock" => $newStock
    ]);

} catch (Exception $e) {

    $conn->rollback();

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
