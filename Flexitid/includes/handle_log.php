<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $logType = $_POST['logType'];
    $logTime = date("Y-m-d H:i:s");

    $conn->begin_transaction();
    try {
        // Insert log entry
        $sql = "INSERT INTO logs (user_id, log_type, log_time) VALUES ('$userId', '$logType', '$logTime')";
        $conn->query($sql);

        // Fetch last log entry
        $lastLogSql = "SELECT log_type, log_time FROM logs WHERE user_id='$userId' ORDER BY log_time DESC LIMIT 2";
        $lastLogResult = $conn->query($lastLogSql);
        $lastLogs = [];
        while ($row = $lastLogResult->fetch_assoc()) {
            $lastLogs[] = $row;
        }

        // Calculate flexitime balance
        if (count($lastLogs) == 2) {
            $previousLog = $lastLogs[1];
            $previousLogType = $previousLog['log_type'];
            $previousLogTime = strtotime($previousLog['log_time']);
            $currentLogTime = strtotime($logTime);

            $balanceSql = "SELECT balance_minutes FROM flexitime_balance WHERE user_id='$userId' FOR UPDATE";
            $balanceResult = $conn->query($balanceSql);
            $balanceRow = $balanceResult->fetch_assoc();

            if ($balanceRow) {
                $balanceMinutes = $balanceRow['balance_minutes'];
                if ($previousLogType === 'inn' && $logType === 'ut') {
                    $diff = ($currentLogTime - $previousLogTime) / 60;
                    $calculatedFlexitime = $balanceMinutes + ($diff - 480); // Adjust by standard workday
                } elseif ($previousLogType === 'ut' && $logType === 'inn') {
                    $diff = ($currentLogTime - $previousLogTime) / 60;
                    $calculatedFlexitime = $balanceMinutes - $diff; // Subtract break time
                }

                // Return response with calculated flexitime
                echo json_encode([
                    'success' => true,
                    'message' => 'Beregnet fleksitid: ' . $calculatedFlexitime . ' minutter. Vil du bekrefte?',
                    'calculatedFlexitime' => $calculatedFlexitime
                ]);
            } else {
                // Insert new balance record
                $balanceMinutes = 0;
                $insertSql = "INSERT INTO flexitime_balance (user_id, balance_minutes, last_update) VALUES ('$userId', '$balanceMinutes', '$logTime')";
                $conn->query($insertSql);
                echo json_encode(['success' => true, 'message' => 'Ingen tidligere fleksitid funnet.']);
            }
        }

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>
