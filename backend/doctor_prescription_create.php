<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 0);
error_reporting(E_ALL);

function respond(int $code, array $payload): void {
  http_response_code($code);
  echo json_encode($payload);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  respond(405, ["ok" => false, "error" => "Method not allowed. Use POST with JSON."]);
}

if (!isset($_SESSION['user_id'])) {
  respond(401, ["ok" => false, "error" => "Unauthorized"]);
}
if (isset($_SESSION['role']) && $_SESSION['role'] !== 'doctor') {
  respond(403, ["ok" => false, "error" => "Forbidden"]);
}

require_once __DIR__ . '/db/cavitemed_db.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
  respond(500, ["ok" => false, "error" => "Database connection not found"]);
}

$raw = file_get_contents("php://input");
$body = json_decode($raw, true);

if (!is_array($body)) {
  respond(400, ["ok" => false, "error" => "Invalid JSON body", "raw" => $raw]);
}

$action = trim($body['action'] ?? '');
if ($action === '') {
  respond(422, ["ok" => false, "error" => "Missing action"]);
}

$doctor_id  = (int)$_SESSION['user_id'];
$created_by = (int)$_SESSION['user_id'];

function makePrescriptionNumber(): string {
  $date = date('Ymd');
  $rand = random_int(100000, 999999);
  return "RX-$date-$rand";
}

function columnExists(mysqli $conn, string $table, string $column): bool {
  $sql = "SELECT 1
          FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = ?
            AND COLUMN_NAME = ?
          LIMIT 1";
  $st = $conn->prepare($sql);
  if (!$st) return false;
  $st->bind_param("ss", $table, $column);
  $st->execute();
  $row = $st->get_result()->fetch_assoc();
  $st->close();
  return (bool)$row;
}

function tableExists(mysqli $conn, string $table): bool {
  $sql = "SELECT 1
          FROM INFORMATION_SCHEMA.TABLES
          WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = ?
          LIMIT 1";
  $st = $conn->prepare($sql);
  if (!$st) return false;
  $st->bind_param("s", $table);
  $st->execute();
  $row = $st->get_result()->fetch_assoc();
  $st->close();
  return (bool)$row;
}

function loadPrescription(mysqli $conn, int $prescription_id, int $doctor_id): ?array {
  $st = $conn->prepare("SELECT * FROM prescriptions WHERE prescription_id = ? AND doctor_id = ? LIMIT 1");
  if (!$st) return null;
  $st->bind_param("ii", $prescription_id, $doctor_id);
  $st->execute();
  $row = $st->get_result()->fetch_assoc();
  $st->close();
  return $row ?: null;
}

