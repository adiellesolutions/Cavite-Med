<?php
require_once "../backend/db/cavitemed_db.php";

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';
$role   = $_GET['role'] ?? 'all';

$where = "WHERE u.deleted_at IS NULL";

/* ===== SEARCH ===== */
if (!empty($search)) {
    $safe = $conn->real_escape_string($search);
    $where .= " AND (
        u.full_name LIKE '%$safe%' OR
        u.username  LIKE '%$safe%' OR
        u.email     LIKE '%$safe%'
    )";
}

/* ===== STATUS FILTER ===== */
if ($status !== 'all') {
    $safeStatus = $conn->real_escape_string($status);
    $where .= " AND u.status = '$safeStatus'";
}

/* ===== ROLE FILTER ===== */
if ($role !== 'all') {
    $safeRole = $conn->real_escape_string($role);
    $where .= " AND u.role = '$safeRole'";
}

/* ===== PAGINATION ===== */
$limit  = 5;
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

/* ===== COUNT TOTAL ===== */
$countSql = "
    SELECT COUNT(*) AS total
    FROM users u
    $where
";

$countQuery = $conn->query($countSql);
if (!$countQuery) {
    die("Count query failed: " . $conn->error);
}

$totalUsers = $countQuery->fetch_assoc()['total'];
$totalPages = max(1, ceil($totalUsers / $limit));

/* Prevent page overflow */
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $limit;
}

/* ===== MAIN QUERY ===== */
$sql = "
    SELECT 
        u.*,
        hc.center_name
    FROM users u
    LEFT JOIN health_centers hc 
        ON u.health_center_id = hc.id
    $where
    ORDER BY u.created_at DESC
    LIMIT $limit OFFSET $offset
";

$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}

$start = $totalUsers ? $offset + 1 : 0;
$end   = min($offset + $limit, $totalUsers);
?>
