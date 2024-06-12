<?php
// add_pizza.php

include '../../db.php'; // Adjust the path as needed

// Check if the POST request contains the necessary data
if (isset($_POST['title']) && isset($_POST['price']) && isset($_POST['description'])) {
    $title = $_POST['title'];
    $price = $_POST['price'];
    $description = $_POST['description'];

    // Prepare an SQL statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO pizza (title, price, description) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $title, $price, $description);

    if ($stmt->execute()) {
        echo "New record created successfully";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Error: Invalid input";
}
?>
