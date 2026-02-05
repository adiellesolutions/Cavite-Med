<?php
require_once "../backend/db/cavitemed_db.php";


$totalUsersQuery = $conn->query("
    SELECT COUNT(*) AS total 
    FROM users 
    WHERE deleted_at IS NULL
");
$totalUsers = $totalUsersQuery->fetch_assoc()['total'];

$activeUsersQuery = $conn->query("
    SELECT COUNT(*) AS active 
    FROM users 
    WHERE status = 'active' AND deleted_at IS NULL
");
$activeUsers = $activeUsersQuery->fetch_assoc()['active'];
?>
