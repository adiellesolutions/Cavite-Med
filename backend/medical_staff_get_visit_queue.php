<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . "/db/cavitemed_db.php"; // ✅ safer path

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

$user_id = (int)$_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';

try {
    // -------------------------
    // Get user's health_center_id
    // -------------------------
    $st = $conn->prepare("SELECT health_center_id FROM users WHERE user_id=? LIMIT 1");
    if (!$st) {
        http_response_code(500);
        echo json_encode(["ok" => false, "error" => "Prepare user center failed", "details" => $conn->error]);
        exit;
    }

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

    // ✅ base SELECT (NOW filtered by p.health_center_id)
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
          AND p.health_center_id = ?
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
            AND v.status IN ('waiting','in_progress','completed','for_dispense', 'for_consultation')
            ORDER BY
                FIELD(v.status, 'in_progress', 'waiting', 'for_dispense', 'completed'),
                FIELD(v.priority, 'high', 'medium', 'low'),
                v.visit_datetime ASC
        ";
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(["ok" => false, "error" => "Prepare query failed", "details" => $conn->error]);
        exit;
    }

    $stmt->bind_param("i", $health_center_id);
    $stmt->execute();

    $result = $stmt->get_result();
    if (!$result) {
        http_response_code(500);
        echo json_encode(["ok" => false, "error" => "Query failed", "details" => $stmt->error]);
        exit;
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    $stmt->close();

    echo json_encode(["ok" => true, "data" => $rows]);
    $conn->close();

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["ok" => false, "error" => "Server error: " . $e->getMessage()]);
}