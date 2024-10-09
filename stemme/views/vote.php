<?php
include_once '../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $destinationId = $_POST['destination_id'];
    $query = "UPDATE destinations SET votes = votes + 1 WHERE id = $destinationId";
    if ($conn->query($query) === TRUE) {
        header('Location: index.php');
    } else {
        echo "Error updating record: " . $conn->error;
    }
}
?>
