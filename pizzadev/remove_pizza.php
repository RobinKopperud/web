<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once '../../db.php'; // Adjust the path as needed

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
if (isset($_POST['removeNumber'])) {
    $id = intval($_POST['removeNumber']);

    // Prepare an SQL statement to delete the pizza by its id (number)
    $stmt = $conn->prepare("DELETE FROM `pizza` WHERE `id` = ?");
    if ($stmt === false) {
        log_error('Prepare failed: ' . htmlspecialchars($conn->error));
        redirect_with_message('Prepare failed: ' . htmlspecialchars($conn->error));
    }

    $bind = $stmt->bind_param("i", $id);
    if ($bind === false) {
        log_error('Bind failed: ' . htmlspecialchars($stmt->error));
        redirect_with_message('Bind failed: ' . htmlspecialchars($stmt->error));
    }

    $exec = $stmt->execute();
    if ($exec) {
        redirect_with_message("Record deleted successfully");
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
