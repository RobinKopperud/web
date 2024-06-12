<?php
// add_pizza.php

include_once dirname(__FILE__, 2) . '/db.php';

// Function to log errors
function log_error($message) {
    $log_file = dirname(__FILE__) . '/error_log.txt';
    error_log($message . "\n", 3, $log_file);
}

// Check if the POST request contains the necessary data
if (isset($_POST['title']) && isset($_POST['price']) && isset($_POST['description'])) {
    $title = $_POST['title'];
    $price = $_POST['price'];
    $description = $_POST['description'];

    // Prepare an SQL statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO `pizza` (`title`, `price`, `description`) VALUES (?, ?, ?)");
    if ($stmt === false) {
        log_error('Prepare failed: ' . htmlspecialchars($conn->error));
        die('Prepare failed: ' . htmlspecialchars($conn->error));
    }

    $bind = $stmt->bind_param("sss", $title, $price, $description);
    if ($bind === false) {
        log_error('Bind failed: ' . htmlspecialchars($stmt->error));
        die('Bind failed: ' . htmlspecialchars($stmt->error));
    }

    $exec = $stmt->execute();
    if ($exec) {
        echo "New record created successfully";
    } else {
        log_error('Execute failed: ' . htmlspecialchars($stmt->error));
        echo "Execute failed: " . htmlspecialchars($stmt->error);
    }

    $stmt->close();
    $conn->close();
} else {
    log_error('Error: Invalid input');
    echo "Error: Invalid input";
}
?>
