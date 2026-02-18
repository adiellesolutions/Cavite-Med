<?php
session_start();
require_once __DIR__ . '/db/cavitemed_db.php';

header('Content-Type: application/json');
ini_set('display_errors', 0);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

/* =========================
   INPUT
========================= */

$id = intval($_POST['distribution_id'] ?? 0);
$new_status = $_POST['status'] ?? null;
$remarks = $_POST['remarks'] ?? '';

$new_center = intval($_POST['health_center_id'] ?? 0);
$new_medicine = intval($_POST['medicine_id'] ?? 0);
$new_quantity = intval($_POST['quantity'] ?? 0);

if (!$id || !$new_status) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

/* =========================
   GET OLD RECORD
========================= */

$stmt = $conn->prepare("
    SELECT health_center_id, medicine_id, quantity, status
    FROM distribution
    WHERE id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Distribution not found']);
    exit;
}

$old = $result->fetch_assoc();

$old_center   = intval($old['health_center_id']);
$old_medicine = intval($old['medicine_id']);
$old_quantity = intval($old['quantity']);
$old_status   = $old['status'];

/* =========================
   Fallback values
========================= */

if (!$new_center)   $new_center   = $old_center;
if (!$new_medicine) $new_medicine = $old_medicine;
if (!$new_quantity) $new_quantity = $old_quantity;

/* =========================
   TRANSACTION START
========================= */

$conn->begin_transaction();

try {

    /*
    =========================================================
    STATUS TRANSITION LOGIC
    =========================================================
    */

    $stockChange = 0;         // how much to change stock
    $targetMedicine = $old_medicine;

    /*
    Determine stock movement based on transition
    */

    if ($old_status !== $new_status) {

        // Any status → distributed (deduct)
        if ($new_status === 'distributed') {
            $stockChange = -$new_quantity;
            $targetMedicine = $new_medicine;
        }

        // distributed → cancelled / returned / pending
        elseif ($old_status === 'distributed' &&
               in_array($new_status, ['cancelled', 'returned', 'pending'])) {

            $stockChange = +$old_quantity;
            $targetMedicine = $old_medicine;
        }
    }

    /*
    If still distributed and editing medicine/quantity
    */

    if ($old_status === 'distributed' && $new_status === 'distributed') {

        if ($old_medicine != $new_medicine) {

            // return old
            $stmt = $conn->prepare("
                UPDATE medicine
                SET current_stock = current_stock + ?
                WHERE id = ?
            ");
            $stmt->bind_param("ii", $old_quantity, $old_medicine);
            $stmt->execute();

            // deduct new
            $stmt = $conn->prepare("
                UPDATE medicine
                SET current_stock = current_stock - ?
                WHERE id = ?
            ");
            $stmt->bind_param("ii", $new_quantity, $new_medicine);
            $stmt->execute();
        }
        else {

            $difference = $new_quantity - $old_quantity;

            if ($difference != 0) {
                $stmt = $conn->prepare("
                    UPDATE medicine
                    SET current_stock = current_stock - ?
                    WHERE id = ?
                ");
                $stmt->bind_param("ii", $difference, $old_medicine);
                $stmt->execute();
            }
        }
    }
    else {

        // Apply stock change if needed
        if ($stockChange != 0) {

            $stmt = $conn->prepare("
                UPDATE medicine
                SET current_stock = current_stock + ?
                WHERE id = ?
            ");
            $stmt->bind_param("ii", $stockChange, $targetMedicine);
            $stmt->execute();
        }
    }

    /*
    =========================================================
    UPDATE DISTRIBUTION
    =========================================================
    */

    $stmt = $conn->prepare("
        UPDATE distribution
        SET health_center_id = ?,
            medicine_id = ?,
            quantity = ?,
            status = ?,
            remarks = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "iiissi",
        $new_center,
        $new_medicine,
        $new_quantity,
        $new_status,
        $remarks,
        $id
    );

    $stmt->execute();

    $conn->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {

    $conn->rollback();

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
