<?php
session_start();
require_once __DIR__ . "/db/cavitemed_db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'encoder') {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$sql = "
    SELECT
        SUM(CASE 
            WHEN expiry_date < CURDATE() THEN 1 
            ELSE 0 
        END) AS total_expired,

        SUM(CASE 
            WHEN expiry_date BETWEEN CURDATE() 
            AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            THEN 1 ELSE 0 
        END) AS expiring_soon
    FROM medicine
    WHERE is_archived = 0
";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode(["error" => "Query failed"]);
    exit;
}

$data = $result->fetch_assoc();

echo json_encode([
    "totalExpired"  => (int)$data['total_expired'],
    "expiringSoon"  => (int)$data['expiring_soon']
]);
