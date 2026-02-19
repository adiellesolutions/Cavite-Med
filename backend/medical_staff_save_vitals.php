<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . "/db/cavitemed_db.php";
$mysqli = $mysqli ?? $conn ?? null;

if (!($mysqli instanceof mysqli)) {
  http_response_code(500);
  echo json_encode(["ok"=>false,"error"=>"DB connection failed"]);
  exit;
}

if (!isset($_SESSION['user_id'])) {
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

$visit_id = (int)($data['visit_id'] ?? 0);
$patient_id = (int)($data['patient_id'] ?? 0);

if ($visit_id <= 0 || $patient_id <= 0) {
  http_response_code(400);
  echo json_encode(["ok"=>false,"error"=>"Missing visit_id or patient_id","visit_id"=>$visit_id,"patient_id"=>$patient_id]);
  exit;
}

$recorded_by = (int)($_SESSION['user_id'] ?? 0);

try {
  $mysqli->begin_transaction();

  // 1) Check visit row exists + get current status
  $chk = $mysqli->prepare("SELECT status FROM patient_visits WHERE visit_id = ? AND patient_id = ? LIMIT 1");
  if (!$chk) throw new Exception("Prepare failed (check visit): ".$mysqli->error);
  $chk->bind_param("ii", $visit_id, $patient_id);
  if (!$chk->execute()) throw new Exception("Execute failed (check visit): ".$chk->error);
  $res = $chk->get_result();
  if ($res->num_rows === 0) {
    throw new Exception("No matching visit found for this visit_id + patient_id");
  }
  $row = $res->fetch_assoc();
  $before_status = $row['status'];
  $chk->close();

  // 2) Save vitals (simple INSERT without duplicate logic for now)
  $stmt = $mysqli->prepare("
    INSERT INTO patient_vitals
      (patient_id, visit_id, bp_systolic, bp_diastolic, heart_rate, temperature,
       spo2, respiratory_rate, blood_glucose, weight, nurse_notes, recorded_by)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
  ");
  if (!$stmt) throw new Exception("Prepare failed (insert vitals): ".$mysqli->error);

  $bp_sys  = isset($data['bp_systolic']) && $data['bp_systolic'] !== '' ? (int)$data['bp_systolic'] : null;
  $bp_dia  = isset($data['bp_diastolic']) && $data['bp_diastolic'] !== '' ? (int)$data['bp_diastolic'] : null;
  $hr      = isset($data['heart_rate']) && $data['heart_rate'] !== '' ? (int)$data['heart_rate'] : null;
  $temp    = isset($data['temperature']) && $data['temperature'] !== '' ? (float)$data['temperature'] : null;
  $spo2    = isset($data['spo2']) && $data['spo2'] !== '' ? (int)$data['spo2'] : null;
  $rr      = isset($data['respiratory_rate']) && $data['respiratory_rate'] !== '' ? (int)$data['respiratory_rate'] : null;
  $bg      = isset($data['blood_glucose']) && $data['blood_glucose'] !== '' ? (int)$data['blood_glucose'] : null;
  $weight  = isset($data['weight']) && $data['weight'] !== '' ? (float)$data['weight'] : null;
  $notes   = (string)($data['nurse_notes'] ?? '');

  // bind as strings to allow NULL safely
  $stmt->bind_param(
    "iisssssssssi",
    $patient_id,
    $visit_id,
    $bp_sys,
    $bp_dia,
    $hr,
    $temp,
    $spo2,
    $rr,
    $bg,
    $weight,
    $notes,
    $recorded_by
  );

  if (!$stmt->execute()) throw new Exception("Vitals insert failed: ".$stmt->error);
  $stmt->close();

  // 3) Update visit status -> for_consultation (NO status filter for debugging)
  $up = $mysqli->prepare("UPDATE patient_visits SET status='for_consultation' WHERE visit_id=? AND patient_id=? LIMIT 1");
  if (!$up) throw new Exception("Prepare failed (update status): ".$mysqli->error);
  $up->bind_param("ii", $visit_id, $patient_id);

  if (!$up->execute()) throw new Exception("Status update failed: ".$up->error);
  $affected = $up->affected_rows;
  $up->close();

  // 4) Read status after
  $chk2 = $mysqli->prepare("SELECT status FROM patient_visits WHERE visit_id=? AND patient_id=? LIMIT 1");
  $chk2->bind_param("ii", $visit_id, $patient_id);
  $chk2->execute();
  $after = $chk2->get_result()->fetch_assoc()['status'] ?? null;
  $chk2->close();

  $mysqli->commit();

  echo json_encode([
    "ok" => true,
    "visit_id" => $visit_id,
    "patient_id" => $patient_id,
    "before_status" => $before_status,
    "after_status" => $after,
    "status_update_affected_rows" => $affected
  ]);
  exit;

} catch (Exception $e) {
  $mysqli->rollback();
  http_response_code(500);
  echo json_encode(["ok"=>false,"error"=>$e->getMessage()]);
  exit;
}
