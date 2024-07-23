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

// Fetch user's flexitime balance
$userId = $_SESSION['user_id'];
$sql = "SELECT log_type, log_time FROM logs WHERE user_id='$userId' ORDER BY log_time DESC";
$result = $conn->query($sql);

$totalMinutes = 0;
$lastLogType = '';
$lastLogTime = '';

while ($row = $result->fetch_assoc()) {
    if ($lastLogType === 'inn' && $row['log_type'] === 'ut') {
        $diff = (strtotime($row['log_time']) - strtotime($lastLogTime)) / 60;
        $totalMinutes += $diff;
    } elseif ($lastLogType === 'ut' && $row['log_type'] === 'inn') {
        $diff = (strtotime($row['log_time']) - strtotime($lastLogTime)) / 60;
        $totalMinutes -= $diff;
    }
    $lastLogType = $row['log_type'];
    $lastLogTime = $row['log_time'];
}

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
        <h2>Fleksitid Balance</h2>
        <p>Total tid i pluss/minus: <?php echo $totalMinutes; ?> minutter</p>
        <button id="login-btn">Kom på jobb nå</button>
        <button id="logout-btn">Drar fra jobb nå</button>
        <button id="logout-btn-system">Log Out</button>
    </div>
    <script src="auth.js"></script>
    <script src="logs.js"></script>
    <script src="topUsers.js"></script>
</body>
</html>
