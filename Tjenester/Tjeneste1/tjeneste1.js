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

document.addEventListener('keydown', moveMotorcycle);
document.addEventListener('keyup', stopMotorcycle);

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

function drawMotorcycle() {
    ctx.fillStyle = 'blue';
    ctx.fillRect(motorcycle.x, motorcycle.y, motorcycle.width, motorcycle.height);
}

function updateMotorcycle() {
    motorcycle.x += motorcycle.dx;

    // Prevent motorcycle from going off canvas
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

    // Remove obstacles that are off the canvas
    obstacles = obstacles.filter(obstacle => obstacle.y < canvas.height);
}

function detectCollision() {
    obstacles.forEach(obstacle => {
        if (motorcycle.x < obstacle.x + obstacle.width &&
            motorcycle.x + motorcycle.width > obstacle.x &&
            motorcycle.y < obstacle.y + obstacle.height &&
            motorcycle.y + motorcycle.height > obstacle.y) {
            gameOver = true;
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
}

setInterval(createObstacle, 2000);
update();
