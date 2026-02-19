<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

function labelFrequency(string $v): string {
  $map = [
    'once-daily' => 'Once daily',
    'twice-daily' => 'Twice daily (BID)',
    'three-times' => 'Three times daily (TID)',
    'four-times' => 'Four times daily (QID)',
    'every-6h' => 'Every 6 hours',
    'every-8h' => 'Every 8 hours',
    'every-12h' => 'Every 12 hours',
    'as-needed' => 'As needed (PRN)',
    'bedtime' => 'At bedtime (HS)',
  ];
  return $map[$v] ?? $v;
}

function fmtDate($dt): string {
  if (!$dt) return '';
  $t = strtotime((string)$dt);
  if (!$t) return (string)$dt;
  return date('F d, Y h:i A', $t);
}

function esc($v): string {
  return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function generatePrescriptionPdf(array $meta, array $items, string $savePathAbs): int {

  $clinicName = "CAVMED MEDICAL CENTER";
  $clinicSub  = "Electronic Prescription • Cavite-Med System";

  $rxNo      = esc($meta['prescription_number'] ?? '');
  $createdAt = esc(fmtDate($meta['created_at'] ?? ''));
  $allowSub  = ((int)($meta['allow_substitution'] ?? 0) === 1) ? "YES" : "NO";

  $patientName = esc($meta['patient_name'] ?? '');
  $patientId   = esc($meta['patient_id'] ?? '-');
  $mrn         = esc($meta['mrn'] ?? '-');

  $doctorName  = esc($meta['doctor_name'] ?? 'Doctor');

  $generalInstructions = trim((string)($meta['special_instructions'] ?? ''));

  // Build medicine rows
  $rows = "";
  $n = 0;
  foreach ($items as $it) {
    $n++;
    $medName  = esc($it['medicine_name'] ?? '');
    $category = esc($it['category'] ?? '');
    $dosage   = esc(($it['dosage_amount'] ?? '') . ($it['dosage_unit'] ?? ''));
    $freq     = esc(labelFrequency((string)($it['frequency_template'] ?? '')));
    $duration = esc(($it['duration_amount'] ?? '') . ' ' . ($it['duration_unit'] ?? ''));
    $route    = esc($it['route_admin'] ?? '');
    $instr    = trim((string)($it['item_instructions'] ?? ''));

    $rows .= "
      <tr>
        <td class='col-no'>{$n}</td>
        <td class='col-med'>
          <div class='med-name'>{$medName}</div>
          ".($category !== '' ? "<div class='med-cat'>{$category}</div>" : "")."
        </td>
        <td class='col-dose'>{$dosage}</td>
        <td class='col-freq'>{$freq}</td>
        <td class='col-dur'>{$duration}</td>
        <td class='col-route'>{$route}</td>
      </tr>
    ";

    if ($instr !== '') {
      $rows .= "
        <tr class='row-instr'>
          <td></td>
          <td colspan='5'>
            <span class='instr-label'>instructions:</span>
            <span class='instr-text'>" . nl2br(esc($instr)) . "</span>
          </td>
        </tr>
      ";
    }
  }

  $generalBox = "";
  if ($generalInstructions !== '') {
    $generalBox = "
      <div class='note-box'>
        <div class='note-title'>general instructions</div>
        <div class='note-text'>".nl2br(esc($generalInstructions))."</div>
      </div>
    ";
  }

  $html = "
<!doctype html>
<html>
<head>
<meta charset='utf-8'>
<style>
  @page { margin: 14mm 14mm 18mm 14mm; }
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11.5px; color:#0f172a; }
  * { box-sizing: border-box; }

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

  .sheet {
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 12px 14px;
    min-height: 250mm;
    position: relative;
    overflow: hidden;
  }

  .topbar {
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 8px;
    margin-bottom: 10px;
  }
  .clinic { font-size: 14px; font-weight: 800; letter-spacing: 0.6px; }
  .subclinic { font-size: 10.5px; color:#475569; margin-top: 2px; }

  .titlewrap { text-align: center; margin-top: 6px; }
  .title { font-size: 18px; font-weight: 900; margin: 0; letter-spacing: 0.7px; }
  .meta { margin-top: 6px; color:#475569; font-size: 10.5px; }
  .meta span { display: inline-block; margin: 0 10px; }

  .grid {
    margin-top: 10px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 10px 10px;
  }
  .row { width: 100%; }
  .col { display: inline-block; width: 49%; vertical-align: top; }
  .field { margin: 4px 0; }
  .label { color:#64748b; font-size: 10px; text-transform: uppercase; letter-spacing: 0.6px; }
  .value { font-size: 12px; font-weight: 800; margin-top: 1px; }

  .pill-row { margin-top: 8px; }
  .pill {
    display: inline-block;
    width: 49%;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 7px 10px;
    vertical-align: top;
    margin-bottom: 6px;
    background: #ffffff;
  }
  .pill-label {
    display:block;
    font-size: 9.8px;
    color:#64748b;
    text-transform: uppercase;
    letter-spacing: 0.6px;
  }
  .pill-value {
    display:block;
    font-size: 12px;
    font-weight: 900;
    margin-top: 2px;
  }

  .sectionlabel {
    margin-top: 12px;
    font-size: 10px;
    color:#64748b;
    text-transform: uppercase;
    letter-spacing: 0.6px;
  }

  table.items {
    width: 100%;
    border-collapse: collapse;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
    margin-top: 6px;
  }
  table.items thead th {
    background: #f1f5f9;
    border-bottom: 1px solid #e2e8f0;
    padding: 8px 8px;
    text-align: left;
    font-size: 10.5px;
    color: #334155;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  table.items tbody td {
    border-bottom: 1px solid #eef2f7;
    padding: 8px 8px;
    vertical-align: top;
    font-size: 11px;
  }
  table.items tbody tr:nth-child(even) td {
    background: #fbfdff;
  }

  .col-no { width: 4%; text-align: right; color: #64748b; }
  .col-med { width: 34%; }
  .col-dose { width: 12%; }
  .col-freq { width: 20%; }
  .col-dur { width: 16%; }
  .col-route { width: 14%; }

  .med-name { font-weight: 900; margin-bottom: 2px; }
  .med-cat { font-size: 10px; color: #64748b; }

  .row-instr td {
    background: #fff !important;
    padding-top: 0;
  }
  .instr-label {
    font-weight: 900;
    color:#334155;
    text-transform: lowercase;
    margin-right: 6px;
  }
  .instr-text { color:#334155; }

  .note-box {
    margin-top: 10px;
    border-radius: 10px;
    border: 1px solid #dbeafe;
    background: #f8fafc;
    padding: 10px 12px;
    border-left: 5px solid #059669;
  }
  .note-title {
    font-size: 10px;
    color:#059669;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    margin-bottom: 6px;
  }
  .note-text { font-size: 11px; color:#334155; }

  .footer {
    position: fixed;
    left: 14mm; right: 14mm;
    bottom: 10mm;
    font-size: 10px;
    color: #64748b;
  }
  .sig { float:right; width: 260px; text-align:center; color:#0f172a; }
  .line { border-top: 1px solid #94a3b8; margin: 10px 0 6px; }
  .verify { text-align:left; margin-top: 6px; }

  .clear { clear: both; }

  .tiny { font-size: 9.8px; color:#64748b; line-height: 1.35; }
</style>
</head>
<body>
  <div class='wm'>CAVITE MEDICAL</div>

  <div class='sheet'>
    <div class='topbar'>
      <div class='clinic'>{$clinicName}</div>
      <div class='subclinic'>{$clinicSub}</div>
    </div>

    <div class='titlewrap'>
      <h1 class='title'>PRESCRIPTION</h1>
      <div class='meta'>
        <span>RX No: <b>{$rxNo}</b></span>
        <span>Date Issued: <b>{$createdAt}</b></span>
      </div>
    </div>

    <div class='grid'>
      <div class='row'>
        <div class='col'>
          <div class='field'><div class='label'>patient name</div><div class='value'>{$patientName}</div></div>
          <div class='field'><div class='label'>mrn</div><div class='value'>{$mrn}</div></div>
        </div>
        <div class='col'>
          <div class='field'><div class='label'>patient id</div><div class='value'>{$patientId}</div></div>
          <div class='field'><div class='label'>prescriber</div><div class='value'>Dr. {$doctorName}</div></div>
        </div>
      </div>

      
    </div>

    <div class='sectionlabel'>prescribed medicines</div>

    <table class='items'>
      <thead>
        <tr>
          <th>#</th>
          <th>medicine</th>
          <th>dose</th>
          <th>intake</th>
          <th>duration</th>
          <th>route</th>
        </tr>
      </thead>
      <tbody>
        {$rows}
      </tbody>
    </table>

    {$generalBox}

    <div class='tiny' style='margin-top:10px;'>
      <b>Reminders:</b> Dispense only after verifying patient identity. Follow dosage and duration strictly.
      If adverse reactions occur, stop medication and consult immediately.
    </div>
  </div>

  <div class='footer'>
    <div class='sig'>
      <div class='line'></div>
      <div style='font-weight:900;'>Dr. {$doctorName}</div>
      <div style='color:#475569;'>{$clinicName}</div>
    </div>
    <div class='clear'></div>

    <div class='verify'>
      <div class='tiny'>This document is system-generated by Cavite-Med. Valid only with authorized prescriber signature.</div>
    </div>
  </div>
</body>
</html>
";

  $options = new Options();
  $options->set('isRemoteEnabled', true);

  $dompdf = new Dompdf($options);
  $dompdf->loadHtml($html, 'UTF-8');
  $dompdf->setPaper('A4', 'portrait');
  $dompdf->render();

  $bytes = $dompdf->output();
  file_put_contents($savePathAbs, $bytes);

  return strlen($bytes);
}
