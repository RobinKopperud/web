<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require '../db.php'; // Adjusted path: one directory up from /Web/parkering/

$result = $conn->query("SELECT facility_id, name, type, lat, lng FROM facilities");
if ($result === false) {
    echo json_encode(['error' => 'Query failed: ' . $conn->error]);
    exit;
}

$facilities = [];
while ($row = $result->fetch_assoc()) {
    $facilities[] = $row;
}
echo json_encode($facilities);
$conn->close();
?>