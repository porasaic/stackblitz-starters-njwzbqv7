<?php
// admin/virtual_office_manage.php
require_once __DIR__ . '/../includes/auth.php';
require_admin_role();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Handle form submissions
$errors = [];
$successMessage = '';

// 1) CREATE / UPDATE / LOCK Channels
if (isset($_POST['action']) && $_POST['action'] === 'manage_channels') {
    $chanName = trim($_POST['channel_name'] ?? '');
    $chanID   = (int)($_POST['channel_id'] ?? 0);
    $isLocked = isset($_POST['is_locked']) ? 1 : 0;
    $submitType = $_POST['submitType'] ?? 'create';

    if ($submitType === 'create') {
        if (!$chanName) {
            $errors[] = "Channel name is required.";
        } else {
            $stmt = $conn->prepare("INSERT INTO chat_channels (channel_name, is_locked) VALUES (?, ?)");
            $stmt->bind_param("si", $chanName, $isLocked);
            if ($stmt->execute()) {
                $successMessage = "Channel '$chanName' created successfully!";
            } else {
                $errors[] = "DB Error: " . $conn->error;
            }
            $stmt->close();
        }
    }
    elseif ($submitType === 'update' && $chanID > 0) {
        // Update channel name and/or lock status
        $stmt = $conn->prepare("UPDATE chat_channels SET channel_name=?, is_locked=? WHERE channel_id=?");
        $stmt->bind_param("sii", $chanName, $isLocked, $chanID);
        if ($stmt->execute()) {
            $successMessage = "Channel #$chanID updated!";
        } else {
            $errors[] = "DB Error: " . $conn->error;
        }
        $stmt->close();
    }
    elseif ($submitType === 'delete' && $chanID > 0) {
        // Deleting a channel
        $stmt = $conn->prepare("DELETE FROM chat_channels WHERE channel_id=?");
        $stmt->bind_param("i", $chanID);
        if ($stmt->execute()) {
            $successMessage = "Channel #$chanID deleted.";
        } else {
            $errors[] = "DB Error: " . $conn->error;
        }
        $stmt->close();
    }
}

