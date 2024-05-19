document.addEventListener('DOMContentLoaded', () => {
    const carousel = document.querySelector('.carousel');
    const items = document.querySelectorAll('.carousel-item');
    const radius = 250;
    const itemCount = items.length;
    const angleStep = 360 / itemCount;

    items.forEach((item, index) => {
        const angle = index * angleStep;
        const x = radius * Math.cos((angle * Math.PI) / 180);
        const z = radius * Math.sin((angle * Math.PI) / 180);

        item.style.transform = `translateX(${x}px) translateZ(${z}px)`;
    });

    const adjustOpacity = () => {
        items.forEach((item, index) => {
            const itemAngle = (index * angleStep + carousel.angle) % 360;
            const opacity = Math.max(0, Math.cos((itemAngle * Math.PI) / 180));
            item.style.opacity = opacity;
        });
    };

    const rotateCarousel = () => {
        carousel.angle = (carousel.angle || 0) + 0.5;
        carousel.style.transform = `rotateY(${carousel.angle}deg)`;
        adjustOpacity();
    };

    setInterval(rotateCarousel, 100);
});
