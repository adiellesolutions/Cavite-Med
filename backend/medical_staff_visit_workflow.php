<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . "/db/cavitemed_db.php";
$mysqli = $mysqli ?? $conn ?? null;

if (!($mysqli instanceof mysqli)) {
  http_response_code(500);
  echo json_encode(["ok"=>false,"error"=>"DB connection failed"]);
  exit;
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'medical_staff') {
  http_response_code(401);
  echo json_encode(["ok"=>false,"error"=>"Unauthorized"]);
  exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data)) {
  http_response_code(400);
  echo json_encode(["ok"=>false,"error"=>"Invalid JSON"]);
  exit;
}

$action   = $data['action'] ?? 'start_visit';
$visit_id = (int)($data['visit_id'] ?? 0);

if ($visit_id <= 0) {
  http_response_code(400);
  echo json_encode(["ok"=>false,"error"=>"Invalid visit_id"]);
  exit;
}

// whitelist statuses
$allowedStatuses = ['waiting','in_progress','for_consultation','for_dispense','completed'];

try {

  if ($action === 'start_visit') {

    // optional safety: start only if waiting
    $stmt = $mysqli->prepare("
      UPDATE patient_visits
      SET status = 'in_progress'
      WHERE visit_id = ?
      LIMIT 1
    ");
    if (!$stmt) throw new Exception($mysqli->error);
    $stmt->bind_param("i", $visit_id);

  } else if ($action === 'set_status') {

    $status = (string)($data['status'] ?? '');
    if (!in_array($status, $allowedStatuses, true)) {
      http_response_code(400);
      echo json_encode(["ok"=>false,"error"=>"Invalid status"]);
      exit;
    }

    $stmt = $mysqli->prepare("
      UPDATE patient_visits
      SET status = ?
      WHERE visit_id = ?
      LIMIT 1
    ");
    if (!$stmt) throw new Exception($mysqli->error);
    $stmt->bind_param("si", $status, $visit_id);

  } else {
    http_response_code(400);
    echo json_encode(["ok"=>false,"error"=>"Invalid action"]);
    exit;
  }

  if (!$stmt->execute()) throw new Exception($stmt->error);

  echo json_encode([
    "ok" => true,
    "action" => $action,
    "visit_id" => $visit_id,
    "affected_rows" => $stmt->affected_rows
  ]);
  $stmt->close();
  exit;

} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(["ok"=>false,"error"=>$e->getMessage()]);
  exit;
}
