<?php
// backend/get_assigned_health_center.php

if (!isset($_SESSION)) {
    session_start();
}

require_once __DIR__ . "/db/cavitemed_db.php";

$assigned_health_center_name = "";

if (isset($_SESSION['user_id'])) {

    $stmt = $conn->prepare("
        SELECT hc.center_name
        FROM users u
        LEFT JOIN health_centers hc
            ON u.health_center_id = hc.id
        WHERE u.user_id = ?
        LIMIT 1
    ");

    if ($stmt) {
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $stmt->bind_result($assigned_health_center_name);
        $stmt->fetch();
        $stmt->close();
    }
}