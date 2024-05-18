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

            // Stop the game if the object reaches the bottom of the playing area
            if (objectTop >= gameContainer.offsetHeight) {
                clearInterval(fallingInterval);
                stopGame();
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
