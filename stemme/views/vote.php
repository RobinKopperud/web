<?php
define('APP_INIT', true);
include_once '../../../db.php';  // Database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $destinationId = $_POST['destination_id'];

    // Check if the user has already voted for this destination using a cookie
    if (isset($_COOKIE['voted_' . $destinationId])) {
        // User has already voted, redirect back with an error message
        header('Location: index.php?vote_error=already_voted');
        exit;
    }

    // Update the vote count in the database
    $query = "UPDATE destinations SET votes = votes + 1 WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $destinationId);
    $stmt->execute();
    $stmt->close();

    // Set a cookie to track that this user has voted for this destination
    setcookie('voted_' . $destinationId, true, time() + (86400 * 2), "/");  // 86400 seconds = 1 day, so 86400 * 2 = 2 days

    // Redirect back to the home page
    header('Location: index.php');
    exit;
}
?>
