<?php
// includes/auth.php

session_start(); // Start session on each page that uses auth

function login_user($username, $password) {
    global $conn; // Assuming $conn is your database connection

    $username = sanitize_input($username); // Sanitize input
    $stmt = $conn->prepare("SELECT user_id, username, password_hash, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password_hash'])) {
            // Password is correct, set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];

            // Update last login time
            $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $update_stmt->bind_param("i", $user['user_id']);
            $update_stmt->execute();

            return true; // Login successful
        }
    }
    return false; // Login failed
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function get_logged_in_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function get_logged_in_user_role() {
    return $_SESSION['user_role'] ?? null;
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: /login.php'); // Redirect to login page (create login.php later)
        exit;
    }
}

function require_admin_role() {
    require_login();
    if (get_logged_in_user_role() !== 'admin') {
        // Optionally redirect to an "unauthorized" page or display an error
        die("Unauthorized access.");
    }
}

function logout_user() {
    session_destroy();
    header('Location: /login.php'); // Redirect to login page after logout
    exit;
}
?>