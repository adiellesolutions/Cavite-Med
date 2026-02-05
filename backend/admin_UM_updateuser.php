<?php
header('Content-Type: application/json');
require_once __DIR__ . "/db/cavitemed_db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false]);
    exit;
}

$user_id = $_POST['user_id'] ?? null;

if (!is_numeric($user_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid user']);
    exit;
}

/* ================================
   Handle Profile Picture (optional)
================================ */
$profilePath = null;

if (!empty($_FILES['profile_picture']['name'])) {

    $allowed = ['image/jpeg', 'image/png'];

    if (!in_array($_FILES['profile_picture']['type'], $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Invalid image type']);
        exit;
    }

    if ($_FILES['profile_picture']['size'] > 2 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Image too large (max 2MB)']);
        exit;
    }

    $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
    $fileName = uniqid('profile_') . '.' . $ext;
    $uploadDir = __DIR__ . '/../uploads/profile/';
    $profilePath = 'uploads/profile/' . $fileName;

    if (!move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadDir . $fileName)) {
        echo json_encode(['success' => false, 'message' => 'Profile upload failed']);
        exit;
    }
}

/* ================================
   Build query dynamically
================================ */
if ($profilePath) {
    $stmt = $conn->prepare("
        UPDATE users SET
            full_name = ?,
            username = ?,
            email = ?,
            role = ?,
            position = ?,
            contact_number = ?,
            clinic = ?,
            status = ?,
            profile_picture = ?
        WHERE user_id = ? AND deleted_at IS NULL
    ");

    $stmt->bind_param(
        "sssssssssi",
        $_POST['full_name'],
        $_POST['username'],
        $_POST['email'],
        $_POST['role'],
        $_POST['position'],
        $_POST['contact_number'],
        $_POST['clinic'],
        $_POST['status'],
        $profilePath,
        $user_id
    );

} else {
    // No profile update
    $stmt = $conn->prepare("
        UPDATE users SET
            full_name = ?,
            username = ?,
            email = ?,
            role = ?,
            position = ?,
            contact_number = ?,
            clinic = ?,
            status = ?
        WHERE user_id = ? AND deleted_at IS NULL
    ");

    $stmt->bind_param(
        "ssssssssi",
        $_POST['full_name'],
        $_POST['username'],
        $_POST['email'],
        $_POST['role'],
        $_POST['position'],
        $_POST['contact_number'],
        $_POST['clinic'],
        $_POST['status'],
        $user_id
    );
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed']);
}
