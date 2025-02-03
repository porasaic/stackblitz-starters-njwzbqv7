<?php
// admin/dashboard.php
require_once __DIR__ . '/../includes/auth.php';
require_admin_role(); // Ensure only logged-in admins can access this page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Gommer Patton Law Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- If you have a custom CSS file -->
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body class="bg-gray-100">
    <?php include '../includes/header.php'; /* If you want to add a shared header */ ?>

    <div class="min-h-screen flex">
        <!-- SIDEBAR -->
        <aside class="bg-[#0C1D36] w-64 text-white flex-shrink-0 hidden md:block">
            <div class="border-b border-blue-700 p-4">
                <h2 class="text-xl font-semibold">Admin Panel</h2>
            </div>
            <nav class="flex-1 overflow-y-auto text-sm">
                <ul>
                    <li class="border-b border-gray-700">
                        <a href="/admin/dashboard.php" class="block px-6 py-3 hover:bg-[#11294a]">
                            Admin Home
                        </a>
                    </li>
                    <li class="border-b border-gray-700">
                        <a href="/admin/users_create.php" class="block px-6 py-3 hover:bg-[#11294a]">
                            Create New User
                        </a>
                    </li>
                    <li class="border-b border-gray-700">
                        <a href="/admin/users_manage.php" class="block px-6 py-3 hover:bg-[#11294a]">
                            Manage Users
                        </a>
                    </li>
                    <li class="border-b border-gray-700">
                        <a href="/admin/requests_manage.php" class="block px-6 py-3 hover:bg-[#11294a]">
                            Manage Requests
                        </a>
                    </li>
                    <li class="border-b border-gray-700">
                        <a href="/admin/onboarding_tasks_manage.php" class="block px-6 py-3 hover:bg-[#11294a]">
                            Onboarding Tasks
                        </a>
                    </li>
                    <li class="border-b border-gray-700">
                        <a href="/admin/virtual_office_manage.php" class="block px-6 py-3 hover:bg-[#11294a]">
                            Virtual Office Control
                        </a>
                    </li>
                    <li class="border-b border-gray-700">
                        <a href="/admin/mark_task_complete.php" class="block px-6 py-3 hover:bg-[#11294a]">
                            Mark Task Complete
                        </a>
                    </li>
                    <li class="border-b border-gray-700">
                        <a href="/admin/impersonate.php" class="block px-6 py-3 hover:bg-[#11294a]">
                            Impersonate User
                        </a>
                    </li>
                    <li class="border-b border-gray-700">
                        <a href="/admin/billing_reports.php" class="block px-6 py-3 hover:bg-[#11294a]">
                            Billing Reports
                        </a>
                    </li>
                    <li class="border-b border-gray-700">
                        <a href="/admin/stats_api.php" class="block px-6 py-3 hover:bg-[#11294a]">
                            Stats API (Example)
                        </a>
                    </li>
                    <li>
                        <a href="/logout.php" class="block px-6 py-3 hover:bg-[#11294a] text-red-300">
                            Logout
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <main class="flex-1">
            <div class="container mx-auto px-4 py-8">
                <h2 class="text-2xl font-semibold mb-4">Welcome to the Admin Dashboard</h2>
                <p class="mb-2">
                    Logged in as: 
                    <span class="font-medium text-blue-700">
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </span>
                    (Role: 
                    <span class="font-medium text-blue-700">
                        <?php echo htmlspecialchars($_SESSION['user_role']); ?>
                    </span>)
                </p>
                <hr class="my-4" />

                <!-- MAIN DASHBOARD CONTENT / QUICK LINKS -->
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="bg-white p-4 rounded shadow">
                        <h3 class="text-xl font-bold mb-2">Quick Actions</h3>
                        <ul class="list-disc list-inside text-sm text-gray-700 space-y-2">
                            <li>
                                <a href="/admin/users_create.php" class="text-blue-600 hover:underline">
                                    Create New User
                                </a>
                            </li>
                            <li>
                                <a href="/admin/users_manage.php" class="text-blue-600 hover:underline">
                                    Manage Users
                                </a>
                            </li>
                            <li>
                                <a href="/admin/requests_manage.php" class="text-blue-600 hover:underline">
                                    Manage Requests
                                </a>
                            </li>
                            <li>
                                <a href="/admin/onboarding_tasks_manage.php" class="text-blue-600 hover:underline">
                                    Onboarding Tasks
                                </a>
                            </li>
                            <li>
                                <a href="/admin/virtual_office_manage.php" class="text-blue-600 hover:underline">
                                    Virtual Office Control
                                </a>
                            </li>                            
                            <li>
                                <a href="/admin/impersonate.php" class="text-blue-600 hover:underline">
                                    Impersonate User
                                </a>
                            </li>
                            <li>
                                <a href="/admin/billing_reports.php" class="text-blue-600 hover:underline">
                                    Billing Reports
                                </a>
                            </li>
                            <li>
                                <a href="/admin/mark_task_complete.php" class="text-blue-600 hover:underline">
                                    Mark Task Complete
                                </a>
                            </li>
                            <li>
                                <a href="/admin/stats_api.php" class="text-blue-600 hover:underline">
                                    Stats API (Example)
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="bg-white p-4 rounded shadow">
                        <h3 class="text-xl font-bold mb-2">System Overview</h3>
                        <p class="text-sm text-gray-700 mb-2">
                            Use the links on the left or above to manage users, requests, tasks, billing, and more.
                        </p>
                        <p class="text-xs text-gray-500">
                            For advanced settings or logs, check Hostinger's hPanel or system logs.
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include '../includes/footer.php'; /* If you have a shared footer */ ?>
</body>
</html>
