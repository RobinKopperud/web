<?php
// add_pizza.php

include '../../db.php'; // Adjust the path as needed

// Check if the POST request contains the necessary data
if (isset($_POST['title']) && isset($_POST['price']) && isset($_POST['description'])) {
    $title = $_POST['title'];
    $price = $_POST['price'];
    $description = $_POST['description'];

    // Prepare an SQL statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO `pizza` (`title`, `price`, `description`) VALUES (?, ?, ?)");
    if ($stmt === false) {
        die('Prepare failed: ' . htmlspecialchars($conn->error));
    }

    $bind = $stmt->bind_param("sss", $title, $price, $description);
    if ($bind === false) {
        die('Bind failed: ' . htmlspecialchars($stmt->error));
    }

    $exec = $stmt->execute();
    if ($exec) {
        echo "New record created successfully";
    } else {
        echo "Execute failed: " . htmlspecialchars($stmt->error);
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Error: Invalid input";
}
?>
