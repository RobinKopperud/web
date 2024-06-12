<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once '../../db.php'; // Correct path to include db.php

// Function to log errors
function log_error($message) {
    $log_file = dirname(__FILE__) . '/error_log.txt';
    error_log($message . "\n", 3, $log_file);
}

// Function to redirect with a message
function redirect_with_message($message) {
    header("Location: index.php?message=" . urlencode($message));
    exit();
}

// Check if the POST request contains the necessary data
if (isset($_POST['title']) && isset($_POST['price']) && isset($_POST['description']) && isset($_POST['section'])) {
    $title = $_POST['title'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $section = $_POST['section'];

    // Prepare an SQL statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO `pizza` (`title`, `price`, `description`, `section`) VALUES (?, ?, ?, ?)");
    if ($stmt === false) {
        log_error('Prepare failed: ' . htmlspecialchars($conn->error));
        echo 'Prepare failed: ' . htmlspecialchars($conn->error);
        exit();
    }

    $bind = $stmt->bind_param("ssss", $title, $price, $description, $section);
    if ($bind === false) {
        log_error('Bind failed: ' . htmlspecialchars($stmt->error));
        echo 'Bind failed: ' . htmlspecialchars($stmt->error);
        exit();
    }

    $exec = $stmt->execute();
    if ($exec) {
        echo "New record created successfully";
    } else {
        log_error('Execute failed: ' . htmlspecialchars($stmt->error));
        echo 'Execute failed: ' . htmlspecialchars($stmt->error);
    }

    $stmt->close();
    $conn->close();
} else {
    log_error('Error: Invalid input');
    echo "Error: Invalid input";
}
?>
