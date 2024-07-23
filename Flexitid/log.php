<?php
session_start();
include_once '../../db.php'; // Adjust the path as needed

if (!isset($_SESSION['user_id'])) {
    die("Not authenticated");
}

$logType = $_POST['logType'];
$userId = $_SESSION['user_id'];

$sql = "INSERT INTO logs (user_id, log_type) VALUES ('$userId', '$logType')";

if ($conn->query($sql) === TRUE) {
    echo "New log entry created successfully";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>
