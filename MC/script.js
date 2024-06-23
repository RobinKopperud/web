document.addEventListener('DOMContentLoaded', function() {
    console.log('Mental Racing Team website loaded');

    // Toggle menu visibility on mobile devices
    const menuToggle = document.querySelector('.menu-toggle');
    const nav = document.querySelector('nav');
    
    menuToggle.addEventListener('click', function() {
        nav.classList.toggle('show');
    });

    // Hent neste arrangementsdato
    fetch('next_event.txt')
        .then(response => response.text())
        .then(date => {
            const countDownDate = new Date(date).getTime();

            const countdownFunction = setInterval(function() {
                const now = new Date().getTime();
                const distance = countDownDate - now;

                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                document.getElementById("days").innerHTML = days;
                document.getElementById("hours").innerHTML = hours;
                document.getElementById("minutes").innerHTML = minutes;
                document.getElementById("seconds").innerHTML = seconds;

                if (distance < 0) {
                    clearInterval(countdownFunction);
                    document.getElementById("countdown").innerHTML = "RACE DAY!";
                }
            }, 1000);
        })
        .catch(error => console.error('Error:', error));

    // Resten av din eksisterende JavaScript-kode...
});
