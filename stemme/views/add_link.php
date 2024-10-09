<?php
define('APP_INIT', true);
include_once '../../../db.php';  // Database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $destinationId = $_POST['destination_id'];
    $url = $conn->real_escape_string($_POST['url']);
    $description = $conn->real_escape_string($_POST['description']);

    // Insert the link into the database
    $query = "INSERT INTO links (destination_id, url, description) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iss", $destinationId, $url, $description);
    $stmt->execute();
    $stmt->close();
}

// Redirect back to the destination page
header("Location: destination.php?id=$destinationId");
exit;
?>
