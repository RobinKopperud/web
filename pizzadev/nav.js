document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.getElementById('menu-toggle');
    const navButtons = document.getElementById('nav-buttons');

    menuToggle.addEventListener('click', () => {
        if (navButtons.style.display === 'block') {
            navButtons.style.display = 'none';
        } else {
            navButtons.style.display = 'block';
        }
    });
});
