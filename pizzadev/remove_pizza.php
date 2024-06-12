<?php
// remove_pizza.php

include_once '../../db.php'; // Correct path to include db.php

// Function to log errors
function log_error($message) {
    $log_file = dirname(__FILE__) . '/error_log.txt';
    error_log($message . "\n", 3, $log_file);
}

// Check if the POST request contains the necessary data
if (isset($_POST['removeNumber'])) {
    $number = intval($_POST['removeNumber']);

    // Prepare an SQL statement to delete the pizza by its id (number)
    $stmt = $conn->prepare("DELETE FROM `pizza` WHERE `id` = ?");
    if ($stmt === false) {
        log_error('Prepare failed: ' . htmlspecialchars($conn->error));
        die('Prepare failed: ' . htmlspecialchars($conn->error));
    }

    $bind = $stmt->bind_param("i", $number);
    if ($bind === false) {
        log_error('Bind failed: ' . htmlspecialchars($stmt->error));
        die('Bind failed: ' . htmlspecialchars($stmt->error));
    }

    $exec = $stmt->execute();
    if ($exec) {
        echo "Record deleted successfully";
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
