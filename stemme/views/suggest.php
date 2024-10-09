<?php
include_once '../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $destinationName = $conn->real_escape_string($_POST['destination_name']);
    $query = "INSERT INTO destinations (name, votes) VALUES ('$destinationName', 0)";
    if ($conn->query($query) === TRUE) {
        header('Location: index.php');
    } else {
        echo "Error: " . $conn->error;
    }
}
?>
