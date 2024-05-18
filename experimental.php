<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Experimental</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Additional CSS for the game */
        .game-container {
            width: 100%;
            max-width: 800px; /* Increased the width */
            height: 600px; /* Set a height for the game container */
            margin: 0 auto;
            border: 2px solid #333;
            position: relative;
            overflow: hidden;
            background-color: #f9f9f9;
        }
        #player {
            width: 50px;
            height: 50px;
            background-color: red;
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
        }
        .object {
            width: 30px;
            height: 30px;
            background-color: green;
            position: absolute;
            top: 0;
        }
        #score {
            text-align: center;
            margin-top: 20px;
            font-size: 1.5em;
        }
        #controls {
            text-align: center;
            margin: 20px 0;
        }
        #start-btn, #stop-btn {
            padding: 10px 20px;
            font-size: 1em;
            margin: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <header>
        <h1>Experimental</h1>
        <nav>
            <a href="index.php">Home</a>
            <a href="ai.php">AI Projects</a>
            <a href="krypto.html">KryptoTjeneste</a>
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
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const player = document.getElementById('player');
            const gameContainer = document.getElementById('game-container');
            const scoreDisplay = document.getElementById('score');
            const startBtn = document.getElementById('start-btn');
            const stopBtn = document.getElementById('stop-btn');
            let score = 0;
            let gameInterval;
            let isGameRunning = false;

            function startGame() {
                if (isGameRunning) return;
                isGameRunning = true;
                score = 0;
                scoreDisplay.textContent = `Score: ${score}`;
                gameInterval = setInterval(() => {
                    createFallingObject();
                }, 1000);

                document.addEventListener('keydown', movePlayer);
                gameContainer.addEventListener('touchstart', handleTouch);
                gameContainer.addEventListener('touchmove', handleTouch);
                startBtn.disabled = true;
                stopBtn.disabled = false;
            }

            function stopGame() {
                clearInterval(gameInterval);
                isGameRunning = false;
                document.removeEventListener('keydown', movePlayer);
                gameContainer.removeEventListener('touchstart', handleTouch);
                gameContainer.removeEventListener('touchmove', handleTouch);
                startBtn.disabled = false;
                stopBtn.disabled = true;
                // Remove all falling objects
                document.querySelectorAll('.object').forEach(obj => obj.remove());
            }

            function movePlayer(event) {
                const left = parseInt(window.getComputedStyle(player).getPropertyValue("left"));
                if (event.key === 'ArrowLeft' && left > 0) {
                    player.style.left = `${left - 20}px`; // Increase the movement step
                } else if (event.key === 'ArrowRight' && left < gameContainer.offsetWidth - player.offsetWidth) {
                    player.style.left = `${left + 20}px`; // Increase the movement step
                }
            }

            function handleTouch(event) {
                const touch = event.touches[0];
                const touchX = touch.clientX - gameContainer.getBoundingClientRect().left;
                const playerWidth = player.offsetWidth;
                const containerWidth = gameContainer.offsetWidth;
                
                if (touchX > 0 && touchX < containerWidth) {
                    player.style.left = `${Math.min(containerWidth - playerWidth, Math.max(0, touchX - playerWidth / 2))}px`;
                }
            }

            function createFallingObject() {
                const object = document.createElement('div');
                object.classList.add('object');
                object.style.left = `${Math.floor(Math.random() * (gameContainer.offsetWidth - 30))}px`; // Adjust for object width
                gameContainer.appendChild(object);

                let fallingInterval = setInterval(() => {
                    const objectTop = parseInt(window.getComputedStyle(object).getPropertyValue("top"));
                    if (objectTop > gameContainer.offsetHeight - player.offsetHeight - 10) {
                        clearInterval(fallingInterval);
                        gameContainer.removeChild(object);

                        // Check for collision
                        const playerLeft = parseInt(window.getComputedStyle(player).getPropertyValue("left"));
                        const objectLeft = parseInt(window.getComputedStyle(object).getPropertyValue("left"));
                        const playerRight = playerLeft + player.offsetWidth;
                        const objectRight = objectLeft + object.offsetWidth;
                        
                        if (objectLeft < playerRight && objectRight > playerLeft) {
                            score++;
                            scoreDisplay.textContent = `Score: ${score}`;
                        }
                    } else {
                        object.style.top = `${objectTop + 5}px`; // Adjust falling speed
                    }
                }, 30);
            }

            startBtn.addEventListener('click', startGame);
            stopBtn.addEventListener('click', stopGame);
        });
    </script>
</body>
</html>
