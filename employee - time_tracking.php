<?php
// employee/time_tracking.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_login();
$userId = get_logged_in_user_id();

// If POST, insert time log
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id  = (int)$_POST['client_id'] ?? 0;
    $description= sanitize_input($_POST['description'] ?? '');
    $hours      = (float)($_POST['hours'] ?? 0);
    $log_date   = $_POST['log_date'] ?? date('Y-m-d');

    if ($client_id > 0 && $hours > 0) {
        $stmt = $conn->prepare("INSERT INTO time_logs (user_id, client_id, description, hours, log_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issis", $userId, $client_id, $description, $hours, $log_date);
        $stmt->execute();
    }
    header('Location: /employee/time_tracking.php');
    exit;
}

// Fetch existing logs
$logRes = $conn->prepare("SELECT tl.*, c.client_name FROM time_logs tl 
                          JOIN clients c ON tl.client_id = c.client_id
                          WHERE tl.user_id=? 
                          ORDER BY tl.created_at DESC");
$logRes->bind_param("i", $userId);
$logRes->execute();
$logs = $logRes->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch clients for dropdown
$cRes = $conn->query("SELECT client_id, client_name FROM clients ORDER BY client_name ASC");
$clients = $cRes->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
  <title>Time Tracking</title>
  <link href="https://cdn.tailwindcss.com" rel="stylesheet">
</head>
<body class="bg-gray-100 p-4">
  <h1 class="text-2xl font-bold mb-4">Time Tracking</h1>
  <form method="post" class="bg-white p-4 rounded shadow w-full max-w-lg mb-6">
    <div class="mb-4">
      <label class="block font-medium mb-1" for="client_id">Client</label>
      <select name="client_id" id="client_id" class="border rounded w-full p-2" required>
        <option value="">-- Select Client --</option>
        <?php foreach ($clients as $cl): ?>
          <option value="<?= $cl['client_id'] ?>"><?= htmlspecialchars($cl['client_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="mb-4">
      <label class="block font-medium mb-1" for="description">Description</label>
      <textarea name="description" id="description" class="border rounded w-full p-2"></textarea>
    </div>
    <div class="mb-4">
      <label class="block font-medium mb-1" for="hours">Hours</label>
      <input type="number" name="hours" id="hours" step="0.1" min="0" class="border rounded w-full p-2" required>
    </div>
    <div class="mb-4">
      <label class="block font-medium mb-1" for="log_date">Date</label>
      <input type="date" name="log_date" id="log_date" class="border rounded w-full p-2" value="<?= date('Y-m-d') ?>">
    </div>
    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
      Log Time
    </button>
  </form>

  <h2 class="text-xl font-semibold mb-2">My Time Logs</h2>
  <div class="bg-white p-4 rounded shadow">
    <?php if (!$logs): ?>
      <p>No time logs yet.</p>
    <?php else: ?>
      <table class="min-w-full border">
        <thead>
          <tr>
            <th class="border-b px-4 py-2">Client</th>
            <th class="border-b px-4 py-2">Description</th>
            <th class="border-b px-4 py-2">Hours</th>
            <th class="border-b px-4 py-2">Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($logs as $lg): ?>
            <tr>
              <td class="border-b px-4 py-2"><?= htmlspecialchars($lg['client_name']) ?></td>
              <td class="border-b px-4 py-2"><?= htmlspecialchars($lg['description']) ?></td>
              <td class="border-b px-4 py-2"><?= $lg['hours'] ?></td>
              <td class="border-b px-4 py-2"><?= $lg['log_date'] ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</body>
</html>
