<?php
require_once "../backend/db/cavitemed_db.php";

if (!isset($_SESSION['health_center_id'])) {
    die("Health center not set.");
}

$health_center_id = (int) $_SESSION['health_center_id'];

/* ==============================
   TOTAL ITEMS (Per Center)
================================ */
$totalItemsQuery = $conn->query("
    SELECT COUNT(*) AS total
    FROM health_center_inventory hci
    INNER JOIN medicine m ON hci.medicine_id = m.id
    WHERE hci.health_center_id = $health_center_id
      AND m.is_archived = 0
      AND hci.current_stock > 0
");

$totalItems = $totalItemsQuery->fetch_assoc()['total'] ?? 0;


/* ==============================
   CRITICAL STOCK
================================ */
$criticalItemsQuery = $conn->query("
    SELECT COUNT(*) AS total
    FROM health_center_inventory hci
    INNER JOIN medicine m ON hci.medicine_id = m.id
    WHERE hci.health_center_id = $health_center_id
      AND m.is_archived = 0
      AND hci.current_stock <= m.reorder_point
      AND hci.current_stock > 0
");

$criticalItems = $criticalItemsQuery->fetch_assoc()['total'] ?? 0;


/* ==============================
   EXPIRING SOON (Next 30 Days)
================================ */
$expiringSoonQuery = $conn->query("
    SELECT COUNT(*) AS total
    FROM health_center_inventory hci
    INNER JOIN medicine m ON hci.medicine_id = m.id
    WHERE hci.health_center_id = $health_center_id
      AND m.is_archived = 0
      AND hci.current_stock > 0
      AND m.expiry_date BETWEEN CURDATE()
          AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
");

$expiringSoon = $expiringSoonQuery->fetch_assoc()['total'] ?? 0;


/* ==============================
   TOTAL INVENTORY VALUE
================================ */
$totalValueQuery = $conn->query("
    SELECT SUM(hci.current_stock * m.unit_price) AS total
    FROM health_center_inventory hci
    INNER JOIN medicine m ON hci.medicine_id = m.id
    WHERE hci.health_center_id = $health_center_id
      AND m.is_archived = 0
");

$totalValue = $totalValueQuery->fetch_assoc()['total'] ?? 0;


/* ==============================
   LAST UPDATED
================================ */
$lastUpdatedQuery = $conn->query("
    SELECT MAX(hci.updated_at) AS last_updated
    FROM health_center_inventory hci
    WHERE hci.health_center_id = $health_center_id
");

$lastUpdated = $lastUpdatedQuery->fetch_assoc()['last_updated'];
