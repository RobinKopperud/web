<?php
define('APP_INIT', true);
include_once '../../../includes/db.php';  // Database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $destinationName = $conn->real_escape_string($_POST['destination_name']);
    
    // Insert the new destination into the database
    $query = "INSERT INTO destinations (name, votes) VALUES (?, 0)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $destinationName);
    $stmt->execute();
    $stmt->close();
}

// Redirect back to the home page
header('Location: index.php');
exit;
?>
