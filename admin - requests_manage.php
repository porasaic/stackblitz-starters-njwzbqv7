<?php
// admin/requests_manage.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin_role();

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id     = (int)$_GET['id'];
    if (in_array($action, ['approve', 'deny'])) {
        $stmt = $conn->prepare("UPDATE requests SET status=? WHERE id=?");
        $stmt->bind_param("si", $action, $id);
        $stmt->execute();
    }
    header('Location: /admin/requests_manage.php');
    exit;
}

// Load all requests
$sql = "
  SELECT r.*, u.username
  FROM requests r
  JOIN users u ON r.user_id=u.user_id
  ORDER BY r.created_at DESC
";
$res = $conn->query($sql);
$requests = $res->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Manage Requests</title>
  <link href="https://cdn.tailwindcss.com" rel="stylesheet">
</head>
<body class="bg-gray-100 p-4">
  <h1 class="text-2xl font-bold mb-4">Manage Equipment/Check Requests</h1>
  <a href="/admin/dashboard.php" class="text-blue-600 hover:underline">← Back to Admin Dashboard</a>

  <div class="mt-4 bg-white p-4 rounded shadow">
    <?php if (!$requests): ?>
      <p>No requests found.</p>
    <?php else: ?>
      <table class="min-w-full border text-sm">
        <thead>
          <tr>
            <th class="border-b px-4 py-2">ID</th>
            <th class="border-b px-4 py-2">User</th>
            <th class="border-b px-4 py-2">Type</th>
            <th class="border-b px-4 py-2">Details</th>
            <th class="border-b px-4 py-2">Status</th>
            <th class="border-b px-4 py-2">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($requests as $req): ?>
            <tr>
              <td class="border-b px-4 py-2"><?= $req['id'] ?></td>
              <td class="border-b px-4 py-2"><?= htmlspecialchars($req['username']) ?></td>
              <td class="border-b px-4 py-2"><?= htmlspecialchars($req['request_type']) ?></td>
              <td class="border-b px-4 py-2"><?= htmlspecialchars($req['request_details']) ?></td>
              <td class="border-b px-4 py-2"><?= htmlspecialchars($req['status']) ?></td>
              <td class="border-b px-4 py-2">
                <?php if ($req['status'] === 'pending'): ?>
                  <a href="?action=approve&id=<?= $req['id'] ?>" class="text-green-600 hover:underline mr-2">Approve</a>
                  <a href="?action=deny&id=<?= $req['id'] ?>" class="text-red-600 hover:underline">Deny</a>
                <?php else: ?>
                  <em><?= ucfirst($req['status']) ?></em>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</body>
</html>
