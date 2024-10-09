<?php
include_once '../../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $destinationId = $_POST['destination_id'];
    $url = $conn->real_escape_string($_POST['url']);
    $description = $conn->real_escape_string($_POST['description']);
    $query = "INSERT INTO links (destination_id, url, description) VALUES ($destinationId, '$url', '$description')";
    if ($conn->query($query) === TRUE) {
        header("Location: destination.php?id=$destinationId");
    } else {
        echo "Error: " . $conn->error;
    }
}
?>
