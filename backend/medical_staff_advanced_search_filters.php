<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  echo json_encode(["ok" => false, "error" => "Unauthorized"]);
  exit;
}

// this file must define $conn = new mysqli(...)
require_once __DIR__ . "/db/cavitemed_db.php";

try {
  // Doctors: full_name from users where role=doctor
  $doctors = [];
  $sqlDoctors = "
    SELECT user_id AS id, full_name AS name
    FROM users
    WHERE role = 'doctor' AND status = 'active'
    ORDER BY full_name
  ";
  if ($res = $conn->query($sqlDoctors)) {
    $doctors = $res->fetch_all(MYSQLI_ASSOC);
  }

  // Locations: center_name from health_centers
  $locations = [];
  $sqlLocs = "
    SELECT id, center_name AS name
    FROM health_centers
    ORDER BY center_name
  ";
  if ($res2 = $conn->query($sqlLocs)) {
    $locations = $res2->fetch_all(MYSQLI_ASSOC);
  }

  echo json_encode([
    "ok" => true,
    "doctors" => $doctors,
    "locations" => $locations
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "Server error"]);
}
