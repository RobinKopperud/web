document.addEventListener('DOMContentLoaded', () => {
    const button = document.getElementById('fetch-data');
    button.addEventListener('click', async () => {
        const inputValue = document.getElementById('input-value').value;
        const url = `https://rettsstiftelser.brreg.no/nb/oppslag/motorvogn/${inputValue}`;
        const resultDiv = document.getElementById('result');

        try {
            const response = await fetch(url);
            const text = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(text, 'text/html');
            const xpath = "/html/body/main/section/article/div[1]/div[2]/div/div/div/div/div[2]/div[3]/div[2]/ul/li/span[1]";
            const node = doc.evaluate(xpath, doc, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue;
            const nokValue = node ? node.textContent : 'NOK value not found';

            resultDiv.textContent = `NOK Value: ${nokValue}`;
        } catch (error) {
            console.error('Error fetching data:', error);
            resultDiv.textContent = 'Error fetching data';
        }
    });
});
