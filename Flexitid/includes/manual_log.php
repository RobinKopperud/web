<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['manualLog'])) {
    $date = $_POST['date'];
    $hoursWorked = floatval($_POST['hours']);
    $logDate = date("Y-m-d", strtotime($date));
    $standardWorkDayMinutes = 480; // 8 timer * 60 minutter

    $conn->begin_transaction();
    try {
        // Slett eksisterende logg for valgt dato
        $deleteSql = "DELETE FROM logs WHERE user_id='$userId' AND DATE(log_time) = '$logDate'";
        $conn->query($deleteSql);

        // Beregn inn- og utloggingsklokkeslett basert på antall timer jobbet
        $logInTime = $logDate . " 08:00:00"; // Start dagen kl 08:00
        $logOutTime = date("Y-m-d H:i:s", strtotime($logInTime) + ($hoursWorked * 3600));

        // Sett inn nye logginnføringer
        $insertInSql = "INSERT INTO logs (user_id, log_type, log_time) VALUES ('$userId', 'inn', '$logInTime')";
        $insertOutSql = "INSERT INTO logs (user_id, log_type, log_time) VALUES ('$userId', 'ut', '$logOutTime')";
        $conn->query($insertInSql);
        $conn->query($insertOutSql);

        // Oppdater fleksitid balanse
        $balanceSql = "SELECT balance_minutes FROM flexitime_balance WHERE user_id='$userId' FOR UPDATE";
        $balanceResult = $conn->query($balanceSql);
        $balanceRow = $balanceResult->fetch_assoc();

        if ($balanceRow) {
            $balanceMinutes = $balanceRow['balance_minutes'];
            $workedMinutes = $hoursWorked * 60;
            $balanceMinutes += $workedMinutes - $standardWorkDayMinutes; // Oppdater balansen

            $updateSql = "UPDATE flexitime_balance SET balance_minutes='$balanceMinutes', last_update=NOW() WHERE user_id='$userId'";
            $conn->query($updateSql);
        } else {
            $balanceMinutes = ($hoursWorked * 60) - $standardWorkDayMinutes;
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
