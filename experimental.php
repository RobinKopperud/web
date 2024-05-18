<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: loginout.php");
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
        #submit-score {
            margin-top: 20px;
            display: none;
            text-align: center;
        }
        #highscores {
            margin-top: 20px;
            text-align: center;
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
        <div id="submit-score">
            <button id="submit-btn">Submit Score</button>
        </div>
        <div id="highscores">
            <h3>High Scores</h3>
            <ul id="highscore-list"></ul>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const player = document.getElementById('player');
            const gameContainer = document.getElementById('game-container');
            const scoreDisplay = document.getElementById('score');
            const startBtn = document.getElementById('start-btn');
            const stopBtn = document.getElementById('stop-btn');
            const submitScoreDiv = document.getElementById('submit-score');
            const submitBtn = document.getElementById('submit-btn');
            const highscoreList = document.getElementById('highscore-list');
            let score = 0;
            let gameInterval;
            let isGameRunning = false;

            // Get the logged-in username from the PHP session
            const username = '<?php echo $_SESSION['username']; ?>';

            function startGame() {
                if (isGameRunning) return;
                isGameRunning = true;
                score = 0;
                scoreDisplay.textContent = `Score: ${score}`;
                submitScoreDiv.style.display = 'none';
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
                submitScoreDiv.style.display = 'block';
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
                        // Check for collision
                        const playerRect = player.getBoundingClientRect();
                        const objectRect = object.getBoundingClientRect();
                        
                        if (objectRect.left < playerRect.right && objectRect.right > playerRect.left && objectRect.top < playerRect.bottom && objectRect.bottom > playerRect.top) {
                            score++;
                            scoreDisplay.textContent = `Score: ${score}`;
                            clearInterval(fallingInterval);
                            gameContainer.removeChild(object);
                        } else if (objectTop >= gameContainer.offsetHeight - 30) {
                            clearInterval(fallingInterval);
                            gameContainer.removeChild(object);
                        }
                    } else {
                        object.style.top = `${objectTop + 5}px`; // Adjust falling speed
                    }

                    // Remove the object if it goes out of the playing area
                    if (objectTop > gameContainer.offsetHeight) {
                        clearInterval(fallingInterval);
                        gameContainer.removeChild(object);
                    }
                }, 30);
            }

            function submitScore() {
                fetch('submit_score.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `username=${username}&score=${score}`
                })
                .then(response => response.text())
                .then(data => {
                    alert(data);
                    loadHighscores();
                })
                .catch(error => console.error('Error:', error));
            }

            function loadHighscores() {
                fetch('get_highscores.php')
                .then(response => response.json())
                .then(data => {
                    highscoreList.innerHTML = '';
                    data.forEach(highscore => {
                        const li = document.createElement('li');
                        li.textContent = `${highscore.username}: ${highscore.score}`;
                        highscoreList.appendChild(li);
                    });
                })
                .catch(error => console.error('Error:', error));
            }

            startBtn.addEventListener('click', startGame);
            stopBtn.addEventListener('click', stopGame);
            submitBtn.addEventListener('click', submitScore);

            loadHighscores();
        });
    </script>
</body>
</html>
