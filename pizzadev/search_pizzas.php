<?php
include_once '../../db.php'; // Ensure this path is correct based on your directory structure

// Fetch dishes based on search query
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

$sql = "SELECT * FROM pizza WHERE title LIKE '%$search%' OR description LIKE '%$search%'";
$result = $conn->query($sql);

$dishes = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $dishes[] = $row;
    }
}

$conn->close();
?>
