<?php
// employee/mark_task_complete.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');
require_login(); // Must be logged in

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

// Parse JSON body
$input = json_decode(file_get_contents('php://input'), true);
$uos_id = intval($input['uos_id'] ?? 0);
$userId = get_logged_in_user_id();

if ($uos_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing task info.']);
    exit;
}

// Ensure the task belongs to the logged-in user
$stmt = $conn->prepare("
    SELECT user_id 
    FROM user_onboarding_status 
    WHERE id=? 
    LIMIT 1
");
$stmt->bind_param("i", $uos_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();

if (!$row || (int)$row['user_id'] !== (int)$userId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

// Mark complete
$stmtUpdate = $conn->prepare("
    UPDATE user_onboarding_status 
    SET is_completed=1, completed_at=NOW() 
    WHERE id=?
");
$stmtUpdate->bind_param("i", $uos_id);
if ($stmtUpdate->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $conn->error]);
}
