<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

ob_start();

function json_out($arr, $code = 200) {
  http_response_code($code);
  $out = ob_get_clean();
  if ($out) { /* ignore */ }
  echo json_encode($arr);
  exit;
}

if (!isset($_SESSION['user_id'])) {
  json_out(["ok" => false, "error" => "Unauthorized"], 401);
}

require_once __DIR__ . "/db/cavitemed_db.php";
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
if (!is_array($data)) {
  json_out(["ok" => false, "error" => "Invalid JSON body"], 400);
}

$patient_id = intval($data['patient_id'] ?? 0);
$template_type = trim($data['template_type'] ?? '');
$diagnosis = trim($data['diagnosis'] ?? '');
$restriction_level_in = trim($data['restriction_level'] ?? '');
$leave_from = trim($data['leave_from'] ?? '');
$leave_to = trim($data['leave_to'] ?? '');
$additional_instructions = trim($data['additional_instructions'] ?? '');
$follow_up_date = $data['follow_up_date'] ?? null;
$include_digital_stamp = intval($data['include_digital_stamp'] ?? 1);
$include_qr_code = intval($data['include_qr_code'] ?? 1);
$visit_id = isset($data['visit_id']) ? intval($data['visit_id']) : null;

if ($patient_id <= 0) json_out(["ok" => false, "error" => "patient_id is required"], 400);
if ($template_type === '') json_out(["ok" => false, "error" => "template_type is required"], 400);
if ($diagnosis === '') json_out(["ok" => false, "error" => "diagnosis is required"], 400);
if ($leave_from === '' || $leave_to === '') json_out(["ok" => false, "error" => "leave_from and leave_to are required"], 400);

$restriction_map = [
  "complete-rest"   => "complete_rest",
  "modified-duties" => "modified_duties",
  "light-duty"      => "light_duty",
  "no-restriction"  => "no_restriction",

  "complete_rest"   => "complete_rest",
  "modified_duties" => "modified_duties",
  "light_duty"      => "light_duty",
  "no_restriction"  => "no_restriction",
];

$restriction_level = $restriction_map[$restriction_level_in] ?? null;
if (!$restriction_level) {
  json_out(["ok" => false, "error" => "Invalid restriction_level"], 400);
}

$allowed_templates = [
  "medical_leave",
  "fitness_certificate",
  "travel_clearance",
  "sports_participation",
  "other"
];
if (!in_array($template_type, $allowed_templates, true)) {
  json_out(["ok" => false, "error" => "Invalid template_type"], 400);
}

$doctor_id = intval($_SESSION['user_id']);
$created_by = intval($_SESSION['user_id']);

if ($follow_up_date === "") $follow_up_date = null;

$conn->begin_transaction();

