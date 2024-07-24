<?php
function fetchHoursForDate($conn, $userId, $selectedDate) {
    $sql = "SELECT log_type, log_time FROM logs 
            WHERE user_id='$userId' AND DATE(log_time)='$selectedDate' 
            ORDER BY log_time ASC";
    $result = $conn->query($sql);

    $totalMinutes = 0;
    $lastLogType = '';
    $lastLogTime = '';

    while ($row = $result->fetch_assoc()) {
        if ($lastLogTime) {
            $diff = (strtotime($row['log_time']) - strtotime($lastLogTime)) / 60;
            if ($lastLogType === 'inn' && $row['log_type'] === 'ut') {
                $totalMinutes += $diff; // Time worked on selected date
            }
        }
        $lastLogType = $row['log_type'];
        $lastLogTime = $row['log_time'];
    }

    return $totalMinutes / 60; // Convert minutes to hours
}
?>
