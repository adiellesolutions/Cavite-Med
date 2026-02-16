<?php
session_start();
header("Content-Type: application/json");
require_once __DIR__ . "/db/cavitemed_db.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["data" => [], "total" => 0]);
    exit;
}

$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = max(1, (int)($_GET['limit'] ?? 10));
$offset = ($page - 1) * $limit;

$search = trim($_GET['search'] ?? "");
$type   = trim($_GET['type'] ?? "");
$date   = trim($_GET['date'] ?? "");

$where = "WHERE is_archived = 1";
$params = [];
$types  = "";

/* ==============================
   SEARCH FILTER
============================== */
if ($search !== "") {
    $where .= " AND (
        medicine_name LIKE ?
        OR barcode LIKE ?
        OR category LIKE ?
        OR batch_number LIKE ?
    )";

    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "ssss";
}

/* ==============================
   TYPE FILTER (medicine_type)
============================== */
if ($type !== "") {
    $where .= " AND medicine_type = ?";
    $params[] = $type;
    $types .= "s";
}

/* ==============================
   DATE FILTER (using updated_at)
============================== */
if ($date !== "") {

    if ($date === "today") {
        $where .= " AND DATE(updated_at) = CURDATE()";
    }

    if ($date === "week") {
        $where .= " AND YEARWEEK(updated_at, 1) = YEARWEEK(CURDATE(), 1)";
    }

    if ($date === "month") {
        $where .= " AND MONTH(updated_at) = MONTH(CURDATE())
                    AND YEAR(updated_at) = YEAR(CURDATE())";
    }

    if ($date === "year") {
        $where .= " AND YEAR(updated_at) = YEAR(CURDATE())";
    }
}

/* ==============================
   TOTAL COUNT + TOTAL VALUE
============================== */
$countSql = "
    SELECT
        COUNT(*) as total,
        COALESCE(SUM(unit_price * current_stock), 0) as total_value
    FROM medicine
    $where
";

$countStmt = $conn->prepare($countSql);

if ($types !== "") {
    $countStmt->bind_param($types, ...$params);
}

$countStmt->execute();
$countData = $countStmt->get_result()->fetch_assoc();

$total = $countData['total'] ?? 0;
$totalValue = $countData['total_value'] ?? 0;

/* ==============================
   FETCH DATA
============================== */
$sql = "
    SELECT
        id,
        barcode,
        medicine_name,
        medicine_type,
        category,
        batch_number,
        expiry_date,
        current_stock,
        unit_price,
        status
    FROM medicine
    $where
    ORDER BY medicine_name ASC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);

if ($types !== "") {
    $bindTypes = $types . "ii";
    $bindParams = array_merge($params, [$limit, $offset]);
    $stmt->bind_param($bindTypes, ...$bindParams);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}

$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode([
    "data" => $data,
    "total" => (int)$total,
    "total_value" => (float)$totalValue
]);
