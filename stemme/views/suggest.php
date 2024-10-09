<?php
define('APP_INIT', true);
include_once '../../../includes/db.php';

header('Content-Type: application/json');

// Process the form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $destinationName = $conn->real_escape_string($_POST['destination_name']);
    $query = "INSERT INTO destinations (name, votes) VALUES (?, 0)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $destinationName);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}

$conn->close();
?>
