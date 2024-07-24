<?php
// Fetch today's work hours
$todayStart = date("Y-m-d 00:00:00");
$todayEnd = date("Y-m-d 23:59:59");

$sql = "SELECT log_type, log_time FROM logs WHERE user_id='$userId' AND log_time BETWEEN '$todayStart' AND '$todayEnd' ORDER BY log_time ASC";
$result = $conn->query($sql);

$todayMinutes = 0;
$lastLogType = '';
$lastLogTime = '';

while ($row = $result->fetch_assoc()) {
    if ($lastLogTime) {
        $diff = (strtotime($row['log_time']) - strtotime($lastLogTime)) / 60;
        if ($lastLogType === 'inn' && $row['log_type'] === 'ut') {
            $todayMinutes += $diff; // Time worked today
        }
    }
    $lastLogType = $row['log_type'];
    $lastLogTime = $row['log_time'];
}

// Fetch this week's work hours
$weekStart = date("Y-m-d 00:00:00", strtotime('monday this week'));
$weekEnd = date("Y-m-d 23:59:59", strtotime('sunday this week'));

$sql = "SELECT log_type, log_time FROM logs WHERE user_id='$userId' AND log_time BETWEEN '$weekStart' AND '$weekEnd' ORDER BY log_time ASC";
$result = $conn->query($sql);

$weekMinutes = 0;
$lastLogType = '';
$lastLogTime = '';

while ($row = $result->fetch_assoc()) {
    if ($lastLogTime) {
        $diff = (strtotime($row['log_time']) - strtotime($lastLogTime)) / 60;
        if ($lastLogType === 'inn' && $row['log_type'] === 'ut') {
            $weekMinutes += $diff; // Time worked this week
        }
    }
    $lastLogType = $row['log_type'];
    $lastLogTime = $row['log_time'];
}

// Fetch flexitime balance
$sql = "SELECT balance_minutes FROM flexitime_balance WHERE user_id='$userId'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $flexitimeBalance = $row['balance_minutes'];
}
?>
