document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('crypto-form');
    const resultDiv = document.getElementById('price-result');

    form.addEventListener('submit', function(event) {
        event.preventDefault();
        const cryptoName = document.getElementById('crypto-name').value;
        const timeFrame = document.getElementById('time-frame').value;

        // Map timeFrame to Binance API parameter
        let interval;
        let limit;
        switch(timeFrame) {
            case '1d':
                interval = '1d';
                limit = 1;
                break;
            case '7d':
                interval = '1d';
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
                const highestPrices = data.map(candle => parseFloat(candle[2])); // High prices are at index 2 in the response
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
            const userMessage = prompt(`Beskriv hva du trenger hjelp med for ${service}:`);

            if (userMessage) {
                sendEmail(service, userMessage);
            }
        });
    });

    function sendEmail(service, message) {
        const email = 'robinkopperud@robinkopperud.no';
        const subject = `Hjelp med ${service}`;
        const body = `Hei,\n\nJeg trenger hjelp med følgende:\n\n${message}\n\nMed vennlig hilsen,\n[Ditt Navn]`;

        window.location.href = `mailto:${email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
    }
});