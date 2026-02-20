<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . "/db/cavitemed_db.php";
$mysqli = $conn ?? null;

function out($code, $arr) {
  http_response_code($code);
  echo json_encode($arr);
  exit;
}

if (!($mysqli instanceof mysqli)) {
  out(500, ["ok"=>false,"error"=>"DB connection failed"]);
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'medical_staff') {
  out(401, ["ok"=>false,"error"=>"Unauthorized"]);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$user_id = (int)$_SESSION['user_id'];

// -------------------------
// Get medical_staff health_center_id (scope)
// -------------------------
$st = $mysqli->prepare("SELECT health_center_id FROM users WHERE user_id=? LIMIT 1");
if (!$st) out(500, ["ok"=>false,"error"=>"Prepare user center failed: ".$mysqli->error]);

$st->bind_param("i", $user_id);
$st->execute();
$st->bind_result($health_center_id);
$st->fetch();
$st->close();

$health_center_id = $health_center_id ? (int)$health_center_id : null;

if (!$health_center_id) {
  out(403, ["ok"=>false,"error"=>"User is not assigned to a health center."]);
}

function freqPerDay($tpl) {
  return match($tpl) {
    'once-daily' => 1,
    'twice-daily' => 2,
    'three-times' => 3,
    'four-times' => 4,
    'every-6h' => 4,
    'every-8h' => 3,
    'every-12h' => 2,
    'bedtime' => 1,
    default => 1,
  };
}
function durationDays($amount, $unit) {
  $amount = (int)$amount;
  return match($unit) {
    'days' => $amount,
    'weeks' => $amount * 7,
    'months' => $amount * 30,
    default => $amount
  };
}

try {

  // LIST
  if ($action === 'list') {
    $status = $_GET['status'] ?? 'all';
    $q = trim($_GET['q'] ?? '');

    $where = "1=1 AND pt.health_center_id = ?";
    $types = "i";
    $params = [$health_center_id];

    if ($status !== 'all') {
      $where .= " AND pr.status = ?";
      $types .= "s";
      $params[] = $status;
    }

    if ($q !== '') {
      $where .= " AND (CONCAT(pt.first_name,' ',pt.last_name) LIKE ? OR pr.prescription_number LIKE ?)";
      $types .= "ss";
      $like = "%{$q}%";
      $params[] = $like;
      $params[] = $like;
    }

    $sql = "
      SELECT pr.prescription_id, pr.prescription_number, pr.status, pr.created_at,
             CONCAT(pt.first_name,' ',pt.last_name) AS patient_name,
             pt.mrn
      FROM prescriptions pr
      JOIN patients pt ON pt.patient_id = pr.patient_id
      WHERE {$where}
      ORDER BY pr.created_at DESC
      LIMIT 50
    ";

    $st = $mysqli->prepare($sql);
    if (!$st) out(500, ["ok"=>false,"error"=>"Prepare failed: ".$mysqli->error]);

    $st->bind_param($types, ...$params);
    $st->execute();
    $res = $st->get_result();

    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;

    out(200, ["ok"=>true,"data"=>$rows]);
  }

  // DETAILS
  if ($action === 'details') {
    $prescription_id = (int)($_GET['prescription_id'] ?? 0);
    if ($prescription_id<=0) out(422, ["ok"=>false,"error"=>"prescription_id required"]);

    $m = $mysqli->prepare("
      SELECT pr.prescription_id, pr.prescription_number, pr.status, pr.created_at,
             pr.special_instructions,
             CONCAT(pt.first_name,' ',pt.last_name) AS patient_name,
             pt.mrn, pt.gender, pt.date_of_birth,
             u.full_name AS doctor_name
      FROM prescriptions pr
      JOIN patients pt ON pt.patient_id = pr.patient_id
      JOIN users u ON u.user_id = pr.doctor_id
      WHERE pr.prescription_id=?
        AND pt.health_center_id = ?
      LIMIT 1
    ");
    if (!$m) out(500, ["ok"=>false,"error"=>"Prepare failed: ".$mysqli->error]);

    $m->bind_param("ii", $prescription_id, $health_center_id);
    $m->execute();
    $meta = $m->get_result()->fetch_assoc();
    $m->close();

    if (!$meta) out(404, ["ok"=>false,"error"=>"Not found"]);

    $p = $mysqli->prepare("
      SELECT pi.item_id, pi.medicine_id,
             m.medicine_name, m.category, m.current_stock,
             pi.dosage_amount, pi.dosage_unit,
             pi.frequency_template, pi.duration_amount, pi.duration_unit,
             pi.route_admin, pi.item_instructions
      FROM prescription_items pi
      JOIN medicine m ON m.id = pi.medicine_id
      WHERE pi.prescription_id=?
      ORDER BY pi.item_id ASC
    ");
    if (!$p) out(500, ["ok"=>false,"error"=>"Prepare failed: ".$mysqli->error]);

    $p->bind_param("i", $prescription_id);
    $p->execute();
    $res = $p->get_result();

    $items = [];
    while ($it = $res->fetch_assoc()) {
      $need = max(1, freqPerDay($it['frequency_template']) * durationDays($it['duration_amount'], $it['duration_unit']));

      $s = $mysqli->prepare("SELECT COALESCE(SUM(dispensed_qty),0) AS dispensed FROM prescription_dispensing WHERE item_id=?");
      $iid = (int)$it['item_id'];
      $s->bind_param("i", $iid);
      $s->execute();
      $sum = $s->get_result()->fetch_assoc();
      $s->close();

      $disp = (int)($sum['dispensed'] ?? 0);

      $it['qty_prescribed'] = $need;
      $it['qty_dispensed'] = $disp;
      $it['qty_remaining'] = max(0, $need - $disp);

      $items[] = $it;
    }

    out(200, ["ok"=>true,"meta"=>$meta,"items"=>$items]);
  }

  // DISPENSE
  if ($action === 'dispense') {
    $body = json_decode(file_get_contents("php://input"), true) ?: [];
    $prescription_id = (int)($body['prescription_id'] ?? 0);
    $item_id = (int)($body['item_id'] ?? 0);
    $qty = (int)($body['qty'] ?? 0);
    $notes = trim($body['notes'] ?? '');

    if ($prescription_id<=0 || $item_id<=0 || $qty<=0) {
      out(422, ["ok"=>false,"error"=>"prescription_id, item_id, qty required"]);
    }

    // ✅ SECURITY: ensure prescription belongs to same health center
    $chk = $mysqli->prepare("
      SELECT pr.prescription_id
      FROM prescriptions pr
      JOIN patients pt ON pt.patient_id = pr.patient_id
      WHERE pr.prescription_id = ?
        AND pt.health_center_id = ?
      LIMIT 1
    ");
    if (!$chk) out(500, ["ok"=>false,"error"=>"Prepare failed: ".$mysqli->error]);

    $chk->bind_param("ii", $prescription_id, $health_center_id);
    $chk->execute();
    $okRow = $chk->get_result()->fetch_assoc();
    $chk->close();

    if (!$okRow) out(404, ["ok"=>false,"error"=>"Prescription not found in your health center"]);

    // load item + stock
    $st = $mysqli->prepare("
      SELECT pi.item_id, pi.prescription_id, pi.medicine_id,
             pi.frequency_template, pi.duration_amount, pi.duration_unit,
             m.current_stock
      FROM prescription_items pi
      JOIN medicine m ON m.id = pi.medicine_id
      WHERE pi.item_id=? AND pi.prescription_id=?
      LIMIT 1
    ");
    if (!$st) out(500, ["ok"=>false,"error"=>"Prepare failed: ".$mysqli->error]);

    $st->bind_param("ii", $item_id, $prescription_id);
    $st->execute();
    $it = $st->get_result()->fetch_assoc();
    $st->close();

    if (!$it) out(404, ["ok"=>false,"error"=>"Item not found"]);

    $need = max(1, freqPerDay($it['frequency_template']) * durationDays($it['duration_amount'], $it['duration_unit']));

    $sumSt = $mysqli->prepare("SELECT COALESCE(SUM(dispensed_qty),0) AS dispensed FROM prescription_dispensing WHERE item_id=?");
    $sumSt->bind_param("i", $item_id);
    $sumSt->execute();
    $disp = (int)($sumSt->get_result()->fetch_assoc()['dispensed'] ?? 0);
    $sumSt->close();

    $remaining = $need - $disp;
    if ($qty > $remaining) out(422, ["ok"=>false,"error"=>"Qty exceeds remaining"]);
    if ($qty > (int)$it['current_stock']) out(422, ["ok"=>false,"error"=>"Not enough stock"]);

    $mysqli->begin_transaction();

    // record dispense
    $ins = $mysqli->prepare("
      INSERT INTO prescription_dispensing (prescription_id, item_id, dispensed_qty, notes, dispensed_by)
      VALUES (?, ?, ?, ?, ?)
    ");
    $ins->bind_param("iiisi", $prescription_id, $item_id, $qty, $notes, $user_id);
    $ins->execute();

    // deduct stock
    $upd = $mysqli->prepare("UPDATE medicine SET current_stock=current_stock-? WHERE id=? AND current_stock>=?");
    $mid = (int)$it['medicine_id'];
    $upd->bind_param("iii", $qty, $mid, $qty);
    $upd->execute();
    if ($upd->affected_rows <= 0) {
      $mysqli->rollback();
      out(409, ["ok"=>false,"error"=>"Stock update failed"]);
    }

    // inventory transaction
    $tx = $mysqli->prepare("
      INSERT INTO inventory_transactions (medicine_id, transaction_type, quantity, remarks, performed_by)
      VALUES (?, 'deduct', ?, 'Dispensed via prescription', ?)
    ");
    $tx->bind_param("iii", $mid, $qty, $user_id);
    $tx->execute();

    // set prescription as dispensed if all items complete
    $check = $mysqli->prepare("SELECT item_id, frequency_template, duration_amount, duration_unit FROM prescription_items WHERE prescription_id=?");
    $check->bind_param("i", $prescription_id);
    $check->execute();
    $r = $check->get_result();

    $allDone = true;
    while ($row = $r->fetch_assoc()) {
      $need2 = max(1, freqPerDay($row['frequency_template']) * durationDays($row['duration_amount'], $row['duration_unit']));

      $ss = $mysqli->prepare("SELECT COALESCE(SUM(dispensed_qty),0) AS dispensed FROM prescription_dispensing WHERE item_id=?");
      $iid = (int)$row['item_id'];
      $ss->bind_param("i", $iid);
      $ss->execute();
      $got = (int)($ss->get_result()->fetch_assoc()['dispensed'] ?? 0);
      $ss->close();

      if ($got < $need2) { $allDone = false; break; }
    }

    if ($allDone) {
      // 1) set prescription as dispensed
      $up = $mysqli->prepare("UPDATE prescriptions SET status='dispensed' WHERE prescription_id=?");
      $up->bind_param("i", $prescription_id);
      $up->execute();
      $up->close();

      // 2) set patient visit as completed (based on prescription.visit_id)
      $vs = $mysqli->prepare("SELECT visit_id FROM prescriptions WHERE prescription_id=? LIMIT 1");
      $vs->bind_param("i", $prescription_id);
      $vs->execute();
      $visitRow = $vs->get_result()->fetch_assoc();
      $vs->close();

      $visit_id = (int)($visitRow['visit_id'] ?? 0);

      if ($visit_id > 0) {
        $uv = $mysqli->prepare("UPDATE patient_visits SET status='completed' WHERE visit_id=?");
        $uv->bind_param("i", $visit_id);
        $uv->execute();
        $uv->close();
      }
    }

    $mysqli->commit();
    out(200, ["ok"=>true, "all_done"=>$allDone]);
  }

  // RECENT (scoped)
  if ($action === 'recent') {
    $resSt = $mysqli->prepare("
      SELECT d.dispensed_at, d.dispensed_qty, p.prescription_number,
             CONCAT(pt.first_name,' ',pt.last_name) AS patient_name,
             m.medicine_name
      FROM prescription_dispensing d
      JOIN prescriptions p ON p.prescription_id=d.prescription_id
      JOIN prescription_items pi ON pi.item_id=d.item_id
      JOIN medicine m ON m.id=pi.medicine_id
      JOIN patients pt ON pt.patient_id=p.patient_id
      WHERE pt.health_center_id = ?
      ORDER BY d.dispensed_at DESC
      LIMIT 10
    ");
    if (!$resSt) out(500, ["ok"=>false,"error"=>"Prepare failed: ".$mysqli->error]);

    $resSt->bind_param("i", $health_center_id);
    $resSt->execute();
    $res = $resSt->get_result();

    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;

    out(200, ["ok"=>true,"data"=>$rows]);
  }

  out(422, ["ok"=>false,"error"=>"Unknown action"]);

} catch (Throwable $e) {
  if ($mysqli instanceof mysqli) @$mysqli->rollback();
  out(500, ["ok"=>false,"error"=>$e->getMessage()]);
}