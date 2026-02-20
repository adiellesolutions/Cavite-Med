<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(["ok" => false, "error" => "Unauthorized"]);
  exit;
}

require_once __DIR__ . "/db/cavitemed_db.php";

$q = trim($_GET['q'] ?? '');
$limit = 25;

$doctorId = (int)$_SESSION['user_id'];

/*
  NOTE:
  - we use JOIN patient_visits to only show patients who have visits "for_consultation"
  - GROUP BY patient_id to avoid duplicate patients if they have multiple visits
  - MAX(v.visit_datetime) as latest_visit to show most recent
*/

$sql = "
  SELECT
    p.patient_id,
    p.mrn,
    p.first_name,
    p.last_name,
    p.middle_name,
    p.preferred_name,
    p.date_of_birth,
    p.gender,
    p.blood_type,
    MAX(v.visit_id) AS visit_id,
    MAX(v.visit_datetime) AS latest_visit_datetime
  FROM patients p
  INNER JOIN patient_visits v
    ON v.patient_id = p.patient_id
  WHERE p.status = 'active'
    AND v.status = 'for_consultation'

    -- ✅ optional: show only visits assigned to this doctor OR not assigned yet
    AND (v.doctor_id = ? OR v.doctor_id IS NULL)

    -- ✅ optional: only show today's queue
    -- AND DATE(v.visit_datetime) = CURDATE()

    AND (
      ? = '' OR
      p.mrn LIKE CONCAT('%', ?, '%') OR
      CAST(p.patient_id AS CHAR) LIKE CONCAT('%', ?, '%') OR
      CONCAT(p.first_name,' ',p.last_name) LIKE CONCAT('%', ?, '%') OR
      CONCAT(p.last_name,' ',p.first_name) LIKE CONCAT('%', ?, '%') OR
      CONCAT(p.first_name,' ',IFNULL(p.middle_name,''),' ',p.last_name) LIKE CONCAT('%', ?, '%') OR
      IFNULL(p.preferred_name,'') LIKE CONCAT('%', ?, '%')
    )
  GROUP BY
    p.patient_id, p.mrn, p.first_name, p.last_name, p.middle_name, p.preferred_name,
    p.date_of_birth, p.gender, p.blood_type
  ORDER BY latest_visit_datetime DESC
  LIMIT $limit
";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
  "isssssss",
  $doctorId,
  $q, $q, $q, $q, $q, $q, $q
);
$stmt->execute();
$res = $stmt->get_result();

$patients = [];
while ($row = $res->fetch_assoc()) {
  $full = trim(
    $row['first_name'] . ' ' .
    ($row['middle_name'] ? $row['middle_name'].' ' : '') .
    $row['last_name']
  );

  $initials = strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1));

  $patients[] = [
    "patient_id" => (int)$row["patient_id"],
    "visit_id" => (int)$row["visit_id"], // ✅ useful for linking prescription to visit
    "mrn" => $row["mrn"],
    "full_name" => $full,
    "preferred_name" => $row["preferred_name"],
    "initials" => $initials,
    "date_of_birth" => $row["date_of_birth"],
    "gender" => $row["gender"],
    "blood_type" => $row["blood_type"],
    "latest_visit_datetime" => $row["latest_visit_datetime"]
  ];
}

echo json_encode(["ok" => true, "patients" => $patients]);
