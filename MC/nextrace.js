document.addEventListener('DOMContentLoaded', function() {
    console.log('Admin page loaded');

    // Hent neste arrangementsdato
    fetch('next_event.txt')
        .then(response => response.text())
        .then(date => {
            console.log('Fetched event date:', date); // Log the fetched date
            const countDownDate = new Date(date).getTime();
            console.log('Countdown date:', countDownDate); // Log the countdown date

            const countdownFunction = setInterval(function() {
                const now = new Date().getTime();
                const distance = countDownDate - now;

                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
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
});
