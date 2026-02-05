<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(["ok" => false, "error" => "Unauthorized"]);
  exit;
}

require_once __DIR__ . "/db/cavitemed_db.php";

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
if ($q === '' || mb_strlen($q) < 2) {
  echo json_encode(["ok" => true, "results" => []]);
  exit;
}

$limit = 10;

try {
  // Adjust these column names if your table is different.
  $sql = "
    SELECT 
      patient_id,
      mrn,
      first_name,
      last_name,
      phone,
      CONCAT(first_name, ' ', last_name) AS full_name
    FROM patients
    WHERE
      CONCAT(first_name, ' ', last_name) LIKE :q
      OR mrn LIKE :q2
      OR phone LIKE :q2
      OR CAST(patient_id AS CHAR) LIKE :q2
    ORDER BY last_name ASC, first_name ASC
    LIMIT $limit
  ";

  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    ":q"  => "%{$q}%",
    ":q2" => "%{$q}%"
  ]);

  $rows = $stmt->fetchAll();

  echo json_encode(["ok" => true, "results" => $rows]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "Server error"]);
}
