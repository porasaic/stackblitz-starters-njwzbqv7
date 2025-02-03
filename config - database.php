<?php
// config/database.php

define('DB_HOST', 'localhost');  // Use 'localhost' for phpMyAdmin on Hostinger
define('DB_USER', 'u704565744_001');  // Your database username from hPanel
define('DB_PASS', 'ItsTime@20..');  // Your database password
define('DB_NAME', 'u704565744_01');  // Your database name
define('DB_PORT', 3306);  // Default MySQL port

function connect_db() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }
    return $conn;
}

// Test the connection
$conn = connect_db();
?>
