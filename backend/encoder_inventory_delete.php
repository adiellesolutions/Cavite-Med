<?php
session_start();
require_once "db/cavitemed_db.php";

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit;
}

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    exit;
}

$stmt = $conn->prepare("
    UPDATE medicine 
    SET is_archived = 1 
    WHERE id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

if ($stmt->affected_rows === 0) {
    http_response_code(404);
}

$stmt->close();
$conn->close();
