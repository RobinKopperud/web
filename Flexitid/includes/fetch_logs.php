<?php
// Hent dagens arbeidstimer
$todayStart = date("Y-m-d 00:00:00");
$todayEnd = date("Y-m-d 23:59:59");

$sql = "SELECT log_type, log_time FROM logs WHERE user_id='$userId' AND log_time BETWEEN '$todayStart' AND '$todayEnd' ORDER BY log_time ASC";
$result = $conn->query($sql);

$lastLogType = '';
$lastLogTime = '';

while ($row = $result->fetch_assoc()) {
    if ($lastLogTime) {
        $diff = (strtotime($row['log_time']) - strtotime($lastLogTime)) / 60;
        if ($lastLogType === 'inn' && $row['log_type'] === 'ut') {
            $todayMinutes += $diff; // Tid brukt på jobb i dag
        }
    }
    $lastLogType = $row['log_type'];
    $lastLogTime = $row['log_time'];
}

// Hent denne ukens arbeidstimer
$weekStart = date("Y-m-d 00:00:00", strtotime('monday this week'));
$weekEnd = date("Y-m-d 23:59:59", strtotime('sunday this week'));

$sql = "SELECT log_type, log_time FROM logs WHERE user_id='$userId' AND log_time BETWEEN '$weekStart' AND '$weekEnd' ORDER BY log_time ASC";
$result = $conn->query($sql);

$lastLogType = '';
$lastLogTime = '';

while ($row = $result->fetch_assoc()) {
    if ($lastLogTime) {
        $diff = (strtotime($row['log_time']) - strtotime($lastLogTime)) / 60;
        if ($lastLogType === 'inn' && $row['log_type'] === 'ut') {
            $weekMinutes += $diff; // Tid brukt på jobb denne uken
        }
    }
    $lastLogType = $row['log_type'];
    $lastLogTime = $row['log_time'];
}

// Beregn fleksitid balanse
$standardWorkDayMinutes = 480; // 8 timer * 60 minutter
$flexitime = $todayMinutes - $standardWorkDayMinutes;

// Hent fleksitid balanse
$sql = "SELECT balance_minutes FROM flexitime_balance WHERE user_id='$userId'";
$result = $conn->query($sql);
$flexitimeBalance = $result->fetch_assoc()['balance_minutes'];
?>
