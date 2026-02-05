<?php
require_once "../backend/db/cavitemed_db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = $_POST['supplier_id'];

    $stmt = $conn->prepare("
        UPDATE suppliers SET
            supplier_name = ?,
            supplier_type = ?,
            contact_person = ?,
            contact_number = ?,
            email = ?,
            address = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "ssssssi",
        $_POST['supplier_name'],
        $_POST['supplier_type'],
        $_POST['contact_person'],
        $_POST['contact_number'],
        $_POST['email'],
        $_POST['address'],
        $id
    );

    $stmt->execute();

    header("Location: ../pages/encoder_inventory.php");
    exit;
}
