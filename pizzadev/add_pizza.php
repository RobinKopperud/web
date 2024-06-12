<?php
// add_pizza.php

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
if (isset($_POST['section']) && isset($_POST['title']) && isset($_POST['price']) && isset($_POST['description'])) {
    $section = $_POST['section'];
    $title = $_POST['title'];
    $price = $_POST['price'];
    $description = $_POST['description'];

    // Prepare an SQL statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO `pizza` (`section`, `title`, `price`, `description`) VALUES (?, ?, ?, ?)");
    if ($stmt === false) {
        log_error('Prepare failed: ' . htmlspecialchars($conn->error));
        redirect_with_message('Prepare failed: ' . htmlspecialchars($conn->error));
    }

    $bind = $stmt->bind_param("ssss", $section, $title, $price, $description);
    if ($bind === false) {
        log_error('Bind failed: ' . htmlspecialchars($stmt->error));
        redirect_with_message('Bind failed: ' . htmlspecialchars($stmt->error));
    }

    $exec = $stmt->execute();
    if ($exec) {
        redirect_with_message("New record created successfully");
    } else {
        log_error('Execute failed: ' . htmlspecialchars($stmt->error));
        redirect_with_message('Execute failed: ' . htmlspecialchars($stmt->error));
    }

    $stmt->close();
    $conn->close();
} else {
    log_error('Error: Invalid input');
    redirect_with_message("Error: Invalid input");
}
?>
