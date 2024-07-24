<?php
session_start();
include_once '../../db.php'; // Adjust the path as needed

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$userId = $_SESSION['user_id'];
$todayMinutes = 0;
$weekMinutes = 0;
$flexitimeBalance = 0;
$message = '';

// Include logic for handling log in/out and manual input
include 'includes/handle_log.php';
include 'includes/manual_log.php';

// Fetch today's and this week's work hours and flexitime balance
include 'includes/fetch_logs.php';

// Fetch all employees
$employees = [];
$sql = "SELECT username FROM brukere";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row['username'];
    }
}


$conn->close();
?>
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fleksitid Ansatteside</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="employee.css">
</head>
<body>
    <header>
        <h1>Velkommen, <?php echo $_SESSION['username']; ?>!</h1>
        <button id="logout-btn-system">Logg ut</button>
    </header>

    <div class="main-container">
        <div class="container">
            <h2>Alle Ansatte</h2>
            <ul>
                <?php foreach ($employees as $employee): ?>
                    <li><?php echo htmlspecialchars($employee); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="container">
            <h2>Dagens Arbeidstimer</h2>
            <p id="today-time">Tid brukt på jobb i dag: <?php echo $todayMinutes; ?> minutter</p>

            <form method="post" action="" id="logForm">
                <input type="hidden" name="logType" id="logType">
                <button type="button" onclick="logTime('inn')">Kom på jobb nå</button>
                <button type="button" onclick="logTime('ut')">Drar fra jobb nå</button>
            </form>

            <?php if ($message): ?>
                <p><?php echo $message; ?></p>
            <?php endif; ?>
        </div>

        <div class="container">
            <h2>Manuell Innlegging av Timer</h2>
            <form method="post" action="">
                <input type="hidden" name="manualLog" value="true">
                <label for="date">Dato:</label>
                <input type="date" id="date" name="date" required>
                <label for="hours">Timer jobbet (inkludert pause):</label>
                <input type="number" id="hours" name="hours" step="0.1" required>
                <button type="submit">Legg til timer</button>
            </form>
        </div>

        <div class="container">
            <h2>Arbeidstimer Denne Uken</h2>
            <p>Total tid denne uken: <?php echo $weekMinutes; ?> minutter</p>
            <p>Nåværende uke: <?php echo date('W, Y'); ?></p>

            <h2>Fleksitid Balanse</h2>
            <p id="flexitime-balance">Fleksitid balanse: <?php echo $flexitimeBalance; ?> minutter</p>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmationModal" style="display: none;">
        <p id="confirmationMessage"></p>
        <button onclick="confirmLog()">Bekreft</button>
        <button onclick="denyLog()">Avbryt</button>
    </div>

    <script src="js/auth.js"></script>
    <script>
        function logTime(type) {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'includes/handle_log.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        document.getElementById('confirmationMessage').textContent = response.message;
                        document.getElementById('confirmationModal').style.display = 'block';
                        document.getElementById('logType').value = type;
                    } else {
                        alert('En feil oppstod: ' + response.error);
                    }
                }
            };
            xhr.send('logType=' + type);
        }

        function confirmLog() {
            document.getElementById('logForm').submit();
        }

        function denyLog() {
            document.getElementById('confirmationModal').style.display = 'none';
            alert('Vennligst legg inn timer manuelt.');
        }
    </script>
</body>
</html>
