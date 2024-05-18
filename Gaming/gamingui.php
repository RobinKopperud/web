<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../loginout.html");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gaming Hub</title>
    <link rel="stylesheet" href="css/gaming.css">
</head>
<body>
    <div class="sidebar">
        <nav>
            <ul>
                <li><a href="../index.html">Home</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </nav>
    </div>
    <div class="main-content">
        <h1>Welcome to the Gaming Hub</h1>
        <div class="game-list">
            <div class="game">
                <a href="game1/game1.php">
                    <img src="images/game1.png" alt="Game 1">
                    <h2>Game 1</h2>
                </a>
            </div>
            <div class="game">
                <a href="game2/game2.php">
                    <img src="images/game2.png" alt="Game 2">
                    <h2>Game 2</h2>
                </a>
            </div>
            <!-- Add more games as needed -->
        </div>
    </div>
</body>
</html>
