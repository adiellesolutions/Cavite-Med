<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . "/db/cavitemed_db.php"; // ✅ safer path

// ✅ use the correct connection variable
// if your db file uses $mysqli instead, change $conn to $mysqli below
if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(["ok" => false, "error" => "Database connection not found"]);
    exit;
}

// Ensure only medical_staff can access this page
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'medical_staff') {
    http_response_code(401);
    echo json_encode(["ok" => false, "error" => "Unauthorized"]);
    exit;
}

$action = $_GET['action'] ?? 'list';

// ✅ base SELECT
$baseSelect = "
    SELECT
        v.visit_id,
        v.patient_id,
        v.visit_type,
        v.visit_datetime,
        v.status,
        v.priority,
        v.reason_for_visit,
        p.mrn,
        p.first_name,
        p.last_name,
        p.gender,
        TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) AS age
    FROM patient_visits v
    INNER JOIN patients p ON p.patient_id = v.patient_id
    WHERE DATE(v.visit_datetime) = CURDATE()
";

if ($action === 'getActivePatient') {

    // ✅ pick active = in_progress first, otherwise earliest waiting
    $sql = $baseSelect . "
        AND v.status IN ('in_progress','waiting')
        ORDER BY
            FIELD(v.status, 'in_progress', 'waiting'),
            FIELD(v.priority, 'high', 'medium', 'low'),
            v.visit_datetime ASC
        LIMIT 1
    ";

} else {

    // ✅ list queue
    $sql = $baseSelect . "
        AND v.status IN ('waiting','in_progress','completed','for_dispense')
        ORDER BY
            FIELD(v.status, 'in_progress', 'waiting', 'for_dispense', 'completed'),
            FIELD(v.priority, 'high', 'medium', 'low'),
            v.visit_datetime ASC
    ";
}

$result = $conn->query($sql);

if (!$result) {
    http_response_code(500);
    echo json_encode(["ok" => false, "error" => "Query failed", "details" => $conn->error]);
    exit;
}

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

echo json_encode(["ok" => true, "data" => $rows]);
$conn->close();
