<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: loginout.html");
    exit();
}
?>

<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Experimental</title>
    <link rel="stylesheet" href="experimental.css">
</head>
<body>
    <header>
        <h1>Experimental</h1>
        <nav>
            <a href="index.php">Home</a>
            <a href="logout.php">Logg ut</a>
        </nav>
    </header>
    <div class="container">
        <h2>Simple Game: Catch the Falling Objects</h2>
        <div id="controls">
            <button id="start-btn">Start</button>
            <button id="stop-btn" disabled>Stop</button>
        </div>
        <div class="game-container" id="game-container">
            <div id="player"></div>
        </div>
        <div id="score">Score: 0</div>
        <div id="submit-score" style="display: none;">
            <button id="submit-btn">Submit Score</button>
        </div>
        <div id="highscores">
            <h3>High Scores</h3>
            <ul id="highscore-list"></ul>
        </div>
    </div>
    <script>
        // Pass the username to JavaScript
        const username = '<?php echo $_SESSION['username']; ?>';
    </script>
    <script src="experimental.js"></script>
</body>
</html>
