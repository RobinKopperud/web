document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('crypto-form');
    const resultDiv = document.getElementById('price-result');

    form.addEventListener('submit', function(event) {
        event.preventDefault();
        const cryptoName = document.getElementById('crypto-name').value.trim();
        const timeFrame = document.getElementById('time-frame').value;

        // Map timeFrame to CoinGecko API parameter
        let days;
        switch(timeFrame) {
            case '1d':
                days = 1;
                break;
            case '7d':
                days = 7;
                break;
            case '1y':
                days = 365;
                break;
        }

        fetch(`https://api.coingecko.com/api/v3/coins/${cryptoName}/market_chart?vs_currency=usd&days=${days}`)
            .then(response => response.json())
            .then(data => {
                const prices = data.prices;
                const highestPrice = Math.max(...prices.map(price => price[1]));

                resultDiv.innerHTML = `
                    <h3>${cryptoName.charAt(0).toUpperCase() + cryptoName.slice(1)} - Høyeste pris de siste ${timeFrame}:</h3>
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
});
