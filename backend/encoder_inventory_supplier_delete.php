<?php
require_once "../backend/db/cavitemed_db.php";

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

    if ($id <= 0) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid supplier ID"
        ]);
        exit;
    }

    $stmt = $conn->prepare("
        UPDATE suppliers
        SET is_archived = 1
        WHERE id = ?
    ");

    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Supplier archived successfully"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Failed to archive supplier"
        ]);
    }
}
