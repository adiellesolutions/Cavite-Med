<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(["ok" => false, "error" => "Unauthorized"]);
  exit;
}

require_once __DIR__ . "/db/cavitemed_db.php";

// use mysqli connection
$mysqli = $mysqli ?? $conn ?? null;
if (!($mysqli instanceof mysqli)) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "DB connection failed"]);
  exit;
}

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
if ($q === '' || mb_strlen($q) < 2) {
  echo json_encode(["ok" => true, "results" => []]);
  exit;
}

$limit = 10;
$like = "%{$q}%";

try {
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
      CONCAT(first_name, ' ', last_name) LIKE ?
      OR mrn LIKE ?
      OR phone LIKE ?
      OR CAST(patient_id AS CHAR) LIKE ?
    ORDER BY last_name ASC, first_name ASC
    LIMIT $limit
  ";

  $stmt = $mysqli->prepare($sql);
  if (!$stmt) throw new Exception("Prepare failed: " . $mysqli->error);

  $stmt->bind_param("ssss", $like, $like, $like, $like);

  if (!$stmt->execute()) throw new Exception("Execute failed: " . $stmt->error);

  $result = $stmt->get_result();
  $rows = [];
  while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
  }

  $stmt->close();
  echo json_encode(["ok" => true, "results" => $rows]);
  exit;

} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => $e->getMessage()]);
  exit;
}
