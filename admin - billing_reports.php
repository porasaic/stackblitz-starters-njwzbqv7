<?php
// admin/billing_reports.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin_role();

// Summarize total hours by user
$sql = "
  SELECT u.username, SUM(tl.hours) AS total_hours
  FROM time_logs tl
  JOIN users u ON tl.user_id = u.user_id
  GROUP BY tl.user_id
  ORDER BY total_hours DESC
";
$res = $conn->query($sql);
$hoursByUser = $res->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Billing & Time Reports</title>
  <link href="https://cdn.tailwindcss.com" rel="stylesheet">
</head>
<body class="bg-gray-100 p-4">
  <h1 class="text-2xl font-bold mb-4">Billing & Time Tracking Reports</h1>
  <a href="/admin/dashboard.php" class="text-blue-600 hover:underline mb-4 inline-block">← Back to Admin Dashboard</a>

  <h2 class="text-xl font-semibold mb-2">Total Hours by User</h2>
  <div class="bg-white p-4 rounded shadow mb-6">
    <table class="min-w-full border">
      <thead>
        <tr>
          <th class="border-b px-4 py-2">Username</th>
          <th class="border-b px-4 py-2">Total Hours</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$hoursByUser): ?>
          <tr><td colspan="2" class="px-4 py-2">No data found.</td></tr>
        <?php else: ?>
          <?php foreach ($hoursByUser as $row): ?>
            <tr>
              <td class="border-b px-4 py-2"><?= htmlspecialchars($row['username']) ?></td>
              <td class="border-b px-4 py-2"><?= $row['total_hours'] ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