// 2) POST MESSAGE AS ANY USER
if (isset($_POST['action']) && $_POST['action'] === 'post_message') {
    $channel_id   = (int)($_POST['channel_id'] ?? 0);
    $user_id      = (int)($_POST['user_id'] ?? 0);
    $message_text = trim($_POST['message_text'] ?? '');
    $customTime   = trim($_POST['custom_created_at'] ?? ''); // optional backdate

    if (!$channel_id || !$user_id || !$message_text) {
        $errors[] = "Please specify channel, user, and a message.";
    } else {
        // Insert with custom 'created_at' if provided
        if ($customTime) {
            $stmt = $conn->prepare("
              INSERT INTO chat_messages (channel_id, user_id, message_text, created_at)
              VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("iiss", $channel_id, $user_id, $message_text, $customTime);
        } else {
            // normal: let DB set current_timestamp
            $stmt = $conn->prepare("
              INSERT INTO chat_messages (channel_id, user_id, message_text, created_at)
              VALUES (?, ?, ?, NOW())
            ");
            $stmt->bind_param("iis", $channel_id, $user_id, $message_text);
        }
        if ($stmt->execute()) {
            $successMessage = "Message posted to channel #$channel_id as user #$user_id.";
        } else {
            $errors[] = "DB Error: " . $conn->error;
        }
        $stmt->close();
    }
}

// GET DATA for displaying
// fetch channels
$cRes = $conn->query("SELECT channel_id, channel_name, is_locked FROM chat_channels ORDER BY channel_id ASC");
$channels = $cRes ? $cRes->fetch_all(MYSQLI_ASSOC) : [];

// fetch users
$uRes = $conn->query("SELECT user_id, username FROM users ORDER BY username ASC");
$allUsers = $uRes ? $uRes->fetch_all(MYSQLI_ASSOC) : [];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Virtual Office Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
<div class="container mx-auto px-4 py-8">
  <h1 class="text-3xl font-bold mb-4">Virtual Office Control Panel</h1>
  <p class="text-sm text-gray-600 mb-6">
    Logged in as: <span class="font-medium text-blue-700">
    <?= htmlspecialchars($_SESSION['username']); ?></span> (Role: 
    <span class="font-medium text-blue-700">
    <?= htmlspecialchars($_SESSION['user_role']); ?>
    </span>)
  </p>

  <?php if ($errors): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
      <ul class="list-disc list-inside">
        <?php foreach($errors as $err): ?>
          <li><?= htmlspecialchars($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php if ($successMessage): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
      <?= htmlspecialchars($successMessage) ?>
    </div>
  <?php endif; ?>

  <div class="grid md:grid-cols-2 gap-6">
    
    <!-- SECTION 1: CHANNELS -->
    <div class="bg-white p-4 rounded shadow">
      <h2 class="text-xl font-semibold mb-3">Manage Channels</h2>
      <!-- List existing channels -->
      <?php if ($channels): ?>
        <table class="min-w-full border text-sm mb-4">
          <thead>
            <tr class="bg-gray-200">
              <th class="px-4 py-2 border-b">ID</th>
              <th class="px-4 py-2 border-b">Name</th>
              <th class="px-4 py-2 border-b">Locked?</th>
              <th class="px-4 py-2 border-b">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($channels as $ch): ?>
              <tr>
                <td class="border-b px-4 py-2"><?= $ch['channel_id'] ?></td>
                <td class="border-b px-4 py-2"><?= htmlspecialchars($ch['channel_name']) ?></td>
                <td class="border-b px-4 py-2">
                  <?= $ch['is_locked'] ? 'Yes' : 'No' ?>
                </td>
                <td class="border-b px-4 py-2">
                  <form method="post" class="inline-block">
                    <input type="hidden" name="action" value="manage_channels"/>
                    <input type="hidden" name="channel_id" value="<?= $ch['channel_id'] ?>"/>
                    <input type="hidden" name="channel_name" value="<?= htmlspecialchars($ch['channel_name']) ?>"/>
                    <input type="hidden" name="is_locked" value="<?= $ch['is_locked'] ?>"/>
                    <input type="hidden" name="submitType" value="update"/>
                    <button type="submit" class="text-blue-600 hover:underline mr-3">
                      Toggle Edit
                    </button>
                  </form>
                  <form method="post" class="inline-block" onsubmit="return confirm('Delete channel #<?= $ch['channel_id'] ?>?');">
                    <input type="hidden" name="action" value="manage_channels"/>
                    <input type="hidden" name="submitType" value="delete"/>
                    <input type="hidden" name="channel_id" value="<?= $ch['channel_id'] ?>"/>
                    <button class="text-red-600 hover:underline">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p class="text-sm text-gray-600 mb-4">No channels found.</p>
      <?php endif; ?>
      
      <!-- Create/Update channel form -->
      <form method="post" class="border p-3 rounded">
        <input type="hidden" name="action" value="manage_channels"/>
        <?php
          // If we posted an "update" to a channel, re-populate fields
          $editChanID   = 0;
          $editChanName = '';
          $editLocked   = 0;
          if (isset($_POST['action'], $_POST['submitType']) 
              && $_POST['action']==='manage_channels'
              && $_POST['submitType']==='update'
          ) {
              $editChanID   = (int)$_POST['channel_id'];
              $editChanName = $_POST['channel_name'] ?? '';
              $editLocked   = isset($_POST['is_locked']) ? (int)$_POST['is_locked'] : 0;
          }
        ?>
        <?php if ($editChanID > 0): ?>
          <input type="hidden" name="channel_id" value="<?= $editChanID ?>"/>
          <input type="hidden" name="submitType" value="update"/>
          <label class="block mb-2">
            <span class="text-sm font-medium">Channel Name</span>
            <input type="text" name="channel_name" value="<?= htmlspecialchars($editChanName) ?>"
                   class="border rounded w-full p-2" required>
          </label>
          <label class="block mb-2">
            <input type="checkbox" name="is_locked" value="1" 
              <?= ($editLocked ? 'checked' : '') ?>
            >
            <span class="text-sm font-medium">Locked?</span>
          </label>
          <button type="submit" class="bg-blue-600 text-white px-3 py-1 rounded">Update Channel</button>
        <?php else: ?>
          <input type="hidden" name="submitType" value="create"/>
          <label class="block mb-2">
            <span class="text-sm font-medium">New Channel Name</span>
            <input type="text" name="channel_name" class="border rounded w-full p-2" placeholder="#my-channel" required/>
          </label>
          <label class="block mb-2">
            <input type="checkbox" name="is_locked" value="1">
            <span class="text-sm font-medium">Lock Channel?</span>
          </label>
          <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded">Create Channel</button>
        <?php endif; ?>
      </form>
    </div>

    <!-- SECTION 2: POST AS ANY USER -->
    <div class="bg-white p-4 rounded shadow">
      <h2 class="text-xl font-semibold mb-3">Post Message as Any User</h2>
      <form method="post" class="space-y-3">
        <input type="hidden" name="action" value="post_message"/>
        <div>
          <label class="block text-sm font-medium mb-1" for="channel_id">Select Channel</label>
          <select name="channel_id" id="channel_id" class="border rounded w-full p-2">
            <?php foreach($channels as $ch): ?>
              <option value="<?= $ch['channel_id'] ?>">
                #<?= htmlspecialchars($ch['channel_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1" for="user_id">Send As User</label>
          <select name="user_id" id="user_id" class="border rounded w-full p-2">
            <?php foreach($allUsers as $u): ?>
              <option value="<?= $u['user_id'] ?>">
                <?= htmlspecialchars($u['username']) ?> (ID: <?= $u['user_id'] ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1" for="message_text">Message Text</label>
          <textarea name="message_text" id="message_text" rows="3" class="border rounded w-full p-2" required></textarea>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1" for="custom_created_at">Backdate Timestamp? (Optional)</label>
          <input type="datetime-local" name="custom_created_at" id="custom_created_at" 
                 class="border rounded w-full p-2 text-sm"
                 placeholder="2025-02-01 09:30:00"/>
          <p class="text-xs text-gray-500">
            Leave blank to use current time. Otherwise, pick a date/time to simulate older messages.
          </p>
        </div>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
          Post Message
        </button>
      </form>
    </div>

  </div><!-- end grid -->

  <!-- Optional: Impersonation or other advanced controls could also go here. -->
</div>
</body>
</html>
