<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $logType = $_POST['logType'];
    $logTime = date("Y-m-d H:i:s");

    $sql = "INSERT INTO logs (user_id, log_type, log_time) VALUES ('$userId', '$logType', '$logTime')";
    if ($conn->query($sql) === TRUE) {
        $message = ($logType === 'inn') ? 'Du logget inn på: ' . $logTime : 'Du logget ut på: ' . $logTime;
    } else {
        $message = 'En feil oppstod: ' . $conn->error;
    }
}
?>
