<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(["ok" => false, "error" => "Unauthorized"]);
  exit;
}

require_once __DIR__ . "/db/cavitemed_db.php"; // $conn (mysqli)

if (!isset($conn) || !($conn instanceof mysqli)) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "Database connection not found"]);
  exit;
}

$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
if ($patient_id <= 0) {
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => "Invalid patient_id"]);
  exit;
}

try {
  // -------------------------
  // Patient (main)
  // -------------------------
  $sqlPatient = "SELECT * FROM patients WHERE patient_id = ? LIMIT 1";
  $stmt = $conn->prepare($sqlPatient);
  if (!$stmt) throw new Exception("Prepare patient failed: " . $conn->error);
  $stmt->bind_param("i", $patient_id);
  $stmt->execute();
  $res = $stmt->get_result();
  $patient = $res ? $res->fetch_assoc() : null;
  $stmt->close();

  if (!$patient) {
    http_response_code(404);
    echo json_encode(["ok" => false, "error" => "Patient not found"]);
    exit;
  }

  // -------------------------
  // Medical profile
  // -------------------------
  $profile = null;
  $sqlProfile = "SELECT * FROM patient_medical_profile WHERE patient_id = ? LIMIT 1";
  $stmt = $conn->prepare($sqlProfile);
  if (!$stmt) throw new Exception("Prepare profile failed: " . $conn->error);
  $stmt->bind_param("i", $patient_id);
  $stmt->execute();
  $res = $stmt->get_result();
  $profile = $res ? $res->fetch_assoc() : null;
  $stmt->close();

  // -------------------------
  // Latest vitals
  // -------------------------
  $latestVitals = null;
  $sqlVitals = "
    SELECT *
    FROM patient_vitals
    WHERE patient_id = ?
    ORDER BY recorded_at DESC
    LIMIT 1
  ";
  $stmt = $conn->prepare($sqlVitals);
  if (!$stmt) throw new Exception("Prepare vitals failed: " . $conn->error);
  $stmt->bind_param("i", $patient_id);
  $stmt->execute();
  $res = $stmt->get_result();
  $latestVitals = $res ? $res->fetch_assoc() : null;
  $stmt->close();

  // -------------------------
  // Visits timeline (optional)
  // -------------------------
  $visits = [];
  $sqlVisits = "
    SELECT
      v.visit_id,
      v.visit_type,
      v.visit_datetime,
      v.status,
      v.notes,
      u.full_name AS doctor_name,
      hc.center_name AS location_name
    FROM patient_visits v
    LEFT JOIN users u ON u.user_id = v.doctor_id
    LEFT JOIN health_centers hc ON hc.id = v.health_center_id
    WHERE v.patient_id = ?
    ORDER BY v.visit_datetime DESC
    LIMIT 20
  ";
  $stmt = $conn->prepare($sqlVisits);
  if (!$stmt) throw new Exception("Prepare visits failed: " . $conn->error);
  $stmt->bind_param("i", $patient_id);
  $stmt->execute();
  $res = $stmt->get_result();
  $visits = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
  $stmt->close();

  // -------------------------
  // Insurance (primary + secondary)
  // -------------------------
  $insurance = [];
  $sqlIns = "
    SELECT
      insurance_id,
      coverage_type,
      provider_name,
      policy_number,
      group_number,
      effective_date,
      subscriber_name,
      relationship,
      verified_status,
      verified_at
    FROM patient_insurance
    WHERE patient_id = ?
    ORDER BY FIELD(coverage_type,'primary','secondary'), insurance_id ASC
  ";
  $stmt = $conn->prepare($sqlIns);
  if (!$stmt) throw new Exception("Prepare insurance failed: " . $conn->error);
  $stmt->bind_param("i", $patient_id);
  $stmt->execute();
  $res = $stmt->get_result();
  $insurance = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
  $stmt->close();

  // -------------------------
  // Emergency contacts
  // -------------------------
  $emergency_contacts = [];
  $sqlEm = "
    SELECT
      contact_id,
      full_name,
      relationship,
      phone,
      email,
      address,
      is_primary,
      created_at
    FROM patient_emergency_contacts
    WHERE patient_id = ?
    ORDER BY is_primary DESC, created_at DESC
  ";
  $stmt = $conn->prepare($sqlEm);
  if (!$stmt) throw new Exception("Prepare emergency failed: " . $conn->error);
  $stmt->bind_param("i", $patient_id);
  $stmt->execute();
  $res = $stmt->get_result();
  $emergency_contacts = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
  $stmt->close();

  // -------------------------
  // Documents
  // -------------------------
  $documents = [];
  $sqlDocs = "
    SELECT
      d.document_id,
      d.document_title,
      d.file_type,
      d.file_size_kb,
      d.file_path,
      d.uploaded_at,
      u.full_name AS uploaded_by_name
    FROM patient_documents d
    LEFT JOIN users u ON u.user_id = d.uploaded_by
    WHERE d.patient_id = ?
    ORDER BY d.uploaded_at DESC
    LIMIT 50
  ";
  $stmt = $conn->prepare($sqlDocs);
  if (!$stmt) throw new Exception("Prepare documents failed: " . $conn->error);
  $stmt->bind_param("i", $patient_id);
  $stmt->execute();
  $res = $stmt->get_result();
  $documents = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
  $stmt->close();

  echo json_encode([
    "ok" => true,
    "patient" => $patient,
    "profile" => $profile,
    "latest_vitals" => $latestVitals,
    "visits" => $visits,
    "insurance" => $insurance,
    "emergency_contacts" => $emergency_contacts,
    "documents" => $documents
  ]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "Server error: " . $e->getMessage()]);
}
