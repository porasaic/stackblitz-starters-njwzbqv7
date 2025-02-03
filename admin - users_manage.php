<?php
// admin/users_manage.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin_role();

$search = sanitize_input($_GET['search'] ?? '');
$filter_role = sanitize_input($_GET['role'] ?? '');

// If user performed an action (suspend, activate, etc.)
if (isset($_GET['action']) && isset($_GET['user_id'])) {
    $action = $_GET['action'];
    $user_id = (int)$_GET['user_id'];

    if ($action === 'suspend') {
        $stmt = $conn->prepare("UPDATE users SET status='suspended' WHERE user_id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    } elseif ($action === 'activate') {
        $stmt = $conn->prepare("UPDATE users SET status='active' WHERE user_id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }
    header('Location: /admin/users_manage.php');
    exit;
}

// Building a dynamic query for search/filter
$sql = "SELECT * FROM users WHERE 1=1";
$params = [];
$types  = '';

// Filter by search
if ($search) {
    $sql .= " AND (username LIKE ? OR full_name LIKE ? OR email LIKE ?)";
    $searchLike = "%{$search}%";
    $params[] = &$searchLike;
    $params[] = &$searchLike;
    $params[] = &$searchLike;
    $types .= 'sss';
}

// Filter by role
if ($filter_role && $filter_role !== 'all') {
    $sql .= " AND role = ?";
    $params[] = &$filter_role;
    $types .= 's';
}

$sql .= " ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);

if ($types) {
    // Bind dynamic params
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);

// If editing a user
$editUser = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt2 = $conn->prepare("SELECT * FROM users WHERE user_id=?");
    $stmt2->bind_param("i", $editId);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    $editUser = $res2->fetch_assoc();
}

// If form is submitted to update user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $editing_user_id = (int)$_POST['user_id'];
    $full_name = sanitize_input($_POST['full_name'] ?? '');
    $email     = sanitize_input($_POST['email'] ?? '');
    $department = sanitize_input($_POST['department'] ?? '');
    $role      = sanitize_input($_POST['role'] ?? 'staff');

    $updateStmt = $conn->prepare("
        UPDATE users
        SET full_name=?, email=?, department=?, role=?
        WHERE user_id=?
    ");
    $updateStmt->bind_param("ssssi", $full_name, $email, $department, $role, $editing_user_id);
    $updateStmt->execute();
    header('Location: /admin/users_manage.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Users - Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
<div class="container mx-auto px-4 py-8">
  <h1 class="text-2xl font-bold mb-4">Manage Users</h1>
  <a href="/admin/dashboard.php" class="text-blue-600 hover:underline">← Back to Dashboard</a>

  <!-- Search & Filter Form -->
  <form method="get" class="my-4 flex flex-wrap gap-2">
    <input 
      type="text" 
      name="search"
      placeholder="Search by username, name, or email"
      value="<?= htmlspecialchars($search) ?>"
      class="border p-2 rounded"
    >
    <select name="role" class="border p-2 rounded">
      <option value="all">All Roles</option>
      <option value="staff"     <?= ($filter_role==='staff')?'selected':'' ?>>Staff</option>
      <option value="paralegal" <?= ($filter_role==='paralegal')?'selected':'' ?>>Paralegal</option>
      <option value="attorney"  <?= ($filter_role==='attorney')?'selected':'' ?>>Attorney</option>
      <option value="admin"     <?= ($filter_role==='admin')?'selected':'' ?>>Admin</option>
    </select>
    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
      Search
    </button>
  </form>

  <!-- Editing User (Inline Form) -->
  <?php if ($editUser): ?>
    <div class="bg-white p-4 rounded shadow mb-6">
      <h2 class="text-xl font-semibold mb-2">Edit User (ID: <?= $editUser['user_id'] ?>)</h2>
      <form method="post">
        <input type="hidden" name="edit_user" value="1">
        <input type="hidden" name="user_id" value="<?= $editUser['user_id'] ?>">

        <div class="mb-4">
          <label class="block font-medium" for="full_name">Full Name</label>
          <input 
            type="text" 
            name="full_name" 
            id="full_name"
            class="border rounded w-full p-2"
            value="<?= htmlspecialchars($editUser['full_name']) ?>"
          >
        </div>
        <div class="mb-4">
          <label class="block font-medium" for="email">Email</label>
          <input 
            type="email" 
            name="email" 
            id="email"
            class="border rounded w-full p-2"
            value="<?= htmlspecialchars($editUser['email']) ?>"
          >
        </div>
        <div class="mb-4">
          <label class="block font-medium" for="department">Department</label>
          <input 
            type="text" 
            name="department" 
            id="department"
            class="border rounded w-full p-2"
            value="<?= htmlspecialchars($editUser['department']) ?>"
          >
        </div>
        <div class="mb-4">
          <label class="block font-medium" for="role">Role</label>
          <select name="role" id="role" class="border rounded w-full p-2">
            <?php
              $roles = ['staff','paralegal','attorney','admin'];
              foreach($roles as $r):
            ?>
              <option value="<?= $r ?>" <?= ($editUser['role'] === $r) ? 'selected' : '' ?>>
                <?= ucfirst($r) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <button 
          type="submit"
          class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700"
        >
          Save Changes
        </button>
      </form>
    </div>
  <?php endif; ?>

  <!-- User List Table -->
  <table class="min-w-full bg-white border">
    <thead>
      <tr>
        <th class="px-4 py-2 border-b">ID</th>
        <th class="px-4 py-2 border-b">Username</th>
        <th class="px-4 py-2 border-b">Full Name</th>
        <th class="px-4 py-2 border-b">Email</th>
        <th class="px-4 py-2 border-b">Role</th>
        <th class="px-4 py-2 border-b">Status</th>
        <th class="px-4 py-2 border-b">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$users): ?>
        <tr><td colspan="7" class="px-4 py-2">No users found.</td></tr>
      <?php else: ?>
        <?php foreach ($users as $u): ?>
          <tr>
            <td class="border-b px-4 py-2"><?= $u['user_id'] ?></td>
            <td class="border-b px-4 py-2"><?= htmlspecialchars($u['username']) ?></td>
            <td class="border-b px-4 py-2"><?= htmlspecialchars($u['full_name'] ?? '') ?></td>
            <td class="border-b px-4 py-2"><?= htmlspecialchars($u['email'] ?? '') ?></td>
            <td class="border-b px-4 py-2"><?= htmlspecialchars($u['role']) ?></td>
            <td class="border-b px-4 py-2"><?= htmlspecialchars($u['status'] ?? 'active') ?></td>
            <td class="border-b px-4 py-2">
              <a href="?edit=<?= $u['user_id'] ?>" 
                 class="text-blue-600 hover:underline mr-2">
                 Edit
              </a>
              <?php if (($u['status'] ?? 'active') !== 'suspended'): ?>
                <a href="?action=suspend&user_id=<?= $u['user_id'] ?>"
                   class="text-red-600 hover:underline mr-2"
                   onclick="return confirm('Suspend this user?')">
                   Suspend
                </a>
              <?php else: ?>
                <a href="?action=activate&user_id=<?= $u['user_id'] ?>"
                   class="text-green-600 hover:underline mr-2"
                   onclick="return confirm('Activate this user?')">
                   Activate
                </a>
              <?php endif; ?>
              <a href="/admin/impersonate.php?user_id=<?= $u['user_id'] ?>"
                 class="text-purple-600 hover:underline"
                 onclick="return confirm('Impersonate this user?')">
                 Impersonate
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
</body>
</html>
