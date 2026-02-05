<?php
session_start();
header("Content-Type: application/json");
require_once __DIR__ . "/db/cavitemed_db.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

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
        created_by
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "issidssi",
    $data['medicine_id'],
    $data['batch_number'],
    $data['expiry_date'],
    $data['quantity'],
    $data['total_value'],
    $data['disposal_method'],
    $data['disposal_date'],
    $_SESSION['user_id']
);

$success = $stmt->execute();

echo json_encode([
    "success" => $success
]);
