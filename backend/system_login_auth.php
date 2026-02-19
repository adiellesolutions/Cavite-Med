<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . "/db/cavitemed_db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$usernameOrEmail = trim($_POST['username'] ?? '');
$password        = $_POST['password'] ?? '';

if ($usernameOrEmail === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Missing credentials']);
    exit;
}

/* =========================
   Fetch user (WITH HEALTH CENTER)
========================= */
$stmt = $conn->prepare("
    SELECT 
        u.user_id,
        u.full_name,
        u.username,
        u.password,
        u.role,
        u.status,
        u.failed_attempts,
        u.must_change_password,
        u.profile_picture,
        u.health_center_id,
        hc.center_name
    FROM users u
    LEFT JOIN health_centers hc 
        ON u.health_center_id = hc.id
    WHERE (u.username = ? OR u.email = ?)
      AND u.deleted_at IS NULL
    LIMIT 1
");

$stmt->bind_param("ss", $usernameOrEmail, $usernameOrEmail);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
    exit;
}

/* =========================
   Status check
========================= */
if ($user['status'] !== 'active') {
    echo json_encode(['success' => false, 'message' => 'Account is inactive']);
    exit;
}

/* =========================
   Password verify
========================= */
if (!password_verify($password, $user['password'])) {

    $stmtFail = $conn->prepare("
        UPDATE users
        SET failed_attempts = failed_attempts + 1
        WHERE user_id = ?
    ");
    $stmtFail->bind_param("i", $user['user_id']);
    $stmtFail->execute();

    echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
    exit;
}

/* =========================
   Login success
========================= */
$stmtSuccess = $conn->prepare("
    UPDATE users
    SET failed_attempts = 0,
        last_login = NOW()
    WHERE user_id = ?
");
$stmtSuccess->bind_param("i", $user['user_id']);
$stmtSuccess->execute();

/* =========================
   Session
========================= */
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['role']    = $user['role'];
$_SESSION['name']    = $user['full_name'];
$_SESSION['profile_picture'] = $user['profile_picture'];

$_SESSION['health_center_id'] = $user['health_center_id'];
$_SESSION['health_center_name'] = $user['center_name'];

/* =========================
   FORCE PASSWORD CHANGE
========================= */
if ((int)$user['must_change_password'] === 1) {
    $_SESSION['force_change_password'] = true;

    echo json_encode([
        'success' => true,
        'force_change_password' => true
    ]);
    exit;
}

/* =========================
   Normal login
========================= */
echo json_encode([
    'success' => true,
    'role' => $user['role']
]);
exit;
