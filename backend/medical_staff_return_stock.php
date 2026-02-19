<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . "/db/cavitemed_db.php";

/* ==========================================
   AUTH CHECK
========================================== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medical_staff') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$health_center_id = $_SESSION['health_center_id'] ?? null;

if (!$health_center_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid health center']);
    exit;
}

/* ==========================================
   VALIDATE INPUT
========================================== */
$medicine_id = (int)($_POST['medicine_id'] ?? 0);
$quantity    = (int)($_POST['quantity'] ?? 0);
$reason      = trim($_POST['reason'] ?? '');

if ($medicine_id <= 0 || $quantity <= 0 || $reason === '') {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

/* ==========================================
   START TRANSACTION
========================================== */
$conn->begin_transaction();

try {

    /* ======================================
       LOCK CENTER STOCK ROW
    ====================================== */
    $check = $conn->prepare("
        SELECT current_stock
        FROM health_center_inventory
        WHERE health_center_id = ?
          AND medicine_id = ?
        FOR UPDATE
    ");
    $check->bind_param("ii", $health_center_id, $medicine_id);
    $check->execute();
    $result = $check->get_result();
    $row = $result->fetch_assoc();

    if (!$row) {
        throw new Exception("No stock record found for this medicine.");
    }

    if ($row['current_stock'] < $quantity) {
        throw new Exception("Not enough stock to return.");
    }

    /* ======================================
       INSERT RETURN LOG
    ====================================== */
    $insert = $conn->prepare("
        INSERT INTO stock_returns
        (health_center_id, medicine_id, quantity, reason, returned_by)
        VALUES (?, ?, ?, ?, ?)
    ");

    $insert->bind_param(
        "iiisi",
        $health_center_id,
        $medicine_id,
        $quantity,
        $reason,
        $_SESSION['user_id']
    );

    if (!$insert->execute()) {
        throw new Exception("Failed to record return.");
    }

    /* ======================================
       DEDUCT FROM HEALTH CENTER
    ====================================== */
    $deduct = $conn->prepare("
        UPDATE health_center_inventory
        SET current_stock = current_stock - ?
        WHERE health_center_id = ?
          AND medicine_id = ?
    ");

    $deduct->bind_param("iii", $quantity, $health_center_id, $medicine_id);

    if (!$deduct->execute()) {
        throw new Exception("Failed to deduct health center stock.");
    }

    /* ======================================
       ADD BACK TO MAIN WAREHOUSE STOCK
    ====================================== */
    $addBack = $conn->prepare("
        UPDATE medicine
        SET current_stock = current_stock + ?
        WHERE id = ?
    ");

    $addBack->bind_param("ii", $quantity, $medicine_id);

    if (!$addBack->execute()) {
        throw new Exception("Failed to update warehouse stock.");
    }

    /* ======================================
       OPTIONAL: UPDATE MEDICINE STATUS
    ====================================== */
    $statusUpdate = $conn->prepare("
        UPDATE medicine
        SET status = 
            CASE
                WHEN current_stock <= 0 THEN 'out_of_stock'
                WHEN current_stock <= reorder_point THEN 'low_stock'
                ELSE 'in_stock'
            END
        WHERE id = ?
    ");

    $statusUpdate->bind_param("i", $medicine_id);
    $statusUpdate->execute();

    /* ======================================
       COMMIT TRANSACTION
    ====================================== */
    $conn->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {

    $conn->rollback();

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

exit;
?>
