<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  echo json_encode(["ok"=>false, "error"=>"Unauthorized"]);
  exit;
}

require_once __DIR__ . "/db.php";

// Inputs (match your form names)
$name = trim($_POST["name"] ?? "");
$mrn = trim($_POST["mrn"] ?? "");
$dob = trim($_POST["dob"] ?? "");
$phone = trim($_POST["phone"] ?? "");
$doctor_id = trim($_POST["doctor_id"] ?? "");
$visit_from = trim($_POST["visit_from"] ?? "");
$visit_to = trim($_POST["visit_to"] ?? "");
$health_center_id = trim($_POST["health_center_id"] ?? "");

try {
  // Base query: use patients table fields
  // You don't have full_name column; you have first_name/last_name/etc.
  $sql = "
    SELECT DISTINCT
      p.patient_id,
      p.mrn,
      p.first_name,
      p.middle_name,
      p.last_name,
      p.phone,
      p.date_of_birth
    FROM patients p
    LEFT JOIN patient_visits v ON v.patient_id = p.patient_id
    WHERE 1=1
  ";
  $params = [];

  if ($name !== "") {
    $sql .= " AND CONCAT(p.first_name,' ',IFNULL(p.middle_name,''),' ',p.last_name) LIKE :name ";
    $params[":name"] = "%$name%";
  }
  if ($mrn !== "") {
    $sql .= " AND p.mrn LIKE :mrn ";
    $params[":mrn"] = "%$mrn%";
  }
  if ($dob !== "") {
    $sql .= " AND p.date_of_birth = :dob ";
    $params[":dob"] = $dob;
  }
  if ($phone !== "") {
    $sql .= " AND p.phone LIKE :phone ";
    $params[":phone"] = "%$phone%";
  }

  // Visit-based filters use patient_visits table (your schema)
  if ($doctor_id !== "") {
    $sql .= " AND v.doctor_id = :doctor_id ";
    $params[":doctor_id"] = $doctor_id;
  }
  if ($health_center_id !== "") {
    $sql .= " AND v.health_center_id = :hc ";
    $params[":hc"] = $health_center_id;
  }
  if ($visit_from !== "") {
    $sql .= " AND v.visit_datetime >= :vf ";
    $params[":vf"] = $visit_from . " 00:00:00";
  }
  if ($visit_to !== "") {
    $sql .= " AND v.visit_datetime <= :vt ";
    $params[":vt"] = $visit_to . " 23:59:59";
    $params[":vt"] = $visit_to . " 23:59:59";
  }

  $sql .= " ORDER BY p.last_name, p.first_name LIMIT 50 ";

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Build full_name in PHP for the JS to display
  $results = array_map(function($r){
    $full = trim($r["first_name"]." ".($r["middle_name"] ?? "")." ".$r["last_name"]);
    return [
      "patient_id" => $r["patient_id"],
      "mrn" => $r["mrn"],
      "full_name" => $full,
      "phone" => $r["phone"],
      "dob" => $r["date_of_birth"],
    ];
  }, $rows);

  echo json_encode(["ok"=>true, "results"=>$results]);
} catch (Exception $e) {
  echo json_encode(["ok"=>false, "error"=>"Server error: ".$e->getMessage()]);
}
