<?php
define('APP_INIT', true);
include_once '../../../db.php';  // Database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $destinationId = $_POST['destination_id'];

    // Update the vote count for the destination
    $query = "UPDATE destinations SET votes = votes + 1 WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $destinationId);
    $stmt->execute();
    $stmt->close();
}

// Redirect back to the home page
header('Location: index.php');
exit;
?>
