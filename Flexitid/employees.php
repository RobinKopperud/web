<?php
session_start();
include_once '../../db.php'; // Juster stien etter behov

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Hent alle ansatte
$sql = "SELECT username FROM brukere";
$result = $conn->query($sql);

$employees = array();
while ($row = $result->fetch_assoc()) {
    $employees[] = $row['username'];
}

// Hent dagens arbeidstimer
$userId = $_SESSION['user_id'];
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

$weekMinutes = 0;
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

$conn->close();
?>
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fleksitid Ansatteside</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Velkommen, <?php echo $_SESSION['username']; ?>!</h1>
        <h2>Alle Ansatte</h2>
        <ul>
            <?php foreach ($employees as $employee): ?>
                <li><?php echo htmlspecialchars($employee); ?></li>
            <?php endforeach; ?>
        </ul>
        <h2>Dagens Arbeidstimer</h2>
        <p id="today-time">Tid brukt i dag: <?php echo $todayMinutes; ?> minutter</p>
        <button id="login-btn">Kom på jobb nå</button>
        <button id="logout-btn">Drar fra jobb nå</button>
        <button id="logout-btn-system">Logg ut</button>

        <h2>Arbeidstimer Denne Uken</h2>
        <p>Total tid denne uken: <?php echo $weekMinutes; ?> minutter</p>
        <p>Nåværende uke: <?php echo date('W, Y'); ?></p>

        <h2>Fleksitid Balanse</h2>
        <p id="flexitime-balance">Fleksitid balanse: <?php echo $flexitime; ?> minutter</p>
    </div>
    <script src="auth.js"></script>
    <script src="logs.js"></script>
    <script src="topUsers.js"></script>
</body>
</html>
