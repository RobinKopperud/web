<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not authenticated.']);
    exit();
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['logType'])) {
    $logType = $_POST['logType'];
    $logTime = date("Y-m-d H:i:s");

    $conn->begin_transaction();
    try {
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
                    $balanceMinutes += $diff - 480; // Adjust by standard workday
                } elseif ($previousLogType === 'ut' && $logType === 'inn') {
                    $diff = ($currentLogTime - $previousLogTime) / 60;
                    $balanceMinutes -= $diff; // Subtract break time
                }

                // Update balance
                $updateSql = "UPDATE flexitime_balance SET balance_minutes='$balanceMinutes', last_update=NOW() WHERE user_id='$userId'";
                $conn->query($updateSql);

                echo json_encode([
                    'success' => true,
                    'message' => 'Loggoppføring bekreftet og fleksitid oppdatert.'
                ]);
            }
        }

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>
