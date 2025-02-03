<?php
// employee/request_check.php
require_once __DIR__ . '/../config/database.php';  
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$userId          = get_logged_in_user_id();
$request_type    = sanitize_input($_POST['request_type'] ?? '');
$request_details = sanitize_input($_POST['request_details'] ?? '');

if (empty($request_type)) {
    echo json_encode(['success' => false, 'message' => 'Missing request type']);
    exit;
}

// Insert the row
$stmt = $conn->prepare("
  INSERT INTO requests (user_id, request_type, request_details, status, created_at) 
  VALUES (?, ?, ?, 'pending', NOW())
");
$stmt->bind_param("iss", $userId, $request_type, $request_details);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Request submitted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'DB error: '.$conn->error]);
}
$stmt->close();
