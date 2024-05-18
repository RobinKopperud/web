<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../loginout.html");
    exit();
}
?>

<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Snake Game</title>
    <link rel="stylesheet" href="game1.css">
</head>
<body>
    <div class="sidebar">
        <nav>
            <ul>
                <li><a href="../gamingui.php">Gaming Hub</a></li>
                <li><a href="../../index.html">Home</a></li>
                <li><a href="../../logout.php">Logout</a></li>
            </ul>
        </nav>
    </div>
    <div class="main-content">
        <h1>Snake Game</h1>
        <canvas id="gameCanvas" width="400" height="400"></canvas>
        <div id="controls">
            <button id="start-btn">Start</button>
            <button id="stop-btn" disabled>Stop</button>
        </div>
        <div id="score">Score: 0</div>
    </div>
    <script>
        // Pass the username to JavaScript
        const username = '<?php echo $_SESSION['username']; ?>';
    </script>
    <script src="game1.js"></script>
</body>
</html>
