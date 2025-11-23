<?php
// Simple MySQL connection helper reused across apps.
// Configure credentials via environment variables or edit the defaults below.
$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_NAME = getenv('DB_NAME') ?: 'your_database_name';
$DB_USER = getenv('DB_USER') ?: 'your_database_user';
$DB_PASS = getenv('DB_PASS') ?: 'your_secure_password';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Ensure consistent encoding.
$conn->set_charset('utf8mb4');
?>
