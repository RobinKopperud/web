<?php
session_start();
include_once '../../db.php'; // Adjust the path as needed

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Fetch all employees
$sql = "SELECT username FROM brukere";
$result = $conn->query($sql);

$employees = array();
while ($row = $result->fetch_assoc()) {
    $employees[] = $row['username'];
}

// Fetch today's working hours
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
            $todayMinutes += $diff; // Time spent working today
        }
    }
    $lastLogType = $row['log_type'];
    $lastLogTime = $row['log_time'];
}

// Fetch this week's working hours
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
            $weekMinutes += $diff; // Time spent working this week
        }
    }
    $lastLogType = $row['log_type'];
    $lastLogTime = $row['log_time'];
}

// Calculate flexitime balance
$standardWorkDayMinutes = 480; // 8 hours * 60 minutes
$flexitime = $todayMinutes - $standardWorkDayMinutes;

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fleksitid Employee Page</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Welcome, <?php echo $_SESSION['username']; ?>!</h1>
        <h2>All Employees</h2>
        <ul>
            <?php foreach ($employees as $employee): ?>
                <li><?php echo htmlspecialchars($employee); ?></li>
            <?php endforeach; ?>
        </ul>
        <h2>Today's Working Hours</h2>
        <p id="today-time">Time spent today: <?php echo $todayMinutes; ?> minutes</p>
        <button id="login-btn">Kom på jobb nå</button>
        <button id="logout-btn">Drar fra jobb nå</button>
        <button id="logout-btn-system">Log Out</button>

        <h2>This Week's Working Hours</h2>
        <p>Total time this week: <?php echo $weekMinutes; ?> minutes</p>
        <p>Current week: <?php echo date('W, Y'); ?></p>

        <h2>Flexitime Balance</h2>
        <p id="flexitime-balance">Flexitime balance: <?php echo $flexitime; ?> minutes</p>
    </div>
    <script src="auth.js"></script>
    <script src="logs.js"></script>
    <script src="topUsers.js"></script>
</body>
</html>