try {
  // Generate certificate number
  $year = date("Y");
  $rand = str_pad((string)random_int(1, 99999), 5, "0", STR_PAD_LEFT);
  $certificate_number = "MC-$year-$rand";

  // Insert into medical_certificates
  $sql = "INSERT INTO medical_certificates
    (patient_id, visit_id, doctor_id, created_by, certificate_number, template_type, diagnosis, restriction_level,
     leave_from, leave_to, additional_instructions, follow_up_date, include_digital_stamp, include_qr_code)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

  $stmt = $conn->prepare($sql);
  if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);

  $visit_id_param = $visit_id ?: null;

  $stmt->bind_param(
    "iiiissssssssii",
    $patient_id,
    $visit_id_param,
    $doctor_id,
    $created_by,
    $certificate_number,
    $template_type,
    $diagnosis,
    $restriction_level,
    $leave_from,
    $leave_to,
    $additional_instructions,
    $follow_up_date,
    $include_digital_stamp,
    $include_qr_code
  );

  if (!$stmt->execute()) {
    throw new Exception("Insert medical_certificates failed: " . $stmt->error);
  }

  $certificate_id = $conn->insert_id;

  // Fetch patient info
  $pstmt = $conn->prepare("SELECT mrn, first_name, last_name, date_of_birth, gender FROM patients WHERE patient_id=?");
  if (!$pstmt) throw new Exception("Prepare patient fetch failed: " . $conn->error);

  $pstmt->bind_param("i", $patient_id);
  $pstmt->execute();
  $pres = $pstmt->get_result();
  $patient = $pres->fetch_assoc();
  if (!$patient) throw new Exception("Patient not found");

  // ---- PDF Data ----
  $issue_date = date("F d, Y");

  $patient_name = htmlspecialchars(trim(($patient['first_name'] ?? '') . " " . ($patient['last_name'] ?? '')));
  $patient_mrn  = htmlspecialchars($patient['mrn'] ?? '');
  $patient_dob  = htmlspecialchars($patient['date_of_birth'] ?? '');
  $patient_gender = htmlspecialchars($patient['gender'] ?? '');

  $diag_safe = htmlspecialchars($diagnosis);

  // Keep ONE PAGE: trim notes (adjust if you want)
  $notes_trimmed = mb_substr($additional_instructions, 0, 220);
  $notes_safe = nl2br(htmlspecialchars($notes_trimmed));

  $follow_up_text = ($follow_up_date) ? date("F d, Y", strtotime($follow_up_date)) : "";

  // Restriction → readable text
  $restriction_text_map = [
    "complete_rest"   => "complete rest",
    "modified_duties" => "modified duties only",
    "light_duty"      => "light duty only",
    "no_restriction"  => "no restrictions",
  ];
  $restriction_text = $restriction_text_map[$restriction_level] ?? "no restrictions";

  // Restriction → fit/unfit label
  $restriction_to_fit = [
    "complete_rest"   => "UNFIT",
    "modified_duties" => "FIT (WITH RESTRICTIONS)",
    "light_duty"      => "FIT (LIGHT DUTY)",
    "no_restriction"  => "FIT",
  ];
  $fit_text = $restriction_to_fit[$restriction_level] ?? "FIT";

  // Template titles + “statement blocks”
  $template_titles = [
    "medical_leave"         => "MEDICAL CERTIFICATE (LEAVE)",
    "fitness_certificate"   => "FITNESS CERTIFICATE",
    "travel_clearance"      => "TRAVEL CLEARANCE",
    "sports_participation"  => "SPORTS PARTICIPATION CLEARANCE",
    "other"                 => "MEDICAL CERTIFICATE",
  ];
  $doc_title = $template_titles[$template_type] ?? "MEDICAL CERTIFICATE";

  // Stamp/QR placeholders (still simple, you can replace with actual images later)
  $stamp_html = $include_digital_stamp ? "<div class='stamp'>OFFICIAL STAMP</div>" : "";
  $qr_html    = $include_qr_code ? "<div class='qr'>QR CODE</div>" : "";

  $doctor_name = "Dr. " . htmlspecialchars($_SESSION['name'] ?? 'Doctor');

  // ---- Build DIFFERENT CONTENT per template ----
  $main_statement = "";
  $period_label = "";
  $period_line = "";

  switch ($template_type) {
    case "medical_leave":
        $period_label = "Leave Period";
        $dates_subtitle = "Leave Period Coverage";
    
      $main_statement = "
        <p class='p'>
          This is to certify that the above-named patient was examined and evaluated, and was found to be suffering from:
        </p>
      ";
      $period_line = "
        <div class='pill-row'>
          <div class='pill'><span class='pill-label'>status</span><span class='pill-value'>{$fit_text}</span></div>
          <div class='pill'><span class='pill-label'>restriction</span><span class='pill-value'>{$restriction_text}</span></div>
        </div>
        <p class='p'>
          The patient is advised to take medical leave from <b>{$leave_from}</b> to <b>{$leave_to}</b>.
        </p>
        <p class='p'>
          This certificate is issued to support the patient's medical leave request.
        </p>
      ";
      break;

      case "fitness_certificate":
        $period_label = "Date Range";
        $dates_subtitle = "Who has been examined/cleared on the following dates.";
      $main_statement = "
        <p class='p'>
          This is to certify that the above-named patient was examined and assessed for fitness to return to work/school.
        </p>
      ";
      $period_line = "
        <div class='pill-row'>
          <div class='pill'><span class='pill-label'>fitness</span><span class='pill-value'>{$fit_text}</span></div>
          <div class='pill'><span class='pill-label'>restriction</span><span class='pill-value'>{$restriction_text}</span></div>
        </div>
        <p class='p'>
          In my professional opinion, the patient is <b>{$fit_text}</b> during the period <b>{$leave_from}</b> to <b>{$leave_to}</b>.
        </p>
        <p class='p'>
          This certificate is issued to certify fitness to return to work/school.
        </p>
      ";
      break;

      case "travel_clearance":
        $period_label = "Date Range";
        $dates_subtitle = "Who has been examined/cleared on the following dates.";
      $main_statement = "
        <p class='p'>
          This is to certify that the above-named patient was examined and is being assessed for travel clearance.
        </p>
      ";
      $period_line = "
        <div class='pill-row'>
          <div class='pill'><span class='pill-label'>clearance</span><span class='pill-value'>{$fit_text}</span></div>
          <div class='pill'><span class='pill-label'>advice</span><span class='pill-value'>{$restriction_text}</span></div>
        </div>
        <p class='p'>
          The patient is medically cleared for travel for the period <b>{$leave_from}</b> to <b>{$leave_to}</b>,
          provided the above advice/restrictions are followed.
        </p>
        <p class='p'>
          This certificate is issued for travel clearance purposes.
        </p>
      ";
      break;

      case "sports_participation":
        $period_label = "Date Range";
        $dates_subtitle = "Who has been examined/cleared on the following dates.";
      $main_statement = "
        <p class='p'>
          This is to certify that the above-named patient was examined and assessed for sports participation clearance.
        </p>
      ";
      $period_line = "
        <div class='pill-row'>
          <div class='pill'><span class='pill-label'>clearance</span><span class='pill-value'>{$fit_text}</span></div>
          <div class='pill'><span class='pill-label'>restriction</span><span class='pill-value'>{$restriction_text}</span></div>
        </div>
        <p class='p'>
          The patient is <b>{$fit_text}</b> for sports participation from <b>{$leave_from}</b> to <b>{$leave_to}</b>,
          subject to the stated restrictions/advice.
        </p>
        <p class='p'>
          This certificate is issued for sports participation clearance purposes.
        </p>
      ";
      break;

    default: // other
    $period_label = "Date Range";
    $dates_subtitle = "Covered Date Range";
     $main_statement = "
        <p class='p'>
          This is to certify that the above-named patient was examined by me and was found to be suffering from:
        </p>
      ";
      $period_line = "
        <div class='pill-row'>
          <div class='pill'><span class='pill-label'>status</span><span class='pill-value'>{$fit_text}</span></div>
          <div class='pill'><span class='pill-label'>restriction</span><span class='pill-value'>{$restriction_text}</span></div>
        </div>
        <p class='p'>
          This certificate is issued for medical purposes only and is valid from <b>{$leave_from}</b> to <b>{$leave_to}</b>.
        </p>
      ";
      break;
  }

  // Follow up line (only if provided)
  $follow_up_block = $follow_up_text
    ? "<div class='follow'><span class='tag'>follow-up</span> Recommended on <b>{$follow_up_text}</b>.</div>"
    : "";

  // ---- Nicer HTML Layout (One Page) ----
  $html = "
