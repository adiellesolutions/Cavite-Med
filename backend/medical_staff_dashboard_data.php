<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . "/db/cavitemed_db.php";

function out($code, $arr) {
  http_response_code($code);
  echo json_encode($arr);
  exit;
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'medical_staff') {
  out(401, ["ok"=>false,"error"=>"Unauthorized"]);
}

if (!isset($conn) || !($conn instanceof mysqli)) {
  out(500, ["ok"=>false,"error"=>"DB connection failed"]);
}

$user_id = (int)$_SESSION['user_id'];

// get staff health_center_id
$st = $conn->prepare("SELECT health_center_id FROM users WHERE user_id=? LIMIT 1");
if (!$st) out(500, ["ok"=>false,"error"=>"Prepare user center failed: ".$conn->error]);

$st->bind_param("i", $user_id);
$st->execute();
$st->bind_result($health_center_id);
$st->fetch();
$st->close();

$health_center_id = $health_center_id ? (int)$health_center_id : 0;

if (!$health_center_id) {
  out(200, [
    "ok"=>true,
    "stats"=>[
      "pending_dispensing"=>0,
      "completed_today"=>0,
      "requiring_attention"=>0,
      "total_patients"=>0,
      "patients_today"=>0,
      "vitals_today"=>0
    ],
    "recent_activity"=>[]
  ]);
}

try {
  // =========================
  // STATS (scope by patient health_center_id)
  // =========================
  $st = $conn->prepare("
    SELECT
      SUM(CASE WHEN v.status='for_dispense' THEN 1 ELSE 0 END) AS pending_dispensing,
      SUM(CASE WHEN v.status='completed' AND DATE(v.visit_datetime)=CURDATE() THEN 1 ELSE 0 END) AS completed_today,
      SUM(CASE WHEN v.priority='high' AND v.status IN ('waiting','in_progress','for_consultation','for_dispense') THEN 1 ELSE 0 END) AS requiring_attention
    FROM patient_visits v
    JOIN patients p ON p.patient_id = v.patient_id
    WHERE p.health_center_id = ?
  ");
  if (!$st) out(500, ["ok"=>false,"error"=>"Prepare stats failed: ".$conn->error]);

  $st->bind_param("i", $health_center_id);
  $st->execute();
  $rs = $st->get_result();
  $statsRow = $rs ? ($rs->fetch_assoc() ?: []) : [];
  $st->close();

  $pending_dispensing  = (int)($statsRow['pending_dispensing'] ?? 0);
  $completed_today     = (int)($statsRow['completed_today'] ?? 0);
  $requiring_attention = (int)($statsRow['requiring_attention'] ?? 0);

  // =========================
  // TOTAL PATIENTS (by patient center)
  // =========================
  $st = $conn->prepare("
    SELECT COUNT(*)
    FROM patients p
    WHERE p.health_center_id = ?
  ");
  if (!$st) out(500, ["ok"=>false,"error"=>"Prepare total patients failed: ".$conn->error]);

  $st->bind_param("i", $health_center_id);
  $st->execute();
  $st->bind_result($total_patients);
  $st->fetch();
  $st->close();
  $total_patients = (int)$total_patients;

  // =========================
  // PATIENTS REGISTERED TODAY (by patient center)
  // =========================
  $st = $conn->prepare("
    SELECT COUNT(*)
    FROM patients p
    WHERE p.health_center_id = ?
      AND DATE(p.created_at) = CURDATE()
  ");
  if (!$st) out(500, ["ok"=>false,"error"=>"Prepare patients today failed: ".$conn->error]);

  $st->bind_param("i", $health_center_id);
  $st->execute();
  $st->bind_result($patients_today);
  $st->fetch();
  $st->close();
  $patients_today = (int)$patients_today;

  // =========================
  // VITALS TODAY (scope by patient center)
  // =========================
  $st = $conn->prepare("
    SELECT COUNT(*)
    FROM patient_vitals pv
    JOIN patients p ON p.patient_id = pv.patient_id
    WHERE p.health_center_id = ?
      AND DATE(pv.recorded_at) = CURDATE()
  ");
  if (!$st) out(500, ["ok"=>false,"error"=>"Prepare vitals today failed: ".$conn->error]);

  $st->bind_param("i", $health_center_id);
  $st->execute();
  $st->bind_result($vitals_today);
  $st->fetch();
  $st->close();
  $vitals_today = (int)$vitals_today;

  // =========================
  // RECENT ACTIVITY (registered + vitals) scoped by patient center
  // =========================
  $recent = [];
  $q = "
    (
      SELECT 
        CONCAT(p.first_name,' ',p.last_name) AS name,
        'registered' AS type,
        p.created_at AS activity_time
      FROM patients p
      WHERE p.health_center_id = ?
      ORDER BY p.created_at DESC
      LIMIT 3
    )
    UNION ALL
    (
      SELECT
        CONCAT(p.first_name,' ',p.last_name) AS name,
        'vitals' AS type,
        pv.recorded_at AS activity_time
      FROM patient_vitals pv
      JOIN patients p ON p.patient_id = pv.patient_id
      WHERE p.health_center_id = ?
      ORDER BY pv.recorded_at DESC
      LIMIT 3
    )
    ORDER BY activity_time DESC
    LIMIT 5
  ";

  $st = $conn->prepare($q);
  if (!$st) out(500, ["ok"=>false,"error"=>"Prepare recent activity failed: ".$conn->error]);

  $st->bind_param("ii", $health_center_id, $health_center_id);
  $st->execute();
  $rs = $st->get_result();
  while ($row = $rs->fetch_assoc()) {
    $recent[] = $row;
  }
  $st->close();

  out(200, [
    "ok"=>true,
    "stats"=>[
      "pending_dispensing"   => $pending_dispensing,
      "completed_today"      => $completed_today,
      "requiring_attention"  => $requiring_attention,
      "total_patients"       => $total_patients,
      "patients_today"       => $patients_today,
      "vitals_today"         => $vitals_today,
    ],
    "recent_activity"=>$recent
  ]);

} catch (Throwable $e) {
  out(500, ["ok"=>false,"error"=>$e->getMessage()]);
}