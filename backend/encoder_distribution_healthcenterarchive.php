<?php
session_start();
require_once __DIR__ . '/db/cavitemed_db.php';

header('Content-Type: application/json');
ini_set('display_errors', 0);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$id = isset($_POST['center_id']) ? intval($_POST['center_id']) : 0;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

/* ===============================
   CHECK IF USED IN DISTRIBUTION
================================ */

$stmt = $conn->prepare("
    SELECT COUNT(*) as total
    FROM distribution
    WHERE health_center_id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row['total'] > 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Cannot archive. Health center is used in distribution records.'
    ]);
    exit;
}

/* ===============================
   ARCHIVE (SOFT DELETE)
================================ */

$stmt = $conn->prepare("
    UPDATE health_centers
    SET is_archived = 1
    WHERE id = ?
");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Archive failed']);
}
