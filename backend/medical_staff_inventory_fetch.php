<?php
require_once __DIR__ . "/db/cavitemed_db.php";

/* =========================================
   SECURITY CHECK
========================================= */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medical_staff') {
    die("Unauthorized access");
}

if (!isset($_SESSION['health_center_id'])) {
    die("Invalid Health Center");
}

$healthCenterId = (int) $_SESSION['health_center_id'];

/* =========================================
   PAGINATION
========================================= */
$page  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 25;

$allowedLimits = [25, 50, 100];
$limit = in_array($limit, $allowedLimits) ? $limit : 25;

$offset = ($page - 1) * $limit;

/* =========================================
   SORTING
========================================= */
$sort = $_GET['sort'] ?? 'name-asc';

$orderBy = match ($sort) {
    'name-desc'      => 'm.medicine_name DESC',
    'stock-low'      => 'current_stock ASC',
    'stock-high'     => 'current_stock DESC',
    'expiry-soon'    => 'm.expiry_date ASC',
    'recently-added' => 'm.created_at DESC',
    default          => 'm.medicine_name ASC'
};

/* =========================================
   MAIN QUERY
========================================= */
$sql = "
    SELECT 
        m.id,
        m.supplier_id,
        m.medicine_name,
        m.medicine_type,
        m.category,
        m.unit_of_measure,
        m.funding_source,
        m.batch_number,
        m.expiry_date,
        m.manufacturing_date,
        m.reorder_point,
        m.unit_price,
        m.notes,
        m.barcode,
        m.status,
        s.supplier_name,
        s.contact_person,
        s.contact_number,
        s.email,
        s.supplier_type,

        hci.current_stock

    FROM health_center_inventory hci
    JOIN medicine m ON hci.medicine_id = m.id
    JOIN suppliers s ON m.supplier_id = s.id

    WHERE hci.health_center_id = ?
      AND m.is_archived = 0
      AND hci.current_stock > 0

    ORDER BY $orderBy
    LIMIT ? OFFSET ?
";


/* =========================================
   PREPARE + EXECUTE
========================================= */
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Query prepare failed: " . $conn->error);
}

$stmt->bind_param("iii", $healthCenterId, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

/* =========================================
   TOTAL RECORDS (FOR PAGINATION)
========================================= */
$countSql = "
    SELECT COUNT(*) AS total
    FROM health_center_inventory hci
    JOIN medicine m ON hci.medicine_id = m.id
    WHERE hci.health_center_id = ?
      AND m.is_archived = 0
      AND hci.current_stock > 0
";


$countStmt = $conn->prepare($countSql);
$countStmt->bind_param("i", $healthCenterId);
$countStmt->execute();
$countResult = $countStmt->get_result();
$totalRecords = $countResult->fetch_assoc()['total'];

$totalPages = ceil($totalRecords / $limit);
