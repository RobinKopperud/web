const canvas = document.getElementById('gameCanvas');
const ctx = canvas.getContext('2d');

let motorcycle = {
    x: canvas.width / 2 - 25,
    y: canvas.height - 100,
    width: 50,
    height: 100,
    speed: 5,
    dx: 0
};

let obstacles = [];
let obstacleSpeed = 3;
let gameOver = false;
let startTime;
let timerInterval;

document.addEventListener('keydown', moveMotorcycle);
document.addEventListener('keyup', stopMotorcycle);
canvas.addEventListener('touchstart', handleTouchStart);
canvas.addEventListener('touchmove', handleTouchMove);
canvas.addEventListener('touchend', handleTouchEnd);

function moveMotorcycle(e) {
    if (e.key === 'ArrowLeft') {
        motorcycle.dx = -motorcycle.speed;
    } else if (e.key === 'ArrowRight') {
        motorcycle.dx = motorcycle.speed;
    }
}

function stopMotorcycle(e) {
    if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
        motorcycle.dx = 0;
    }
}

function handleTouchStart(e) {
    handleTouchMove(e);
}

function handleTouchMove(e) {
    const touchX = e.touches[0].clientX;
    const rect = canvas.getBoundingClientRect();
    const canvasX = touchX - rect.left;
    
    if (canvasX < motorcycle.x) {
        motorcycle.dx = -motorcycle.speed;
    } else if (canvasX > motorcycle.x + motorcycle.width) {
        motorcycle.dx = motorcycle.speed;
    }
}

function handleTouchEnd(e) {
    motorcycle.dx = 0;
}

function drawMotorcycle() {
    ctx.fillStyle = 'blue';
    ctx.fillRect(motorcycle.x, motorcycle.y, motorcycle.width, motorcycle.height);
}

function updateMotorcycle() {
    motorcycle.x += motorcycle.dx;

    if (motorcycle.x < 0) {
        motorcycle.x = 0;
    }
    if (motorcycle.x + motorcycle.width > canvas.width) {
        motorcycle.x = canvas.width - motorcycle.width;
    }
}

function createObstacle() {
    const x = Math.random() * (canvas.width - 50);
    obstacles.push({ x, y: 0, width: 50, height: 50 });
}

function drawObstacles() {
    ctx.fillStyle = 'red';
    obstacles.forEach(obstacle => {
        ctx.fillRect(obstacle.x, obstacle.y, obstacle.width, obstacle.height);
    });
}

function updateObstacles() {
    obstacles.forEach(obstacle => {
        obstacle.y += obstacleSpeed;
    });

    obstacles = obstacles.filter(obstacle => obstacle.y < canvas.height);
}

function detectCollision() {
    obstacles.forEach(obstacle => {
        if (motorcycle.x < obstacle.x + obstacle.width &&
            motorcycle.x + motorcycle.width > obstacle.x &&
            motorcycle.y < obstacle.y + obstacle.height &&
            motorcycle.y + motorcycle.height > obstacle.y) {
            gameOver = true;
            clearInterval(timerInterval);
        }
    });
}

function clearCanvas() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
}

function update() {
    clearCanvas();
    drawMotorcycle();
    drawObstacles();
    updateMotorcycle();
    updateObstacles();
    detectCollision();

    if (!gameOver) {
        requestAnimationFrame(update);
    } else {
        ctx.font = '30px Arial';
        ctx.fillStyle = 'black';
        ctx.fillText('Game Over', canvas.width / 2 - 70, canvas.height / 2);
    }

    drawTimer();
}

function startTimer() {
    startTime = Date.now();
    timerInterval = setInterval(() => {
        drawTimer();
    }, 1000);
}

function drawTimer() {
    const elapsedTime = Math.floor((Date.now() - startTime) / 1000);
    ctx.font = '20px Arial';
    ctx.fillStyle = 'black';
    ctx.fillText(`Time: ${elapsedTime}s`, 10, 30);
}

setInterval(createObstacle, 2000);
startTimer();
update();