<!doctype html>
<html>
<head>
<meta charset='utf-8'>
<style>
  @page { margin: 14mm 14mm 18mm 14mm; }
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11.5px; color:#0f172a; }
  * { box-sizing: border-box; }

  .sheet { border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px 14px; min-height: 250mm; position: relative; overflow: hidden; }

  .wm {
    position: fixed;
    top: 43%;
    left: 0; right: 0;
    text-align: center;
    font-size: 54px;
    color: #f1f5f9;
    transform: rotate(-18deg);
    z-index: -1;
    letter-spacing: 1px;
  }

  .topbar {
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 8px;
    margin-bottom: 10px;
  }
  .clinic { font-size: 14px; font-weight: 800; letter-spacing: 0.6px; }
  .subclinic { font-size: 10.5px; color:#475569; margin-top: 2px; }

  .titlewrap { text-align: center; margin-top: 8px; }
  .title { font-size: 18px; font-weight: 900; margin: 0; letter-spacing: 0.7px; }
  .meta { margin-top: 6px; color:#475569; font-size: 10.5px; }
  .meta span { display: inline-block; margin: 0 8px; }

  .grid { margin-top: 10px; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 10px; }
  .row { width: 100%; }
  .col { display: inline-block; width: 49%; vertical-align: top; }
  .field { margin: 4px 0; }
  .label { color:#64748b; font-size: 10px; text-transform: uppercase; letter-spacing: 0.6px; }
  .value { font-size: 12px; font-weight: 700; margin-top: 1px; }

  .p { margin: 8px 0; line-height: 1.45; }

  .diagbox {
    margin-top: 8px;
    border-radius: 10px;
    border: 1px solid #dbeafe;
    background: #f8fafc;
    padding: 10px 12px;
    border-left: 5px solid #059669;
    page-break-inside: avoid;
  }
  .diagtitle { font-size: 12px; color:#059669; font-weight: 900; margin: 0 0 4px 0; }
  .diagtext { font-size: 12.5px; font-weight: 800; margin: 0 0 4px 0; }
  .notes { font-size: 10.8px; color:#475569; }

  .sectionlabel {
    margin-top: 10px;
    font-size: 10px;
    color:#64748b;
    text-transform: uppercase;
    letter-spacing: 0.6px;
  }

  .dates {
    margin-top: 6px;
    text-align: center;
    border: 1px dashed #cbd5e1;
    border-radius: 10px;
    padding: 8px 10px;
    page-break-inside: avoid;
  }
  .dates .big { font-size: 13.5px; font-weight: 900; margin: 0; }
  .dates .small { font-size: 10.5px; color:#475569; margin-top: 4px; }

  .pill-row { margin-top: 8px; }
  .pill {
    display: inline-block;
    width: 49%;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 7px 10px;
    vertical-align: top;
    margin-bottom: 6px;
  }
  .pill-label { display:block; font-size: 9.8px; color:#64748b; text-transform: uppercase; letter-spacing: 0.6px; }
  .pill-value { display:block; font-size: 12px; font-weight: 900; margin-top: 2px; }

  .follow {
    margin-top: 8px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 8px 10px;
    font-size: 10.8px;
    color:#334155;
  }
  .tag {
    display:inline-block;
    border: 1px solid #e2e8f0;
    border-radius: 999px;
    padding: 2px 8px;
    font-size: 9.5px;
    color:#64748b;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    margin-right: 6px;
  }

  .footer {
    position: fixed;
    left: 14mm; right: 14mm;
    bottom: 10mm;
    font-size: 10px;
    color: #64748b;
  }
  .sig { float:right; width: 230px; text-align:center; color:#0f172a; }
  .line { border-top: 1px solid #94a3b8; margin: 10px 0 6px; }
  .stamp { border: 1px dashed #94a3b8; padding: 7px; font-weight: 900; margin-bottom: 8px; border-radius: 8px; }
  .qr { width: 78px; height: 78px; border:1px solid #e2e8f0; margin: 6px auto 0; display:flex; align-items:center; justify-content:center; font-size:10px; color:#94a3b8; border-radius: 10px; }
  .verify { text-align:center; margin-top: 6px; }
  .clear { clear: both; }
</style>
</head>
<body>
  <div class='wm'>CAVITE MEDICAL</div>

  <div class='sheet'>
    <div class='topbar'>
      <div class='clinic'>CAVMED MEDICAL CENTER</div>
      <div class='subclinic'>Official Medical Documentation • Cavite-Med System</div>
    </div>

    <div class='titlewrap'>
      <h1 class='title'>{$doc_title}</h1>
      <div class='meta'>
        <span>Certificate No: <b>{$certificate_number}</b></span>
        <span>Date Issued: <b>{$issue_date}</b></span>
      </div>
    </div>

    <div class='grid'>
      <div class='row'>
        <div class='col'>
          <div class='field'><div class='label'>patient name</div><div class='value'>{$patient_name}</div></div>
          <div class='field'><div class='label'>patient id</div><div class='value'>{$patient_mrn}</div></div>
        </div>
        <div class='col'>
          <div class='field'><div class='label'>date of birth</div><div class='value'>{$patient_dob}</div></div>
          <div class='field'><div class='label'>gender</div><div class='value'>{$patient_gender}</div></div>
        </div>
      </div>
    </div>

    {$main_statement}

    <div class='diagbox'>
      <div class='diagtitle'>diagnosis / condition</div>
      <div class='diagtext'>{$diag_safe}</div>
      ".($notes_trimmed !== "" ? "<div class='notes'><b>notes:</b> {$notes_safe}</div>" : "")."
    </div>

    <div class='sectionlabel'>{$period_label}</div>
    <div class='dates'>
  <p class='big'>{$leave_from} to {$leave_to}</p>
  <div class='small'>{$dates_subtitle}</div>
</div>


    {$period_line}

    {$follow_up_block}
  </div>

  <div class='footer'>
    <div class='sig'>
      {$stamp_html}
      <div class='line'></div>
      <div style='font-weight:900;'>{$doctor_name}</div>
      <div style='color:#475569;'>CAVMED Medical Center</div>
    </div>
    <div class='clear'></div>

    <div class='verify'>
      <div>This certificate can be verified online.</div>
      {$qr_html}
      <div>Valid only with official stamp and signature.</div>
    </div>
  </div>
</body>
</html>
";

  // Render PDF
  $options = new Options();
  $options->set('isRemoteEnabled', true);

  $dompdf = new Dompdf($options);
  $dompdf->loadHtml($html, 'UTF-8');
  $dompdf->setPaper("A4", "portrait");
  $dompdf->render();

  // Save file
  $dir = __DIR__ . "/../uploads/documents/medical_certificates";
  if (!is_dir($dir)) mkdir($dir, 0777, true);

  $filename = $certificate_number . ".pdf";
  $abs_path = $dir . "/" . $filename;
  file_put_contents($abs_path, $dompdf->output());

  $size_kb = (int)ceil(filesize($abs_path) / 1024);

  $rel_path = "uploads/documents/medical_certificates/" . $filename;

  // Insert into patient_documents
  $doc_sql = "INSERT INTO patient_documents
    (patient_id, visit_id, document_title, file_type, file_size_kb, file_path, uploaded_by, uploaded_at, document_type, certificate_id)
    VALUES (?,?,?,?,?,?,?,?,?,?)";

  $doc_title_full = $doc_title . " " . $certificate_number;
  $file_type = "pdf";
  $uploaded_at = date("Y-m-d H:i:s");
  $document_type = "medical_certificate";

  $dstmt = $conn->prepare($doc_sql);
  if (!$dstmt) throw new Exception("Prepare patient_documents failed: " . $conn->error);

  $dstmt->bind_param(
    "iissisissi",
    $patient_id,
    $visit_id_param,
    $doc_title_full,
    $file_type,
    $size_kb,
    $rel_path,
    $created_by,
    $uploaded_at,
    $document_type,
    $certificate_id
  );

  if (!$dstmt->execute()) {
    throw new Exception("Insert patient_documents failed: " . $dstmt->error);
  }

  $conn->commit();

  $download_url = "/CAVITE-MED/" . $rel_path;

  json_out([
    "ok" => true,
    "certificate_id" => $certificate_id,
    "certificate_number" => $certificate_number,
    "download_url" => $download_url
  ]);

} catch (Throwable $e) {
  $conn->rollback();
  json_out(["ok" => false, "error" => $e->getMessage()], 500);
}
