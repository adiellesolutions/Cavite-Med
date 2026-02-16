<?php
session_start();
header("Content-Type: application/json");
require_once __DIR__ . "/db/cavitemed_db.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$id = (int)($data['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid ID"]);
    exit;
}

$conn->begin_transaction();

try {

    /* 1️⃣ Lock disposal record */
    $stmt = $conn->prepare("
        SELECT medicine_id, quantity
        FROM disposal_records
        WHERE id = ?
        FOR UPDATE
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Record not found");
    }

    $record = $result->fetch_assoc();
    $medicineId = (int)$record['medicine_id'];
    $qty        = (int)$record['quantity'];

    /* 2️⃣ Restore stock */
    $stmt = $conn->prepare("
        UPDATE medicine
        SET current_stock = current_stock + ?
        WHERE id = ?
    ");
    $stmt->bind_param("ii", $qty, $medicineId);

    if (!$stmt->execute()) {
        throw new Exception("Failed to restore stock");
    }

    /* 3️⃣ Permanently delete disposal record */
    $stmt = $conn->prepare("
        DELETE FROM disposal_records
        WHERE id = ?
    ");
    $stmt->bind_param("i", $id);

    if (!$stmt->execute()) {
        throw new Exception("Failed to delete disposal record");
    }

    /* 4️⃣ Commit */
    $conn->commit();

    echo json_encode([
        "success" => true
    ]);

} catch (Exception $e) {

    $conn->rollback();

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