try {

  // ---------------------------------------
  // ACTION: START DRAFT
  // ---------------------------------------
  if ($action === 'start_draft') {
    $patient_id = (int)($body['patient_id'] ?? 0);
    $visit_id   = $body['visit_id'] ?? null;

    $allow_substitution    = (int)($body['allow_substitution'] ?? 1);
    $special_instructions  = trim($body['special_instructions'] ?? '');

    if ($patient_id <= 0) respond(422, ["ok" => false, "error" => "Patient is required"]);

    $chk = $conn->prepare("SELECT patient_id FROM patients WHERE patient_id = ? LIMIT 1");
    if (!$chk) throw new Exception("Prepare failed (patients check): " . $conn->error);
    $chk->bind_param("i", $patient_id);
    $chk->execute();
    $exists = $chk->get_result()->fetch_assoc();
    $chk->close();
    if (!$exists) respond(404, ["ok" => false, "error" => "Patient not found"]);

    $prescription_number = makePrescriptionNumber();
    $hasStatus = columnExists($conn, 'prescriptions', 'status');

    $conn->begin_transaction();

    if ($visit_id === null || $visit_id === '') {
      if ($hasStatus) {
        $sql = "INSERT INTO prescriptions
          (patient_id, visit_id, doctor_id, created_by, prescription_number, allow_substitution, special_instructions, status)
          VALUES (?, NULL, ?, ?, ?, ?, ?, 'draft')";
        $st = $conn->prepare($sql);
        if (!$st) throw new Exception("Prepare failed (insert draft): " . $conn->error);
        $st->bind_param("iiisis", $patient_id, $doctor_id, $created_by, $prescription_number, $allow_substitution, $special_instructions);
      } else {
        $sql = "INSERT INTO prescriptions
          (patient_id, visit_id, doctor_id, created_by, prescription_number, allow_substitution, special_instructions)
          VALUES (?, NULL, ?, ?, ?, ?, ?)";
        $st = $conn->prepare($sql);
        if (!$st) throw new Exception("Prepare failed (insert draft): " . $conn->error);
        $st->bind_param("iiisis", $patient_id, $doctor_id, $created_by, $prescription_number, $allow_substitution, $special_instructions);
      }
    } else {
      $visit_id = (int)$visit_id;
      if ($hasStatus) {
        $sql = "INSERT INTO prescriptions
          (patient_id, visit_id, doctor_id, created_by, prescription_number, allow_substitution, special_instructions, status)
          VALUES (?, ?, ?, ?, ?, ?, ?, 'draft')";
        $st = $conn->prepare($sql);
        if (!$st) throw new Exception("Prepare failed (insert draft): " . $conn->error);
        $st->bind_param("iiiisis", $patient_id, $visit_id, $doctor_id, $created_by, $prescription_number, $allow_substitution, $special_instructions);
      } else {
        $sql = "INSERT INTO prescriptions
          (patient_id, visit_id, doctor_id, created_by, prescription_number, allow_substitution, special_instructions)
          VALUES (?, ?, ?, ?, ?, ?, ?)";
        $st = $conn->prepare($sql);
        if (!$st) throw new Exception("Prepare failed (insert draft): " . $conn->error);
        $st->bind_param("iiiisis", $patient_id, $visit_id, $doctor_id, $created_by, $prescription_number, $allow_substitution, $special_instructions);
      }
    }

    $st->execute();
    $prescription_id = $conn->insert_id;
    $st->close();

    $conn->commit();

    respond(200, [
      "ok" => true,
      "prescription_id" => $prescription_id,
      "prescription_number" => $prescription_number,
      "status" => "draft"
    ]);
  }

  // ---------------------------------------
  // ACTION: ADD ONE ITEM
  // ---------------------------------------
  if ($action === 'add_item') {
    $prescription_id = (int)($body['prescription_id'] ?? 0);
    if ($prescription_id <= 0) respond(422, ["ok" => false, "error" => "prescription_id is required"]);

    $pres = loadPrescription($conn, $prescription_id, $doctor_id);
    if (!$pres) respond(404, ["ok" => false, "error" => "Prescription not found or not yours"]);

    $medicine_id        = (int)($body['medicine_id'] ?? 0);
    $dosage_amount      = (float)($body['dosage_amount'] ?? 0);
    $dosage_unit        = trim($body['dosage_unit'] ?? '');
    $frequency_template = trim($body['frequency_template'] ?? '');
    $duration_amount    = (int)($body['duration_amount'] ?? 0);
    $duration_unit      = trim($body['duration_unit'] ?? '');
    $route_admin        = trim($body['route_admin'] ?? '');
    $item_instructions  = trim($body['item_instructions'] ?? '');

    if ($medicine_id <= 0) respond(422, ["ok" => false, "error" => "Invalid medicine_id"]);
    if ($dosage_amount <= 0) respond(422, ["ok" => false, "error" => "Invalid dosage_amount"]);
    if ($dosage_unit === '' || $frequency_template === '' || $duration_amount <= 0 || $duration_unit === '' || $route_admin === '') {
      respond(422, ["ok" => false, "error" => "Missing required item fields"]);
    }

    $chk = $conn->prepare("SELECT id FROM medicine WHERE id = ? LIMIT 1");
    if (!$chk) throw new Exception("Prepare failed (medicine check): " . $conn->error);
    $chk->bind_param("i", $medicine_id);
    $chk->execute();
    $mexists = $chk->get_result()->fetch_assoc();
    $chk->close();
    if (!$mexists) respond(404, ["ok" => false, "error" => "Medicine not found"]);

    $conn->begin_transaction();

    $itemSql = "INSERT INTO prescription_items
      (prescription_id, medicine_id, dosage_amount, dosage_unit, frequency_template, duration_amount, duration_unit, route_admin, item_instructions)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $st = $conn->prepare($itemSql);
    if (!$st) throw new Exception("Prepare failed (insert item): " . $conn->error);

    $st->bind_param(
      "iidssisss",
      $prescription_id,
      $medicine_id,
      $dosage_amount,
      $dosage_unit,
      $frequency_template,
      $duration_amount,
      $duration_unit,
      $route_admin,
      $item_instructions
    );
    $st->execute();
    $item_id = $conn->insert_id;
    $st->close();

    $conn->commit();

    respond(200, [
      "ok" => true,
      "item_id" => $item_id,
      "prescription_id" => $prescription_id
    ]);
  }

  // ---------------------------------------
  // ACTION: FINALIZE (PDF + patient_documents + update visit status)
  // ---------------------------------------
  if ($action === 'finalize') {
    require_once __DIR__ . '/doctor_pdf_prescription.php';

    $prescription_id = (int)($body['prescription_id'] ?? 0);
    if ($prescription_id <= 0) respond(422, ["ok" => false, "error" => "prescription_id is required"]);

    $pres = loadPrescription($conn, $prescription_id, $doctor_id);
    if (!$pres) respond(404, ["ok" => false, "error" => "Prescription not found or not yours"]);

    $cnt = $conn->prepare("SELECT COUNT(*) AS c FROM prescription_items WHERE prescription_id = ?");
    if (!$cnt) throw new Exception("Prepare failed (count items): " . $conn->error);
    $cnt->bind_param("i", $prescription_id);
    $cnt->execute();
    $cRow = $cnt->get_result()->fetch_assoc();
    $cnt->close();
    if (((int)($cRow['c'] ?? 0)) <= 0) {
      respond(422, ["ok" => false, "error" => "Add at least 1 medicine before finalize"]);
    }

    $conn->begin_transaction();

    $metaSql = "
      SELECT
        pr.prescription_id,
        pr.prescription_number,
        pr.patient_id,
        pr.visit_id,
        pr.created_at,
        pr.allow_substitution,
        pr.special_instructions,
        u.full_name AS doctor_name,
        CONCAT(pt.first_name,' ',pt.last_name) AS patient_name,
        pt.mrn
      FROM prescriptions pr
      JOIN users u ON u.user_id = pr.doctor_id
      JOIN patients pt ON pt.patient_id = pr.patient_id
      WHERE pr.prescription_id = ?
      LIMIT 1
    ";
    $m = $conn->prepare($metaSql);
    if (!$m) throw new Exception("Prepare failed (meta): " . $conn->error);
    $m->bind_param("i", $prescription_id);
    $m->execute();
    $meta = $m->get_result()->fetch_assoc();
    $m->close();
    if (!$meta) throw new Exception("Failed to load prescription meta");

    $pdfItemsSql = "
      SELECT
        pi.dosage_amount, pi.dosage_unit, pi.frequency_template,
        pi.duration_amount, pi.duration_unit, pi.route_admin, pi.item_instructions,
        m.medicine_name, m.category
      FROM prescription_items pi
      JOIN medicine m ON m.id = pi.medicine_id
      WHERE pi.prescription_id = ?
      ORDER BY pi.item_id ASC
    ";
    $p = $conn->prepare($pdfItemsSql);
    if (!$p) throw new Exception("Prepare failed (pdf items): " . $conn->error);
    $p->bind_param("i", $prescription_id);
    $p->execute();
    $pdfRes = $p->get_result();
    $pdfItems = [];
    while ($r = $pdfRes->fetch_assoc()) $pdfItems[] = $r;
    $p->close();

    $patient_id = (int)$meta['patient_id'];
    $visit_id   = $meta['visit_id']; // may be null

// ✅ filesystem path (real folder on disk) — SAVES TO uploads/documents/prescriptions/
$projectRoot = realpath(__DIR__ . "/..");
if ($projectRoot === false) {
  throw new Exception("Project root not found");
}

$absoluteDir = $projectRoot . "/uploads/documents/prescriptions";

// ✅ web path saved in DB (hosting-safe, no /Cavite-Med hardcode)
// ✅ base URL path (hosting-safe): "" if hosted at domain root, "/Cavite-Med" if in subfolder
$baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), "/\\"); 
// If your PHP is inside /Cavite-Med/backend, this becomes "/Cavite-Med/backend"
$baseUrl = preg_replace('#/backend$#', '', $baseUrl); // remove /backend if present

