<?php
session_start();
include_once '../../db.php'; // Adjust the path as needed

if (!isset($_SESSION['user_id'])) {
    die("Not authenticated");
}

$userId = $_SESSION['user_id'];
$sql = "SELECT log_type, log_time FROM logs WHERE user_id='$userId' ORDER BY log_time DESC";
$result = $conn->query($sql);

$logs = array();
while($row = $result->fetch_assoc()) {
    $logs[] = $row;
}

$conn->close();

echo json_encode($logs);
?>
