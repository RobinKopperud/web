document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('gameCanvas');
    const ctx = canvas.getContext('2d');
    const startBtn = document.getElementById('start-btn');
    const stopBtn = document.getElementById('stop-btn');
    const scoreDisplay = document.getElementById('score');
    const box = 20;
    const canvasSize = 400;
    let snake = [{ x: box * 5, y: box * 5 }];
    let direction = null;
    let food = {};
    let score = 0;
    let gameInterval;

    function startGame() {
        direction = 'RIGHT';
        placeFood();
        gameInterval = setInterval(draw, 100);
        document.addEventListener('keydown', changeDirection);
        startBtn.disabled = true;
        stopBtn.disabled = false;
    }

    function stopGame() {
        clearInterval(gameInterval);
        document.removeEventListener('keydown', changeDirection);
        startBtn.disabled = false;
        stopBtn.disabled = true;
    }

    function placeFood() {
        food.x = Math.floor(Math.random() * (canvasSize / box)) * box;
        food.y = Math.floor(Math.random() * (canvasSize / box)) * box;
    }

    function draw() {
        ctx.clearRect(0, 0, canvasSize, canvasSize);
        for (let i = 0; i < snake.length; i++) {
            ctx.fillStyle = (i === 0) ? 'green' : 'white';
            ctx.fillRect(snake[i].x, snake[i].y, box, box);
            ctx.strokeStyle = 'red';
            ctx.strokeRect(snake[i].x, snake[i].y, box, box);
        }

        ctx.fillStyle = 'red';
        ctx.fillRect(food.x, food.y, box, box);

        let snakeX = snake[0].x;
        let snakeY = snake[0].y;

        if (direction === 'LEFT') snakeX -= box;
        if (direction === 'UP') snakeY -= box;
        if (direction === 'RIGHT') snakeX += box;
        if (direction === 'DOWN') snakeY += box;

        if (snakeX === food.x && snakeY === food.y) {
            score++;
            scoreDisplay.textContent = `Score: ${score}`;
            placeFood();
        } else {
            snake.pop();
        }

        const newHead = { x: snakeX, y: snakeY };

        if (snakeX < 0 || snakeY < 0 || snakeX >= canvasSize || snakeY >= canvasSize || collision(newHead, snake)) {
            stopGame();
        }

        snake.unshift(newHead);
    }

    function changeDirection(event) {
        if (event.key === 'ArrowLeft' && direction !== 'RIGHT') {
            direction = 'LEFT';
        } else if (event.key === 'ArrowUp' && direction !== 'DOWN') {
            direction = 'UP';
        } else if (event.key === 'ArrowRight' && direction !== 'LEFT') {
            direction = 'RIGHT';
        } else if (event.key === 'ArrowDown' && direction !== 'UP') {
            direction = 'DOWN';
        }
    }

    function collision(newHead, snake) {
        for (let i = 0; i < snake.length; i++) {
            if (newHead.x === snake[i].x && newHead.y === snake[i].y) {
                return true;
            }
        }
        return false;
    }

    startBtn.addEventListener('click', startGame);
    stopBtn.addEventListener('click', stopGame);
});
