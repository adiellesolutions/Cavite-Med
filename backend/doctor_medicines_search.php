<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(["ok" => false, "error" => "Unauthorized"]);
  exit;
}

require_once __DIR__ . "/db/cavitemed_db.php";

$q = trim($_GET['q'] ?? '');

if (strlen($q) < 2) {
  echo json_encode(["ok" => true, "medicines" => []]);
  exit;
}

$limit = 20;

$sql = "
  SELECT id, barcode, medicine_name, category, unit_of_measure, current_stock, status
  FROM medicine
  WHERE is_archived = 0
    AND (
      medicine_name LIKE CONCAT('%', ?, '%') OR
      category LIKE CONCAT('%', ?, '%') OR
      barcode LIKE CONCAT('%', ?, '%')
    )
  ORDER BY medicine_name ASC
  LIMIT $limit
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $q, $q, $q);
$stmt->execute();
$res = $stmt->get_result();

$meds = [];
while ($row = $res->fetch_assoc()) {
  $meds[] = [
    "id" => (int)$row["id"],
    "barcode" => $row["barcode"],
    "medicine_name" => $row["medicine_name"],
    "category" => $row["category"],
    "unit_of_measure" => $row["unit_of_measure"],
    "current_stock" => (int)$row["current_stock"],
    "status" => $row["status"],
  ];
}

echo json_encode(["ok" => true, "medicines" => $meds]);
