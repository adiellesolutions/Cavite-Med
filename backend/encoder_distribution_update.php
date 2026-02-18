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
   SAFE INPUT
========================= */

$id = isset($_POST['distribution_id']) ? intval($_POST['distribution_id']) : 0;
$new_status = $_POST['status'] ?? null;
$remarks = $_POST['remarks'] ?? '';

$new_center = isset($_POST['health_center_id']) ? intval($_POST['health_center_id']) : null;
$new_medicine = isset($_POST['medicine_id']) ? intval($_POST['medicine_id']) : null;
$new_quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : null;

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
   USE OLD VALUES IF NOT SENT
========================= */

if (!$new_center)   $new_center   = $old_center;
if (!$new_medicine) $new_medicine = $old_medicine;
if (!$new_quantity) $new_quantity = $old_quantity;

/* =========================
   BEGIN TRANSACTION
========================= */

$conn->begin_transaction();

try {

    /*
    =========================================================
    STOCK LOGIC
    =========================================================

    We must compare OLD vs NEW status carefully.
    */

    // CASE 1: distributed → cancelled
    if ($old_status === 'distributed' && $new_status === 'cancelled') {

        // return stock
        $stmt = $conn->prepare("
            UPDATE medicine
            SET current_stock = current_stock + ?
            WHERE id = ?
        ");
        $stmt->bind_param("ii", $old_quantity, $old_medicine);
        $stmt->execute();
    }

    // CASE 2: cancelled → distributed
    elseif ($old_status === 'cancelled' && $new_status === 'distributed') {

        // deduct stock
        $stmt = $conn->prepare("
            UPDATE medicine
            SET current_stock = current_stock - ?
            WHERE id = ?
        ");
        $stmt->bind_param("ii", $old_quantity, $old_medicine);
        $stmt->execute();
    }

    // CASE 3: distributed → distributed (medicine or quantity changed)
    elseif ($old_status === 'distributed' && $new_status === 'distributed') {

        // If medicine changed
        if ($old_medicine != $new_medicine) {

            // return old stock
            $stmt = $conn->prepare("
                UPDATE medicine
                SET current_stock = current_stock + ?
                WHERE id = ?
            ");
            $stmt->bind_param("ii", $old_quantity, $old_medicine);
            $stmt->execute();

            // deduct new stock
            $stmt = $conn->prepare("
                UPDATE medicine
                SET current_stock = current_stock - ?
                WHERE id = ?
            ");
            $stmt->bind_param("ii", $new_quantity, $new_medicine);
            $stmt->execute();
        }
        else {
            // same medicine → adjust difference only
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

    /*
    =========================================================
    UPDATE DISTRIBUTION RECORD
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
