<?php
// admin/impersonate.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin_role();

if (!isset($_GET['user_id'])) {
    die("No user specified.");
}
$user_id = (int)$_GET['user_id'];

// Fetch the user
$stmt = $conn->prepare("SELECT user_id, username, password_hash, role, status FROM users WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();

if (!$user) {
    die("User not found.");
}
if ($user['status'] === 'suspended') {
    die("Cannot impersonate a suspended user.");
}

// Store current admin’s ID to revert later (optional)
if (!isset($_SESSION['original_admin_id'])) {
    $_SESSION['original_admin_id'] = $_SESSION['user_id'];
    $_SESSION['original_admin_role'] = $_SESSION['user_role'];
}

$_SESSION['user_id']   = $user['user_id'];
$_SESSION['username']  = $user['username'];
$_SESSION['user_role'] = $user['role'];

// Redirect to the employee dashboard
header('Location: /employee/dashboard.php');
exit;
