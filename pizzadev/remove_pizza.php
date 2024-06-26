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

// Function to renumber pizzas
function renumber_pizzas($conn) {
    // First pass: Assign temporary IDs to avoid conflicts
    $sql = "SELECT id FROM pizza ORDER BY FIELD(section, 'pizza', 'kebab', 'grill'), id ASC";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $temp_id = 1000;
        while ($row = $result->fetch_assoc()) {
            $update_sql = "UPDATE pizza SET id = ? WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ii", $temp_id, $row['id']);
            $stmt->execute();
            $temp_id++;
        }
    }

    // Second pass: Assign new sequential IDs
    $sql = "SELECT id FROM pizza ORDER BY FIELD(section, 'pizza', 'kebab', 'grill'), id ASC";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $number = 1;
        while ($row = $result->fetch_assoc()) {
            $update_sql = "UPDATE pizza SET id = ? WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ii", $number, $row['id']);
            $stmt->execute();
            $number++;
        }
    }
}

// Check if the POST request contains the necessary data
if (isset($_POST['removeNumber'])) {
    $id = intval($_POST['removeNumber']);

    // Prepare an SQL statement to delete the pizza
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
        // Renumber pizzas after removing the pizza
        renumber_pizzas($conn);
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
