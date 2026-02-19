<?php
session_start();

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo "Unauthorized";
  exit;
}

require_once __DIR__ . "/../db/cavitemed_db.php";

$certificate_id = (int)($_GET['certificate_id'] ?? 0);
if ($certificate_id <= 0) {
  http_response_code(400);
  echo "Missing certificate_id";
  exit;
}

$stmt = $conn->prepare("
  SELECT d.file_path, d.file_type, d.document_title
  FROM patient_documents d
  WHERE d.certificate_id = ? AND d.document_type = 'medical_certificate'
  ORDER BY d.uploaded_at DESC
  LIMIT 1
");
$stmt->bind_param("i", $certificate_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();

if (!$row) {
  http_response_code(404);
  echo "File not found";
  exit;
}

$filePath = __DIR__ . "/../../" . $row['file_path'];
if (!file_exists($filePath)) {
  http_response_code(404);
  echo "File not found on disk";
  exit;
}

$filename = preg_replace('/[^a-zA-Z0-9_\- ]/', '', $row['document_title']);
$ext = $row['file_type'] === 'pdf' ? 'pdf' : 'html';

if ($ext === 'pdf') {
  header("Content-Type: application/pdf");
  header("Content-Disposition: inline; filename=\"{$filename}.pdf\"");
} else {
  header("Content-Type: text/html; charset=utf-8");
  header("Content-Disposition: inline; filename=\"{$filename}.html\"");
}

readfile($filePath);
