<?php
require_once "../backend/db/cavitemed_db.php";

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';
$role   = $_GET['role'] ?? 'all';

$where = "WHERE deleted_at IS NULL";

if (!empty($search)) {
    $safe = $conn->real_escape_string($search);
    $where .= " AND (
        full_name LIKE '%$safe%' OR
        username  LIKE '%$safe%' OR
        email     LIKE '%$safe%'
    )";
}

if ($status !== 'all') {
    $where .= " AND status = '{$conn->real_escape_string($status)}'";
}

if ($role !== 'all') {
    $where .= " AND role = '{$conn->real_escape_string($role)}'";
}

$limit  = 5;
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$countQuery = $conn->query("SELECT COUNT(*) AS total FROM users $where");
$totalUsers = $countQuery->fetch_assoc()['total'];
$totalPages = ceil($totalUsers / $limit);

$sql = "SELECT * FROM users
        $where
        ORDER BY created_at DESC
        LIMIT $limit OFFSET $offset";

$result = $conn->query($sql);
if (!$result) {
    die("Query failed: " . $conn->error);
}

$start = $offset + 1;
$end   = min($offset + $limit, $totalUsers);
?>
