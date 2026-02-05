<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  echo json_encode(["ok"=>false, "error"=>"Unauthorized"]);
  exit;
}

require_once __DIR__ . "/cavitemed_db.php"; // must create this and it must set $pdo (PDO)

try {
  // Doctors come from users table
  $stmt = $pdo->prepare("
    SELECT user_id AS id, full_name AS name
    FROM users
    WHERE role = 'doctor' AND status = 'active'
    ORDER BY full_name
  ");
  $stmt->execute();
  $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Locations come from health_centers table
  $stmt2 = $pdo->prepare("
    SELECT id, center_name AS name
    FROM health_centers
    ORDER BY center_name
  ");
  $stmt2->execute();
  $locations = $stmt2->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode([
    "ok" => true,
    "doctors" => $doctors,
    "locations" => $locations
  ]);
} catch (Exception $e) {
  echo json_encode(["ok"=>false, "error"=>"Server error: ".$e->getMessage()]);
}
