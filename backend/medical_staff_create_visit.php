<?php
// medical_staff_create_visit.php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once "../backend/db/cavitemed_db.php";

/**
 * Support db_connection.php that may expose $mysqli OR $conn
 */
$mysqli = $mysqli ?? $conn ?? null;

if (!($mysqli instanceof mysqli)) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database connection not found. Ensure db_connection.php provides \$mysqli or \$conn."
    ]);
    exit;
}

/**
 * ✅ Ensure DB timezone is correct (PH = +08:00)
 */
$mysqli->query("SET time_zone = '+08:00'");

/**
 * Optional: simple auth (adjust if you want)
 */
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

/**
 * -------------------------
 * Get user's health_center_id (scope)
 * -------------------------
 */
$st = $mysqli->prepare("SELECT health_center_id FROM users WHERE user_id=? LIMIT 1");
if (!$st) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Prepare user center failed", "error" => $mysqli->error]);
    exit;
}
$st->bind_param("i", $user_id);
$st->execute();
$st->bind_result($user_health_center_id);
$st->fetch();
$st->close();

$user_health_center_id = $user_health_center_id ? (int)$user_health_center_id : null;

if (!$user_health_center_id) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "User is not assigned to a health center."]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

/**
 * =========================
 * GET: PATIENT SEARCH
 * =========================
 * /medical_staff_create_visit.php?query=...
 */
if ($method === 'GET' && isset($_GET['query'])) {
    $searchTerm = trim($_GET['query']);

    if ($searchTerm === '') {
        echo json_encode([]);
        exit;
    }

    // ✅ limit results + scoped to health center
    $sql = "
        SELECT patient_id, first_name, last_name, mrn
        FROM patients
        WHERE status = 'active'
          AND health_center_id = ?
          AND (
            first_name LIKE ?
            OR last_name LIKE ?
            OR mrn LIKE ?
            OR CONCAT(first_name,' ',last_name) LIKE ?
          )
        ORDER BY last_name ASC, first_name ASC
        LIMIT 20
    ";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Prepare failed", "error" => $mysqli->error]);
        exit;
    }

    $like = "%{$searchTerm}%";
    $stmt->bind_param("issss", $user_health_center_id, $like, $like, $like, $like);

    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Execute failed", "error" => $stmt->error]);
        $stmt->close();
        exit;
    }

    $result = $stmt->get_result();
    $patients = [];
    while ($row = $result->fetch_assoc()) {
        $patients[] = $row;
    }

    $stmt->close();
    echo json_encode($patients);
    exit;
}

/**
 * =========================
 * POST: CREATE VISIT
 * =========================
 * Body: JSON
 */
if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Invalid JSON body"]);
        exit;
    }

    $patient_id   = isset($data['patient_id']) ? (int)$data['patient_id'] : 0;
    $visit_type   = isset($data['visit_type']) ? trim($data['visit_type']) : '';
    $visit_reason = isset($data['visit_reason']) ? trim($data['visit_reason']) : '';
    $priority     = isset($data['priority']) ? trim($data['priority']) : 'medium';
    $status       = isset($data['status']) ? trim($data['status']) : 'waiting';

    if ($patient_id <= 0 || $visit_type === '' || $visit_reason === '') {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Missing required fields: patient_id, visit_type, visit_reason"
        ]);
        exit;
    }

    // Validate ENUM values
    $allowedPriority = ['low', 'medium', 'high'];
    if (!in_array($priority, $allowedPriority, true)) $priority = 'medium';

    $allowedStatus = ['waiting', 'in_progress', 'completed', 'for_dispense'];
    if (!in_array($status, $allowedStatus, true)) $status = 'waiting';

    // ✅ Ensure patient exists AND belongs to same health center (prevents cross-center access)
    $check = $mysqli->prepare("
        SELECT patient_id
        FROM patients
        WHERE patient_id = ?
          AND health_center_id = ?
        LIMIT 1
    ");
    if (!$check) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Prepare failed", "error" => $mysqli->error]);
        exit;
    }
    $check->bind_param("ii", $patient_id, $user_health_center_id);
    $check->execute();
    $checkRes = $check->get_result();
    if ($checkRes->num_rows === 0) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Patient not found in your health center"]);
        $check->close();
        exit;
    }
    $check->close();

    $created_by = (int)$_SESSION['user_id']; // ✅ from session

    /**
     * Let MySQL DEFAULT CURRENT_TIMESTAMP handle visit_datetime
     */
    $sql = "
        INSERT INTO patient_visits
            (patient_id, visit_type, doctor_id, created_by, reason_for_visit, priority, status, notes)
        VALUES
            (?, ?, NULL, ?, ?, ?, ?, NULL)
    ";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Prepare failed", "error" => $mysqli->error]);
        exit;
    }

    // patient_id(i), visit_type(s), created_by(i), visit_reason(s), priority(s), status(s)
    $stmt->bind_param("isisss", $patient_id, $visit_type, $created_by, $visit_reason, $priority, $status);

    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Error inserting visit",
            "error" => $stmt->error
        ]);
        $stmt->close();
        exit;
    }

    $visit_id = $stmt->insert_id;
    $stmt->close();

    // Optional: return the actual DB timestamp so you can confirm it
    $visit_datetime = null;
    $get = $mysqli->prepare("SELECT visit_datetime FROM patient_visits WHERE visit_id = ? LIMIT 1");
    if ($get) {
        $get->bind_param("i", $visit_id);
        if ($get->execute()) {
            $row = $get->get_result()->fetch_assoc();
            $visit_datetime = $row['visit_datetime'] ?? null;
        }
        $get->close();
    }

    echo json_encode([
        "success" => true,
        "visit_id" => $visit_id,
        "patient_id" => $patient_id,
        "status" => $status,
        "visit_datetime" => $visit_datetime
    ]);
    exit;
}

/**
 * If request is not supported
 */
http_response_code(405);
echo json_encode(["success" => false, "message" => "Method not allowed"]);
exit;