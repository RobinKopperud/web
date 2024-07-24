<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['manualLog'])) {
    $date = $_POST['date'];
    $hoursWorked = floatval($_POST['hours']);
    $logDate = date("Y-m-d", strtotime($date));
    $standardWorkDayMinutes = 480; // 8 timer * 60 minutter

    $conn->begin_transaction();
    try {
        // Delete existing log for selected date
        $deleteSql = "DELETE FROM logs WHERE user_id='$userId' AND DATE(log_time) = '$logDate'";
        $conn->query($deleteSql);

        // Calculate log in and log out times based on hours worked
        $logInTime = $logDate . " 08:00:00"; // Start day at 08:00
        $logOutTime = date("Y-m-d H:i:s", strtotime($logInTime) + ($hoursWorked * 3600));

        // Insert new log entries
        $insertInSql = "INSERT INTO logs (user_id, log_type, log_time) VALUES ('$userId', 'inn', '$logInTime')";
        $insertOutSql = "INSERT INTO logs (user_id, log_type, log_time) VALUES ('$userId', 'ut', '$logOutTime')";
        $conn->query($insertInSql);
        $conn->query($insertOutSql);

        // Recalculate the flexitime balance for all days
        $balanceMinutes = 0;

        $logSql = "SELECT log_type, log_time FROM logs WHERE user_id='$userId' ORDER BY log_time ASC";
        $logResult = $conn->query($logSql);

        $lastLogType = '';
        $lastLogTime = '';

        while ($row = $logResult->fetch_assoc()) {
            if ($lastLogTime) {
                $diff = (strtotime($row['log_time']) - strtotime($lastLogTime)) / 60;
                if ($lastLogType === 'inn' && $row['log_type'] === 'ut') {
                    $balanceMinutes += $diff - $standardWorkDayMinutes; // Adjust balance
                }
            }
            $lastLogType = $row['log_type'];
            $lastLogTime = $row['log_time'];
        }

        // Update flexitime balance in the database
        $balanceSql = "SELECT balance_minutes FROM flexitime_balance WHERE user_id='$userId' FOR UPDATE";
        $balanceResult = $conn->query($balanceSql);

        if ($balanceResult->num_rows > 0) {
            $updateSql = "UPDATE flexitime_balance SET balance_minutes='$balanceMinutes', last_update=NOW() WHERE user_id='$userId'";
            $conn->query($updateSql);
        } else {
            $insertBalanceSql = "INSERT INTO flexitime_balance (user_id, balance_minutes, last_update) VALUES ('$userId', '$balanceMinutes', NOW())";
            $conn->query($insertBalanceSql);
        }

        $conn->commit();
        $message = "Timer for $logDate er lagt til.";
    } catch (Exception $e) {
        $conn->rollback();
        $message = 'En feil oppstod: ' . $e->getMessage();
    }
}
?>
