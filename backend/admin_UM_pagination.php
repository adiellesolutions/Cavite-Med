<?php
require_once "../backend/db/cavitemed_db.php";

$limit  = 5;
$page   = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$page   = max(1, $page);
$offset = ($page - 1) * $limit;

$countQuery = $conn->query("
    SELECT COUNT(*) AS total
    FROM users
    $where
");

if (!$countQuery) {
    die("Count query failed: " . $conn->error);
}

$totalUsers = $countQuery->fetch_assoc()['total'];
$totalPages = max(1, ceil($totalUsers / $limit));

$start = $totalUsers ? $offset + 1 : 0;
$end   = min($offset + $limit, $totalUsers);

?>
