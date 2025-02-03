<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin_role(); // Only admins can access this page
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$error_message = '';
$success_message = '';
$username = ''; // Initialize for clearing form field
$email = '';      // Initialize for clearing form field
$first_name = ''; // Initialize for clearing form field
$last_name = '';  // Initialize for clearing form field
$department = ''; // Initialize for clearing form field
$role = 'employee'; // Default role

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process form submission
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password']; // **Important: Handle password securely**
    $email = sanitize_input($_POST['email']);
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $department = sanitize_input($_POST['department']);
    $role = sanitize_input($_POST['role']); // Employee or Admin role

    // **Password Hashing - Crucial Step**
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $conn = connect_db();
    $stmt = $conn->prepare("INSERT INTO users (username, password_hash, email, role, first_name, last_name, department) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $username, $hashed_password, $email, $role, $first_name, $last_name, $department);

    if ($stmt->execute()) {
        $success_message = "User created successfully!";
        // Clear form fields after success
        $username = '';
        $email = '';
        $first_name = '';
        $last_name = '';
        $department = '';
        $role = 'employee'; // Reset role to default
    } else {
        $error_message = "Error creating user: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New User - Admin Dashboard - Gommer Patton Law Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body class="bg-gray-100">
    <?php include '../includes/header.php'; // If you create a header.php ?>

    <div class="container mx-auto px-4 py-8">
        <h2 class="text-2xl font-semibold mb-4">Admin Dashboard - Create New User</h2>

        <?php if ($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline"><?php echo $error_message; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Success!</strong>
                <span class="block sm:inline"><?php echo $success_message; ?></span>
            </div>
        <?php endif; ?>

        <form method="post" action="users_create.php" class="max-w-md bg-white p-6 rounded-md shadow-md">
            <div class="mb-4">
                <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Username:</label>
                <input type="text" id="username" name="username" class="shadow appearance-none border rounded-md w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline border-gray-300 focus:border-blue-500" value="<?php echo htmlspecialchars($username); ?>" required>
            </div>
            <div class="mb-4">
                <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password:</label>
                <input type="password" id="password" name="password" class="shadow appearance-none border rounded-md w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline border-gray-300 focus:border-blue-500" value="">
            </div>
            <div class="mb-4">
                <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email:</label>
                <input type="email" id="email" name="email" class="shadow appearance-none border rounded-md w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline border-gray-300 focus:border-blue-500" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            <div class="mb-4">
                <label for="first_name" class="block text-gray-700 text-sm font-bold mb-2">First Name:</label>
                <input type="text" id="first_name" name="first_name" class="shadow appearance-none border rounded-md w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline border-gray-300 focus:border-blue-500" value="<?php echo htmlspecialchars($first_name); ?>">
            </div>
            <div class="mb-4">
                <label for="last_name" class="block text-gray-700 text-sm font-bold mb-2">Last Name:</label>
                <input type="text" id="last_name" name="last_name" class="shadow appearance-none border rounded-md w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline border-gray-300 focus:border-blue-500" value="<?php echo htmlspecialchars($last_name); ?>">
            </div>
            <div class="mb-4">
                <label for="department" class="block text-gray-700 text-sm font-bold mb-2">Department:</label>
                <input type="text" id="department" name="department" class="shadow appearance-none border rounded-md w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline border-gray-300 focus:border-blue-500" value="<?php echo htmlspecialchars($department); ?>">
            </div>
            <div class="mb-4">
                <label for="role" class="block text-gray-700 text-sm font-bold mb-2">Role:</label>
                <select id="role" name="role" class="shadow appearance-none border rounded-md w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline border-gray-300 focus:border-blue-500">
                    <option value="employee" <?php if ($role === 'employee') echo 'selected'; ?>>Employee</option>
                    <option value="admin" <?php if ($role === 'admin') echo 'selected'; ?>>Admin</option>
                </select>
            </div>
            <div class="flex items-center justify-between mb-4">
                <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md focus:outline-none focus:shadow-outline" type="submit">
                    Create User
                </button>
            </div>
             <div>
                <a href="dashboard.php" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
                    Back to Admin Dashboard
                </a>
            </div>
        </form>
    </div>

    <?php include '../includes/footer.php'; // If you create a footer.php ?>
</body>
</html>