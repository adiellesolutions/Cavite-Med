<?php
session_start();
require_once "db/cavitemed_db.php";

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit;
}

$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) exit;

$stmt = $conn->prepare("DELETE FROM medicine WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
