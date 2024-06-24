document.addEventListener('DOMContentLoaded', function() {
    console.log('Mental Racing Team website loaded');

    // Toggle menu visibility on mobile devices
    const menuToggle = document.querySelector('.menu-toggle');
    const nav = document.querySelector('nav');
    
    menuToggle.addEventListener('click', function() {
        nav.classList.toggle('show');
    });
});
