<?php
// admin/onboarding_tasks_manage.php
// Manages the "onboarding_tasks" table and basic assignment logic.

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin_role();

$errors = [];
$successMessage = '';

// Handle form submission for creating/updating tasks
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_name = sanitize_input($_POST['task_name'] ?? '');
    $task_desc = sanitize_input($_POST['task_description'] ?? '');
    $role_req  = sanitize_input($_POST['role_required'] ?? 'staff');
    $task_id   = intval($_POST['task_id'] ?? 0);
    $action    = $_POST['action'] ?? 'create';

    if (!$task_name) {
        $errors[] = 'Task Name is required.';
    }

    if (!$errors) {
        if ($action === 'create') {
            // Insert new task
            $stmt = $conn->prepare("INSERT INTO onboarding_tasks (task_name, task_description, role_required) 
                                    VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $task_name, $task_desc, $role_req);
            if ($stmt->execute()) {
                $successMessage = 'New task created successfully.';
            } else {
                $errors[] = 'Database error: ' . $conn->error;
            }
        } elseif ($action === 'update' && $task_id > 0) {
            // Update existing task
            $stmt = $conn->prepare("UPDATE onboarding_tasks 
                                    SET task_name=?, task_description=?, role_required=? 
                                    WHERE task_id=?");
            $stmt->bind_param("sssi", $task_name, $task_desc, $role_req, $task_id);
            if ($stmt->execute()) {
                $successMessage = 'Task updated successfully.';
            } else {
                $errors[] = 'Database error: ' . $conn->error;
            }
        }
    }
}

// Handle delete action
if (isset($_GET['delete_task_id'])) {
    $delete_id = intval($_GET['delete_task_id']);
    if ($delete_id > 0) {
        $delStmt = $conn->prepare("DELETE FROM onboarding_tasks WHERE task_id=?");
        $delStmt->bind_param("i", $delete_id);
        if ($delStmt->execute()) {
            $successMessage = 'Task deleted successfully.';
        } else {
            $errors[] = 'Could not delete task: ' . $conn->error;
        }
    }
}

// Retrieve tasks to display
$taskRes = $conn->query("SELECT * FROM onboarding_tasks ORDER BY created_at DESC");
$tasks = $taskRes->fetch_all(MYSQLI_ASSOC);

// If editing a task, fetch it
$editTask = null;
if (isset($_GET['edit_task_id'])) {
    $edit_id = intval($_GET['edit_task_id']);
    $stmt = $conn->prepare("SELECT * FROM onboarding_tasks WHERE task_id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $editTask = $res->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Onboarding Tasks Management</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-4">Onboarding Tasks Management</h1>
    <a href="/admin/dashboard.php" class="text-blue-600 hover:underline mb-4 inline-block">
        ← Back to Admin Dashboard
    </a>

    <?php if ($errors): ?>
        <div class="bg-red-100 text-red-700 border border-red-400 p-4 my-4 rounded">
            <ul>
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <?php if ($successMessage): ?>
        <div class="bg-green-100 text-green-700 border border-green-400 p-4 my-4 rounded">
            <?= htmlspecialchars($successMessage) ?>
        </div>
    <?php endif; ?>

    <!-- Task Form (Create/Update) -->
    <div class="bg-white p-4 rounded shadow mb-8 w-full max-w-lg">
        <?php if ($editTask): ?>
            <h2 class="text-xl font-semibold mb-2">Edit Task (ID: <?= $editTask['task_id'] ?>)</h2>
        <?php else: ?>
            <h2 class="text-xl font-semibold mb-2">Create New Onboarding Task</h2>
        <?php endif; ?>
        <form action="" method="post">
            <input type="hidden" name="task_id" value="<?= $editTask['task_id'] ?? 0 ?>">
            <input type="hidden" name="action" value="<?= $editTask ? 'update' : 'create' ?>">

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1" for="task_name">Task Name</label>
                <input 
                    type="text" 
                    name="task_name" 
                    id="task_name" 
                    class="border rounded w-full p-2"
                    value="<?= htmlspecialchars($editTask['task_name'] ?? '') ?>"
                    required
                >
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1" for="task_description">Task Description</label>
                <textarea 
                    name="task_description" 
                    id="task_description" 
                    class="border rounded w-full p-2"
                ><?= htmlspecialchars($editTask['task_description'] ?? '') ?></textarea>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1" for="role_required">Role Required</label>
                <select 
                    name="role_required" 
                    id="role_required" 
                    class="border rounded w-full p-2"
                >
                    <?php
                    $roles = ['staff','paralegal','attorney','admin'];
                    $current = $editTask['role_required'] ?? 'staff';
                    foreach ($roles as $r):
                    ?>
                        <option value="<?= $r ?>" <?= ($r == $current) ? 'selected' : '' ?>>
                            <?= ucfirst($r) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button 
                type="submit" 
                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
            >
                <?= $editTask ? 'Update Task' : 'Create Task' ?>
            </button>
        </form>
    </div>

    <!-- Task Listing -->
    <div class="bg-white p-4 rounded shadow">
        <h2 class="text-xl font-semibold mb-4">Existing Onboarding Tasks</h2>
        <?php if (!$tasks): ?>
            <p>No tasks found.</p>
        <?php else: ?>
            <table class="min-w-full border">
                <thead>
                    <tr>
                        <th class="border-b px-4 py-2">ID</th>
                        <th class="border-b px-4 py-2">Task Name</th>
                        <th class="border-b px-4 py-2">Role Required</th>
                        <th class="border-b px-4 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                  <?php foreach ($tasks as $t): ?>
                    <tr>
                        <td class="border-b px-4 py-2"><?= $t['task_id'] ?></td>
                        <td class="border-b px-4 py-2"><?= htmlspecialchars($t['task_name']) ?></td>
                        <td class="border-b px-4 py-2"><?= htmlspecialchars($t['role_required']) ?></td>
                        <td class="border-b px-4 py-2">
                            <a href="?edit_task_id=<?= $t['task_id'] ?>" 
                               class="text-blue-600 hover:underline mr-2">Edit</a>
                            <a href="?delete_task_id=<?= $t['task_id'] ?>" 
                               class="text-red-600 hover:underline"
                               onclick="return confirm('Are you sure you want to delete this task?')">
                                Delete
                            </a>
                        </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
