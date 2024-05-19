<?php
include('../header.php');
?>

<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Snake Game</title>
    <link rel="stylesheet" href="game1.css">
    <link rel="stylesheet" href="../css/gaming.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
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
    <script src="../js/gaming.js"></script>
</body>
</html>
