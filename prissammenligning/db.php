<?php
// db.php
// Minimal MySQL-tilkobling brukt av alle sidene i MVP-en.

$DB_HOST = getenv('DB_HOST') ?: '127.0.0.1';
$DB_PORT = getenv('DB_PORT') ?: '3306';
$DB_NAME = getenv('DB_NAME') ?: 'offers_mvp';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '';

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, (int) $DB_PORT);

if ($mysqli->connect_error) {
    die('Kunne ikke koble til databasen: ' . $mysqli->connect_error);
}

$mysqli->set_charset('utf8mb4');
