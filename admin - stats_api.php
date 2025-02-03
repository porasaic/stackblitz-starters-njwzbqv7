<?php
// admin/stats_api.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

// Must be admin
if (get_logged_in_user_role() !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

// Get total users
$resUsers = $conn->query("SELECT COUNT(*) AS cnt FROM users");
$totalUsers = $resUsers->fetch_assoc()['cnt'] ?? 0;

// Active check requests
$resReq = $conn->query("SELECT COUNT(*) AS cnt FROM check_requests WHERE status='pending'");
$activeRequests = $resReq->fetch_assoc()['cnt'] ?? 0;

// Pending Approvals
// Let's assume an "approve" is the final step—so 'pending' also means awaiting approval
$pendingApprovals = $activeRequests; // Or you could differentiate if you have more statuses

echo json_encode([
    'totalUsers' => $totalUsers,
    'activeRequests' => $activeRequests,
    'pendingApprovals' => $pendingApprovals
]);
