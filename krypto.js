document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('crypto-form');
    const resultDiv = document.getElementById('price-result');
    const serviceButtons = document.querySelectorAll('.service-btn');

    form.addEventListener('submit', function(event) {
        event.preventDefault();
        const cryptoName = document.getElementById('crypto-name').value;
        const timeFrame = document.getElementById('time-frame').value;

        let interval;
        let limit;
        switch(timeFrame) {
            case '1d':
                interval = '1d';
                limit = 1;
                break;
            case '1w':
                interval = '1w';
                limit = 7;
                break;
            case '1y':
                interval = '1w';
                limit = 52;
                break;
        }

        fetch(`https://api.binance.com/api/v3/klines?symbol=${cryptoName}USDT&interval=${interval}&limit=${limit}`)
            .then(response => response.json())
            .then(data => {
                const highestPrices = data.map(candle => parseFloat(candle[2]));
                const highestPrice = Math.max(...highestPrices);

                resultDiv.innerHTML = `
                    <h3>${cryptoName} - Høyeste pris de siste ${timeFrame}:</h3>
                    <p>$${highestPrice.toFixed(2)}</p>
                `;
            })
            .catch(error => {
                resultDiv.innerHTML = `
                    <p>Noe gikk galt. Vennligst prøv igjen.</p>
                `;
                console.error('Error fetching crypto data:', error);
            });
    });

    serviceButtons.forEach(button => {
        button.addEventListener('click', function() {
            const service = this.getAttribute('data-service');
            const userEmail = prompt('Vennligst skriv inn din e-postadresse:');
            if (!userEmail) return; // Avbryt hvis brukeren ikke skrev inn e-post

            const userMessage = prompt(`Beskriv hva du trenger hjelp med for ${service}:`);
            if (!userMessage) return; // Avbryt hvis brukeren ikke skrev inn melding

            sendEmail(service, userMessage, userEmail);
        });
    });

    function sendEmail(service, message, userEmail) {
        const xhr = new XMLHttpRequest();
        const url = 'send_email.php';
        const params = `service=${encodeURIComponent(service)}&message=${encodeURIComponent(message)}&userEmail=${encodeURIComponent(userEmail)}`;

        xhr.open('POST', url, true);
        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                alert(xhr.responseText);
            } else if (xhr.readyState === 4) {
                alert('Det oppstod en feil ved sending av e-posten.');
            }
        };

        xhr.send(params);
    }
});
