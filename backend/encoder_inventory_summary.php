<?php
require_once "../backend/db/cavitemed_db.php";

/* ===== Inventory Summary ===== */

// Total items
$totalItems = $conn->query("
    SELECT COUNT(*) AS total
    FROM medicine
    WHERE is_archived = 0
")->fetch_assoc()['total'];

// Critical stock
$criticalItems = $conn->query("
    SELECT COUNT(*) AS total
    FROM medicine
    WHERE is_archived = 0
      AND current_stock <= reorder_point
")->fetch_assoc()['total'];

// Expiring soon (next 30 days)
$expiringSoon = $conn->query("
    SELECT COUNT(*) AS total
    FROM medicine
    WHERE is_archived = 0
      AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
")->fetch_assoc()['total'];

// Total inventory value
$totalValue = $conn->query("
    SELECT SUM(current_stock * unit_price) AS total
    FROM medicine
    WHERE is_archived = 0
")->fetch_assoc()['total'] ?? 0;

// Last updated
$lastUpdated = $conn->query("
    SELECT MAX(updated_at) AS last_updated
    FROM medicine
")->fetch_assoc()['last_updated'];
