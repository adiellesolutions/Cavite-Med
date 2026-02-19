<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . "/db/cavitemed_db.php";

/* =========================
   Check login
========================= */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medical_staff') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$health_center_id = $_SESSION['health_center_id'] ?? null;

if (!$health_center_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid health center']);
    exit;
}

$barcode = trim($_POST['barcode'] ?? '');

if ($barcode === '') {
    echo json_encode(['success' => false, 'message' => 'Empty barcode']);
    exit;
}

/* =========================
   START TRANSACTION
========================= */
$conn->begin_transaction();

try {

    /* ======================================
       1️⃣ Get ONE pending distribution record
    ====================================== */
    $selectStmt = $conn->prepare("
        SELECT d.id, d.medicine_id, d.quantity
        FROM distribution d
        INNER JOIN medicine m ON d.medicine_id = m.id
        WHERE d.health_center_id = ?
          AND d.status = 'pending'
          AND m.barcode = ?
        ORDER BY d.created_at ASC
        LIMIT 1
    ");

    $selectStmt->bind_param("is", $health_center_id, $barcode);
    $selectStmt->execute();
    $result = $selectStmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("No pending record found for this barcode");
    }

    $distribution = $result->fetch_assoc();

    $distribution_id = $distribution['id'];
    $medicine_id     = $distribution['medicine_id'];
    $quantity        = $distribution['quantity'];

    /* ======================================
       2️⃣ Update distribution status
    ====================================== */
    $updateStmt = $conn->prepare("
        UPDATE distribution
        SET status = 'distributed'
        WHERE id = ?
    ");

    $updateStmt->bind_param("i", $distribution_id);
    $updateStmt->execute();

    if ($updateStmt->affected_rows === 0) {
        throw new Exception("Failed to update distribution");
    }

    /* ======================================
       3️⃣ Insert or Update health center stock
    ====================================== */
    $inventoryStmt = $conn->prepare("
        INSERT INTO health_center_inventory
        (health_center_id, medicine_id, current_stock)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE
            current_stock = current_stock + VALUES(current_stock)
    ");

    $inventoryStmt->bind_param("iii", $health_center_id, $medicine_id, $quantity);
    $inventoryStmt->execute();

    /* ======================================
       COMMIT
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
