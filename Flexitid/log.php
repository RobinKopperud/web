<?php
session_start();
include_once '../../db.php'; // Juster stien etter behov

if (!isset($_SESSION['user_id'])) {
    die("Ikke autentisert");
}

$logType = $_POST['logType'];
$userId = $_SESSION['user_id'];
$logTime = date("Y-m-d H:i:s");

$sql = "INSERT INTO logs (user_id, log_type, log_time) VALUES ('$userId', '$logType', '$logTime')";

if ($conn->query($sql) === TRUE) {
    echo json_encode(['success' => true, 'logTime' => $logTime]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}

$conn->close();
?>
