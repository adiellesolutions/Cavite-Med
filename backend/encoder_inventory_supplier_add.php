<?php
require_once "../backend/db/cavitemed_db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

$supplier_id     = $_POST['supplier_id'] ?? '';
$supplier_name   = trim($_POST['supplier_name']);
$supplier_type   = $_POST['supplier_type'];
$contact_person  = $_POST['contact_person'] ?? null;
$contact_number  = $_POST['contact_number'] ?? null;
$email           = $_POST['email'] ?? null;
$address         = $_POST['address'] ?? null;

/* ===============================
   INSERT (ADD NEW)
================================ */
if (empty($supplier_id)) {

    $stmt = $conn->prepare("
        INSERT INTO suppliers
        (supplier_name, supplier_type, contact_person, contact_number, email, address)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "ssssss",
        $supplier_name,
        $supplier_type,
        $contact_person,
        $contact_number,
        $email,
        $address
    );

/* ===============================
   UPDATE (EDIT)
================================ */
} else {

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
        $supplier_name,
        $supplier_type,
        $contact_person,
        $contact_number,
        $email,
        $address,
        $supplier_id
    );
}

if ($stmt->execute()) {
    header("Location: ../pages/encoder_inventory.php");
    exit;
}

die("Database error");
