const canvas = document.createElement('canvas');
const ctx = canvas.getContext('2d');
document.getElementById('matrix-container').appendChild(canvas);

canvas.width = window.innerWidth;
canvas.height = window.innerHeight;

const letters = Array(256).join(1).split('');
const fontSize = 14;
const columns = canvas.width / fontSize;

function draw() {
    ctx.fillStyle = 'rgba(0, 0, 0, 0.05)';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.fillStyle = '#0F0';
    ctx.font = fontSize + 'px monospace';

    letters.map((y, index) => {
        const text = String.fromCharCode(3e4 + Math.random() * 33);
        const x = index * fontSize;
        ctx.fillText(text, x, y);
        letters[index] = y > canvas.height + Math.random() * 1e4 ? 0 : y + fontSize / 2; // Slowing down the speed
    });
}

setInterval(draw, 50); // Slowing down the drawing interval

window.addEventListener('resize', () => {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
});
