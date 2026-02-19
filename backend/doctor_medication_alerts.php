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

if (!isset($_SESSION['user_id'])) respond(401, ["ok"=>false,"error"=>"Unauthorized"]);
if (isset($_SESSION['role']) && $_SESSION['role'] !== 'doctor') respond(403, ["ok"=>false,"error"=>"Forbidden"]);

require_once __DIR__ . '/db/cavitemed_db.php';
if (!isset($conn) || !($conn instanceof mysqli)) respond(500, ["ok"=>false,"error"=>"DB connection not found"]);

$raw = file_get_contents("php://input");
$body = json_decode($raw, true);
if (!is_array($body)) respond(400, ["ok"=>false,"error"=>"Invalid JSON"]);

$patient_id = (int)($body['patient_id'] ?? 0);
$medicine_id = (int)($body['medicine_id'] ?? 0);

if ($patient_id <= 0) respond(422, ["ok"=>false,"error"=>"patient_id is required"]);
if ($medicine_id <= 0) respond(422, ["ok"=>false,"error"=>"medicine_id is required"]);

try {
  // get patient profile
  $ps = $conn->prepare("SELECT allergies, current_medications FROM patient_medical_profile WHERE patient_id = ? LIMIT 1");
  $ps->bind_param("i", $patient_id);
  $ps->execute();
  $profile = $ps->get_result()->fetch_assoc();
  $ps->close();

  $allergies = strtolower(trim($profile['allergies'] ?? ''));
  $current_meds = trim($profile['current_medications'] ?? '');

  // get medicine info
  $ms = $conn->prepare("SELECT medicine_name, category FROM medicine WHERE id = ? LIMIT 1");
  $ms->bind_param("i", $medicine_id);
  $ms->execute();
  $med = $ms->get_result()->fetch_assoc();
  $ms->close();

  if (!$med) respond(404, ["ok"=>false,"error"=>"Medicine not found"]);

  $medName = $med['medicine_name'] ?? '';
  $category = $med['category'] ?? '';

  $alerts = [];

  // very simple matching (substring) since no structured allergy table
  $needle1 = strtolower($medName);
  $needle2 = strtolower($category);

  if ($allergies !== '' && (
      ($needle1 !== '' && strpos($allergies, $needle1) !== false) ||
      ($needle2 !== '' && strpos($allergies, $needle2) !== false)
    )) {
    $alerts[] = [
      "type" => "error",
      "title" => "Drug Allergy Alert",
      "message" => "Patient allergies may match selected medication (" . $medName . " / " . $category . "). Review allergies: " . ($profile['allergies'] ?? '')
    ];
  }

  if ($current_meds !== '') {
    $alerts[] = [
      "type" => "warning",
      "title" => "Drug Interaction Warning",
      "message" => "Potential interaction check needed. Current medications: " . $current_meds
    ];
  }

  respond(200, ["ok"=>true, "alerts"=>$alerts]);

} catch (Throwable $e) {
  respond(500, ["ok"=>false, "error"=>$e->getMessage()]);
}
