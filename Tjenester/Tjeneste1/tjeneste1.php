<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tjeneste 1 - Motorcycle Game</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        canvas {
            display: block;
            margin: 0 auto;
            background-color: #f4f4f4;
            width: 90vw;
            max-width: 480px;
        }
        .controls {
            text-align: center;
            margin-top: 10px;
        }
        .controls button {
            margin: 0 5px;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="../index.php">Home</a></li>
                <li><a href="#">Leaderboard</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <h1>Motorcycle Game</h1>
        <canvas id="gameCanvas" width="480" height="640"></canvas>
        <div class="controls">
            <button id="startButton">Start</button>
            <button id="stopButton">Stop</button>
        </div>
        <button id="restartButton" style="display:none;">Restart</button>
    </main>
    <footer>
        <p>&copy; 2024 Tjenester. All rights reserved.</p>
    </footer>
    <script src="tjeneste1.js"></script>
</body>
</html>
