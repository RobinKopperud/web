document.addEventListener('DOMContentLoaded', () => {
    const button = document.getElementById('fetch-data');
    button.addEventListener('click', async () => {
        let inputValue = document.getElementById('input-value').value;

        if (inputValue.length !== 7) {
            alert("Please enter exactly 7 characters.");
            return;
        }

        await fetchData(inputValue);
    });

    // Listen for the custom event
    document.addEventListener('licensePlateObtained', async (event) => {
        const licensePlateNumber = event.detail.licensePlateNumber;

        // Use the license plate number to fetch data
        await fetchData(licensePlateNumber);
    });
});

async function fetchData(inputValue) {
    const url = `https://rettsstiftelser.brreg.no/nb/oppslag/motorvogn/${inputValue}`;
    const proxyUrl = `proxy.php?url=${encodeURIComponent(url)}`;
    const resultDiv = document.getElementById('result');
    resultDiv.textContent = "Fetching data...";

    try {
        const response = await fetch(proxyUrl);

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const text = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(text, 'text/html');
        const xpath = "/html/body/main/section/article/div[1]/div[2]/div/div/div/div/div[2]/div[3]/div[2]/ul/li/span[1]";
        const node = doc.evaluate(xpath, doc, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue;
        const nokValue = node ? node.textContent.replace(/\D/g, '') : null;

        let message;
        if (nokValue !== null) {
            const nokValueInt = parseInt(nokValue, 10);
            if (nokValueInt < 50000) {
                message = "Akseptabelt lån";
                bodyClass = 'akseptabelt';

            } else if (nokValueInt >= 50000 && nokValueInt <= 250000) {
                message = "Begynner å bli mye lån";
                bodyClass = 'bilen-lan';

            } else if (nokValueInt > 250000) {
                message = "Personen eier jo nesten ikke bilen";
                bodyClass = 'eier-ikke-bilen';

            } else {
                message = "NOK value not found or 0";
            }
            resultDiv.textContent = `NOK Value: ${nokValueInt} - ${message}`;
        } else {
            message = "Null lån";
            bodyClass = 'akseptabelt';
            resultDiv.textContent = message;
        }
        // Apply the appropriate class to the main element
        const mainElement = document.querySelector('main');
        mainElement.className = ''; // Reset any existing classes
        if (bodyClass) {
            mainElement.classList.add(bodyClass);
        }
    } catch (error) {
        console.error('Error fetching data:', error);
        resultDiv.textContent = `Error fetching data: ${error.message}`;
    }
}
