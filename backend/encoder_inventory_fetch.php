<?php
require_once __DIR__ . "/db/cavitemed_db.php";

/* ===== Pagination ===== */
$page  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 25;
$limit = in_array($limit, [25,50,100]) ? $limit : 25;
$offset = ($page - 1) * $limit;

/* ===== Sorting ===== */
$sort = $_GET['sort'] ?? 'name-asc';

$orderBy = match ($sort) {
    'name-desc'      => 'm.medicine_name DESC',
    'stock-low'      => 'm.current_stock ASC',
    'stock-high'     => 'm.current_stock DESC',
    'expiry-soon'    => 'm.expiry_date ASC',
    'recently-added' => 'm.created_at DESC',
    default          => 'm.medicine_name ASC'
};

/* ===== Query ===== */
$sql = "
    SELECT 
        m.id,
        m.supplier_id AS supplier_id,
        m.medicine_name,
        m.medicine_type,
        m.unit_of_measure,
        m.category,
        m.funding_source,
        m.status,
        m.current_stock,
        m.reorder_point,
        m.batch_number,
        m.notes,
        m.unit_price,
        m.manufacturing_date,
        m.expiry_date,
        m.barcode,
        s.supplier_name,
        s.contact_person,
        s.contact_number,
        s.email,
        s.supplier_type,
        s.address

    FROM medicine m
    JOIN suppliers s ON m.supplier_id = s.id
    WHERE m.is_archived = 0
    ORDER BY $orderBy
    LIMIT $limit OFFSET $offset
";

$result = $conn->query($sql);
