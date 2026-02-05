<?php
session_start();
header("Content-Type: application/json");
require_once __DIR__ . "/db/cavitemed_db.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$medicineId = (int)$data['medicine_id'];
$qty        = (int)$data['quantity'];

if ($medicineId <= 0 || $qty <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid input"]);
    exit;
}

$conn->begin_transaction();

try {

    /* 1️⃣ GET CURRENT STOCK */
    $stmt = $conn->prepare("
        SELECT current_stock, reorder_point
        FROM medicine
        WHERE id = ? AND is_archived = 0
        FOR UPDATE
    ");
    $stmt->bind_param("i", $medicineId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Medicine not found");
    }

    $medicine = $result->fetch_assoc();

    if ($qty > $medicine['current_stock']) {
        throw new Exception("Quantity exceeds available stock");
    }

    $newStock = $medicine['current_stock'] - $qty;

    /* sanitize notes */
    $notes = isset($data['notes']) && trim($data['notes']) !== ''
        ? trim($data['notes'])
        : null;

    /* 2️⃣ INSERT DISPOSAL RECORD */
    $stmt = $conn->prepare("
        INSERT INTO disposal_records
        (
            medicine_id,
            batch_number,
            expiry_date,
            quantity,
            total_value,
            disposal_method,
            disposal_date,
            notes,
            created_by
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "issidsssi",
        $medicineId,
        $data['batch_number'],
        $data['expiry_date'],
        $qty,
        $data['total_value'],
        $data['disposal_method'],
        $data['disposal_date'],
        $notes,
        $_SESSION['user_id']
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to insert disposal record");
    }

    /* 3️⃣ UPDATE MEDICINE STOCK + STATUS */
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

    if (!$stmt->execute()) {
        throw new Exception("Failed to update medicine stock");
    }

    /* 4️⃣ LOG INVENTORY TRANSACTION */
    $stmt = $conn->prepare("
        INSERT INTO inventory_transactions
        (
            medicine_id,
            transaction_type,
            quantity,
            remarks,
            performed_by
        )
        VALUES (?, 'expired', ?, ?, ?)
    ");

    $remarks = "Disposed {$qty} units (Batch: {$data['batch_number']})";

    $stmt->bind_param(
        "iisi",
        $medicineId,
        $qty,
        $remarks,
        $_SESSION['user_id']
    );

    if (!$stmt->execute()) {
        throw new Exception("Failed to log inventory transaction");
    }

    /* 5️⃣ COMMIT ALL CHANGES */
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
