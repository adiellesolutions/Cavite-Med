<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . "/db/cavitemed_db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$limit = 200;

/* ======================================================
   COMBINED AUDIT QUERY
====================================================== */
$sql = "
(
    SELECT 
        u.updated_at AS log_time,
        'User Updated' AS action,
        'User Management' AS category,
        u.full_name AS performed_by,
        CONCAT('User account updated: ', u.username) AS description,
        'medium' AS severity
    FROM users u
    WHERE u.updated_at IS NOT NULL
)

UNION ALL

(
    SELECT
        m.created_at AS log_time,
        'Medicine Added' AS action,
        'Inventory' AS category,
        creator.full_name AS performed_by,
        CONCAT('Added medicine: ', m.medicine_name) AS description,
        'info' AS severity
    FROM medicine m
    JOIN users creator ON m.created_by = creator.user_id
)

UNION ALL

(
    SELECT
        it.created_at AS log_time,
        CONCAT('Inventory ', it.transaction_type) AS action,
        'Inventory Transaction' AS category,
        u.full_name AS performed_by,
        CONCAT('Medicine ID ', it.medicine_id, ' | Qty: ', it.quantity) AS description,
        'medium' AS severity
    FROM inventory_transactions it
    JOIN users u ON it.performed_by = u.user_id
)

UNION ALL

(
    SELECT
        d.created_at AS log_time,
        CONCAT('Distribution ', d.status) AS action,
        'Distribution' AS category,
        u.full_name AS performed_by,
        CONCAT('Distributed Medicine ID ', d.medicine_id, 
               ' to Center ID ', d.health_center_id,
               ' | Qty: ', d.quantity) AS description,
        IF(d.status = 'cancelled','high','info') AS severity
    FROM distribution d
    JOIN users u ON d.created_by = u.user_id
)

UNION ALL

(
    SELECT
        sr.created_at AS log_time,
        'Stock Returned' AS action,
        'Returns' AS category,
        u.full_name AS performed_by,
        CONCAT('Returned Medicine ID ', sr.medicine_id,
               ' | Qty: ', sr.quantity,
               ' | Reason: ', sr.reason) AS description,
        'medium' AS severity
    FROM stock_returns sr
    JOIN users u ON sr.returned_by = u.user_id
)

UNION ALL

(
    SELECT
        dr.created_at AS log_time,
        'Medicine Disposed' AS action,
        'Disposal' AS category,
        u.full_name AS performed_by,
        CONCAT('Disposed Medicine ID ', dr.medicine_id,
               ' | Qty: ', dr.quantity,
               ' | Method: ', dr.disposal_method) AS description,
        'high' AS severity
    FROM disposal_records dr
    JOIN users u ON dr.created_by = u.user_id
)

UNION ALL

(
    SELECT
        hc.created_at AS log_time,
        'Health Center Created' AS action,
        'System' AS category,
        'System' AS performed_by,
        CONCAT('New health center added: ', hc.center_name) AS description,
        'info' AS severity
    FROM health_centers hc
)

UNION ALL

(
    SELECT
        NOW() AS log_time,
        'Supplier Registered' AS action,
        'System' AS category,
        'System' AS performed_by,
        CONCAT('Supplier added: ', s.supplier_name) AS description,
        'info' AS severity
    FROM suppliers s
)

ORDER BY log_time DESC
LIMIT $limit
";

$result = $conn->query($sql);

$logs = [];

while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}

echo json_encode([
    'success' => true,
    'logs' => $logs
]);
exit;
