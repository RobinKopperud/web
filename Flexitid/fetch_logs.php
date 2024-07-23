<?php
session_start();
include_once '../../db.php'; // Adjust the path as needed

if (!isset($_SESSION['user_id'])) {
    die("Not authenticated");
}

$userId = $_SESSION['user_id'];
$sql = "SELECT log_type, log_time FROM logs WHERE user_id='$userId' ORDER BY log_time ASC";
$result = $conn->query($sql);

$logs = array();
while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}

// Check last log entry
$lastLogType = '';
if (!empty($logs)) {
    $lastLogType = $logs[count($logs) - 1]['log_type'];
}

$conn->close();

echo json_encode(['logs' => $logs, 'lastLogType' => $lastLogType]);
?>
