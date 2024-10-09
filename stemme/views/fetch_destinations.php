<?php
define('APP_INIT', true);
include_once '../../../db.php';  // Database connection

header('Content-Type: application/json');

// Fetch the most upvoted destination
$topDestinationQuery = "SELECT * FROM destinations ORDER BY votes DESC LIMIT 1";
$topDestinationResult = $conn->query($topDestinationQuery);
$topDestination = $topDestinationResult->fetch_assoc();

// Fetch all destinations
$destinationsQuery = "SELECT * FROM destinations ORDER BY name ASC";
$destinationsResult = $conn->query($destinationsQuery);
$destinations = [];
while ($row = $destinationsResult->fetch_assoc()) {
    $destinations[] = $row;
}

// Return data as JSON
echo json_encode([
    'topDestination' => $topDestination,
    'destinations' => $destinations
]);

$conn->close();
?>
