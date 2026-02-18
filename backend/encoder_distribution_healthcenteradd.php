<?php
session_start();
require_once __DIR__ . '/db/cavitemed_db.php';

header('Content-Type: application/json');
ini_set('display_errors', 0);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$id = $_POST['center_id'] ?? null;

$name   = $_POST['center_name'] ?? '';
$type   = $_POST['center_type'] ?? '';
$person = $_POST['contact_person'] ?? null;
$phone  = $_POST['contact_number'] ?? null;
$addr   = $_POST['address'] ?? null;

if (!$name || !$type) {
    echo json_encode([
        'success' => false,
        'message' => 'Name and type required'
    ]);
    exit;
}

/* =========================
   EDIT MODE
========================= */

if ($id) {

    $stmt = $conn->prepare("
        UPDATE health_centers
        SET center_name = ?,
            center_type = ?,
            contact_person = ?,
            contact_number = ?,
            address = ?
        WHERE id = ?
    ");

    $stmt->bind_param("sssssi",
        $name,
        $type,
        $person,
        $phone,
        $addr,
        $id
    );

    $stmt->execute();

    echo json_encode(['success' => true]);
    exit;
}

/* =========================
   ADD MODE
========================= */

$stmt = $conn->prepare("
    INSERT INTO health_centers
    (center_name, center_type, contact_person, contact_number, address)
    VALUES (?, ?, ?, ?, ?)
");

$stmt->bind_param("sssss",
    $name,
    $type,
    $person,
    $phone,
    $addr
);

$stmt->execute();

echo json_encode(['success' => true]);
