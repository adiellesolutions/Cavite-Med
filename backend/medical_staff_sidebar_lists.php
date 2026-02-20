<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(["ok" => false, "error" => "Unauthorized"]);
  exit;
}

require_once __DIR__ . "/db/cavitemed_db.php"; // provides $conn (mysqli)

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
  // FAVORITES (top 10)
  // includes last visit details
  // -------------------------
  $sqlFav = "
    SELECT
      p.patient_id,
      p.mrn,
      CONCAT(p.first_name,' ',p.last_name) AS full_name,
      p.status,

      v.visit_datetime AS last_visit_date,
      v.visit_type     AS last_visit_type,
      d.full_name      AS last_doctor_name,
      hc.center_name   AS last_location_name

    FROM user_patient_favorites f
    INNER JOIN patients p ON p.patient_id = f.patient_id

    LEFT JOIN patient_visits v
      ON v.patient_id = p.patient_id
     AND v.visit_datetime = (
        SELECT MAX(v2.visit_datetime)
        FROM patient_visits v2
        WHERE v2.patient_id = p.patient_id
     )

    LEFT JOIN users d ON d.user_id = v.doctor_id
    LEFT JOIN health_centers hc ON hc.id = p.health_center_id

    WHERE f.user_id = ?
      AND p.health_center_id = ?
    ORDER BY f.created_at DESC
    LIMIT 10
  ";

  $stmtFav = $conn->prepare($sqlFav);
  if (!$stmtFav) throw new Exception("Prepare favorites failed: " . $conn->error);

  $stmtFav->bind_param("ii", $user_id, $health_center_id);
  $stmtFav->execute();
  $resFav = $stmtFav->get_result();
  $favorites = $resFav ? $resFav->fetch_all(MYSQLI_ASSOC) : [];
  $stmtFav->close();


  // -------------------------
  // "RECENTS" (TOP 10 sidebar)
  // NOW: ALL PATIENTS sorted by most recent visit
  // - alias last_visit_date AS last_viewed_at so your JS timeAgo() still works
  // -------------------------
  $sqlRec = "
    SELECT
      p.patient_id,
      p.mrn,
      CONCAT(p.first_name,' ',p.last_name) AS full_name,

      v.visit_datetime AS last_viewed_at, -- keep your JS working (timeAgo uses this)

      v.visit_datetime AS last_visit_date,
      v.visit_type     AS last_visit_type,
      d.full_name      AS last_doctor_name,
      hc.center_name   AS last_location_name

    FROM patients p

    LEFT JOIN patient_visits v
      ON v.patient_id = p.patient_id
     AND v.visit_datetime = (
        SELECT MAX(v2.visit_datetime)
        FROM patient_visits v2
        WHERE v2.patient_id = p.patient_id
     )

    LEFT JOIN users d ON d.user_id = v.doctor_id
    LEFT JOIN health_centers hc ON hc.id = p.health_center_id

    WHERE p.health_center_id = ?

    ORDER BY
      v.visit_datetime IS NULL ASC,   -- patients with visits first
      v.visit_datetime DESC,          -- most recent visit first
      p.created_at DESC               -- tie-breaker
    LIMIT 10
  ";

  $stmtRec = $conn->prepare($sqlRec);
  if (!$stmtRec) throw new Exception("Prepare recent patients failed: " . $conn->error);

  $stmtRec->bind_param("i", $health_center_id);
  $stmtRec->execute();
  $resRec = $stmtRec->get_result();
  $recent = $resRec ? $resRec->fetch_all(MYSQLI_ASSOC) : [];
  $stmtRec->close();

  echo json_encode([
    "ok" => true,
    "favorites" => $favorites,
    "recent" => $recent
  ]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode([
    "ok" => false,
    "error" => "Server error: " . $e->getMessage()
  ]);
}