$relativeDir = $baseUrl . "/uploads/documents/prescriptions";    if (!is_dir($absoluteDir)) {
      if (!mkdir($absoluteDir, 0775, true)) {
        throw new Exception("Failed to create directory for pdf");
      }
    }

    $fileName = "prescription_{$prescription_id}_" . date("Ymd_His") . ".pdf";
    $absPath = $absoluteDir . "/" . $fileName;
    $relPath = $relativeDir . "/" . $fileName;

    $sizeBytes = generatePrescriptionPdf([
      "prescription_id" => $meta["prescription_id"],
      "prescription_number" => $meta["prescription_number"],
      "created_at" => $meta["created_at"],
      "patient_id" => $meta["patient_id"],
      "patient_name" => $meta["patient_name"],
      "mrn" => $meta["mrn"],
      "doctor_name" => $meta["doctor_name"],
      "allow_substitution" => (int)$meta["allow_substitution"],
      "special_instructions" => $meta["special_instructions"],
    ], $pdfItems, $absPath);

    if ($sizeBytes <= 0 || !file_exists($absPath)) {
      throw new Exception("PDF generation failed");
    }

    $sizeKb = (int)ceil($sizeBytes / 1024);
    $docTitle = "Prescription {$meta['prescription_number']}";

    if ($visit_id === null || $visit_id === '') {
      $docSql = "
        INSERT INTO patient_documents
          (patient_id, visit_id, document_title, document_type, file_type, file_size_kb, file_path, uploaded_by, prescription_id)
        VALUES
          (?, NULL, ?, 'prescription', 'pdf', ?, ?, ?, ?)
      ";
      $doc = $conn->prepare($docSql);
      if (!$doc) throw new Exception("Prepare failed (patient_documents): " . $conn->error);
      $doc->bind_param("isisii", $patient_id, $docTitle, $sizeKb, $relPath, $created_by, $prescription_id);
    } else {
      $visit_id = (int)$visit_id;
      $docSql = "
        INSERT INTO patient_documents
          (patient_id, visit_id, document_title, document_type, file_type, file_size_kb, file_path, uploaded_by, prescription_id)
        VALUES
          (?, ?, ?, 'prescription', 'pdf', ?, ?, ?, ?)
      ";
      $doc = $conn->prepare($docSql);
      if (!$doc) throw new Exception("Prepare failed (patient_documents): " . $conn->error);
      $doc->bind_param("iisisii", $patient_id, $visit_id, $docTitle, $sizeKb, $relPath, $created_by, $prescription_id);
    }

    $doc->execute();
    $doc->close();

    // mark prescription final if status column exists
    if (columnExists($conn, 'prescriptions', 'status')) {
      $u = $conn->prepare("UPDATE prescriptions SET status = 'final' WHERE prescription_id = ?");
      if ($u) {
        $u->bind_param("i", $prescription_id);
        $u->execute();
        $u->close();
      }
    }

    // ✅ NEW: update visit status to for_dispensing (only if visit_id exists)
    if (!empty($visit_id)) {
      $visitTable = tableExists($conn, 'patient_visits') ? 'patient_visits' : (tableExists($conn, 'patient_visit') ? 'patient_visit' : null);

      if ($visitTable && columnExists($conn, $visitTable, 'status')) {
        $vs = $conn->prepare("UPDATE {$visitTable} SET status = 'for_dispense' WHERE visit_id = ?");
        if ($vs) {
          $vs->bind_param("i", $visit_id);
          $vs->execute();
          $vs->close();
        }
      }
    }

    $conn->commit();

    respond(200, [
      "ok" => true,
      "prescription_id" => $prescription_id,
      "prescription_number" => $meta["prescription_number"],
      "pdf_url" => $relPath,
      "status" => "final",
      "visit_status" => (!empty($visit_id) ? "for_dispensing" : null)
    ]);
  }

  respond(422, ["ok" => false, "error" => "Unknown action: $action"]);

} catch (Throwable $e) {
  if (isset($conn) && $conn instanceof mysqli) {
    @$conn->rollback();
  }
  respond(500, [
    "ok" => false,
    "error" => $e->getMessage(),
    "file" => $e->getFile(),
    "line" => $e->getLine()
  ]);
}
