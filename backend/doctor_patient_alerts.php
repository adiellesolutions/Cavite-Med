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

$patient_id = (int)($_GET['patient_id'] ?? 0);
if ($patient_id <= 0) respond(422, ["ok"=>false,"error"=>"patient_id is required"]);

try {
  // profile can be missing, so LEFT JOIN style
  $st = $conn->prepare("
    SELECT allergies, current_medications, chronic_conditions
    FROM patient_medical_profile
    WHERE patient_id = ?
    LIMIT 1
  ");
  $st->bind_param("i", $patient_id);
  $st->execute();
  $profile = $st->get_result()->fetch_assoc();
  $st->close();

  $alerts = [];

  $allergies = trim($profile['allergies'] ?? '');
  $current_meds = trim($profile['current_medications'] ?? '');

  if ($allergies !== '') {
    $alerts[] = [
      "type" => "error",
      "title" => "Drug Allergy Alert",
      "message" => "Patient has documented allergies: " . $allergies
    ];
  }

  if ($current_meds !== '') {
    $alerts[] = [
      "type" => "warning",
      "title" => "Current Medications",
      "message" => "Patient is currently taking: " . $current_meds . ". Please check possible interactions."
    ];
  }

  respond(200, ["ok"=>true, "alerts"=>$alerts]);

} catch (Throwable $e) {
  respond(500, ["ok"=>false, "error"=>$e->getMessage()]);
}
