<?php
require_once __DIR__ . "/db/cavitemed_db.php";

$page  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 25;
$limit = in_array($limit, [25,50,100]) ? $limit : 25;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM medicine
    WHERE is_archived = 0
");

$totalRecords = (int)$result->fetch_assoc()['total'];
$totalPages   = max(1, ceil($totalRecords / $limit));
