<?php
session_start();

$servername = "localhost";
$username = "jmntxjwa_AI";
$password = "ai-Admin";
$dbname = "jmntxjwa_users";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = htmlspecialchars($_POST['username']);
    $score = intval($_POST['score']);

    $stmt = $conn->prepare("INSERT INTO highscores (username, score) VALUES (?, ?)");
    $stmt->bind_param("si", $username, $score);

    if ($stmt->execute()) {
        echo "Score submitted successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
