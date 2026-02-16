<?php
require_once "../backend/db/cavitemed_db.php";

$suppliersResult = $conn->query("
    SELECT
        id,
        supplier_name,
        supplier_type,
        contact_person,
        contact_number,
        email,
        address
    FROM suppliers
    WHERE is_archived = 0
    ORDER BY supplier_name ASC
");
?>
