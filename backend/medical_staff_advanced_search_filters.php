<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  echo json_encode(["ok" => false, "error" => "Unauthorized"]);
  exit;
}

// this file must define $conn = new mysqli(...)
require_once __DIR__ . "/db/cavitemed_db.php";

if (!isset($conn) || !($conn instanceof mysqli)) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "Database connection not found"]);
  exit;
}

$user_id = (int)$_SESSION['user_id'];

try {
  // -------------------------
  // Get user's health_center_id
  // -------------------------
  $st = $conn->prepare("SELECT health_center_id FROM users WHERE user_id=? LIMIT 1");
  if (!$st) throw new Exception("Prepare user center failed: " . $conn->error);

  $st->bind_param("i", $user_id);
  $st->execute();
  $st->bind_result($health_center_id);
  $st->fetch();
  $st->close();

  $health_center_id = $health_center_id ? (int)$health_center_id : null;

  if (!$health_center_id) {
    http_response_code(403);
    echo json_encode(["ok" => false, "error" => "User is not assigned to a health center."]);
    exit;
  }

  // -------------------------
  // Doctors (filtered to same health center)
  // -------------------------
  $doctors = [];
  $sqlDoctors = "
    SELECT user_id AS id, full_name AS name
    FROM users
    WHERE role = 'doctor'
      AND status = 'active'
      AND health_center_id = ?
    ORDER BY full_name
  ";

  $stmtDoc = $conn->prepare($sqlDoctors);
  if (!$stmtDoc) throw new Exception("Prepare doctors failed: " . $conn->error);

  $stmtDoc->bind_param("i", $health_center_id);
  $stmtDoc->execute();
  $res = $stmtDoc->get_result();
  $doctors = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
  $stmtDoc->close();

  // -------------------------
  // Locations (ONLY user's health center)
  // -------------------------
  $locations = [];
  $sqlLocs = "
    SELECT id, center_name AS name
    FROM health_centers
    WHERE id = ?
    ORDER BY center_name
  ";

  $stmtLoc = $conn->prepare($sqlLocs);
  if (!$stmtLoc) throw new Exception("Prepare locations failed: " . $conn->error);

  $stmtLoc->bind_param("i", $health_center_id);
  $stmtLoc->execute();
  $res2 = $stmtLoc->get_result();
  $locations = $res2 ? $res2->fetch_all(MYSQLI_ASSOC) : [];
  $stmtLoc->close();

  echo json_encode([
    "ok" => true,
    "doctors" => $doctors,
    "locations" => $locations
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "Server error: " . $e->getMessage()]);
}