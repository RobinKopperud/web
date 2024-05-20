document.getElementById('generateButton').addEventListener('click', generateWallet);

function generateWallet() {
    const bitcoin = window.bitcoin;

    const keyPair = bitcoin.ECPair.makeRandom();
    const { address } = bitcoin.payments.p2pkh({ pubkey: keyPair.publicKey });
    const privateKey = keyPair.toWIF();

    document.getElementById('address').textContent = address;
    document.getElementById('privateKey').textContent = privateKey;
    document.getElementById('keys').style.display = 'block';
}
