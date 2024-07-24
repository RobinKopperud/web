<?php
session_start();
include_once '../../db.php'; // Juster stien etter behov

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$userId = $_SESSION['user_id'];
$todayMinutes = 0;
$weekMinutes = 0;
$flexitime = 0;
$message = '';

// Inkluder logikk for håndtering av logginn/ut
include 'includes/handle_log.php';

// Hent dagens og ukens arbeidstimer
include 'includes/fetch_logs.php';

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
        <button id="logout-btn-system">Logg ut</button>

        <h2>Alle Ansatte</h2>
        <ul>
            <?php foreach ($employees as $employee): ?>
                <li><?php echo htmlspecialchars($employee); ?></li>
            <?php endforeach; ?>
        </ul>

        <h2>Dagens Arbeidstimer</h2>
        <p id="today-time">Tid brukt i dag: <?php echo $todayMinutes; ?> minutter</p>

        <form method="post" action="">
            <input type="hidden" name="logType" value="inn">
            <button type="submit">Kom på jobb nå</button>
        </form>

        <form method="post" action="">
            <input type="hidden" name="logType" value="ut">
            <button type="submit">Drar fra jobb nå</button>
        </form>

        <?php if ($message): ?>
            <p><?php echo $message; ?></p>
        <?php endif; ?>
    </div>

    <div class="container">
        <h2>Arbeidstimer Denne Uken</h2>
        <p>Total tid denne uken: <?php echo $weekMinutes; ?> minutter</p>
        <p>Nåværende uke: <?php echo date('W, Y'); ?></p>

        <h2>Fleksitid Balanse</h2>
        <p id="flexitime-balance">Fleksitid balanse: <?php echo $flexitime; ?> minutter</p>
    </div>

    <script src="js/auth.js"></script>
    <script src="js/logs.js"></script>
</body>
</html>
