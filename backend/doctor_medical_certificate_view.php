<?php
session_start();

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  exit("Unauthorized");
}

require_once __DIR__ . "/db/cavitemed_db.php"; // $conn

if (!isset($conn) || !($conn instanceof mysqli)) {
  http_response_code(500);
  exit("Database connection not found");
}

$certificate_id = isset($_GET['certificate_id']) ? (int)$_GET['certificate_id'] : 0;
if ($certificate_id <= 0) {
  http_response_code(400);
  exit("certificate_id is required");
}

/*
  ✅ security:
  - allow doctor who created it OR any logged-in user if you want
  - adjust WHERE depending on your rules
*/
$sql = "
  SELECT
    pd.file_path,
    mc.created_by
  FROM medical_certificates mc
  LEFT JOIN patient_documents pd
    ON pd.certificate_id = mc.certificate_id
  WHERE mc.certificate_id = ?
  LIMIT 1
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  http_response_code(500);
  exit("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $certificate_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$row || empty($row['file_path'])) {
  http_response_code(404);
  exit("PDF not found");
}

// ✅ allow only creator doctor (optional)
$doctor_id = (int)$_SESSION['user_id'];
if ((int)$row['created_by'] !== $doctor_id) {
  http_response_code(403);
  exit("Forbidden");
}

// ✅ resolve absolute file path safely
// remove starting slash
$relative = ltrim($row['file_path'], "/");  
// result: uploads/medical_certificates/MC-2026-0001.pdf

// project root = cavite-med
$projectRoot = realpath(__DIR__ . "/../"); 

// absolute file path
$abs = $projectRoot . "/" . $relative;

// base uploads folder (for security check)
$base = realpath($projectRoot . "/uploads");

if (!$abs || !$base || strpos($abs, $base) !== 0 || !file_exists($abs)) {
  http_response_code(404);
  exit("File not found");
}

// ✅ serve PDF inline (no folder listing)
header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=\"certificate_$certificate_id.pdf\"");
header("Content-Length: " . filesize($abs));
header("Accept-Ranges: bytes");

readfile($abs);
exit;
