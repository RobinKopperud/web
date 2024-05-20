document.getElementById('generateBtcButton').addEventListener('click', function() {
    generateBitcoinWallet();
});

function generateBitcoinWallet() {
    const bitcoin = window.bitcoin;
    const keyPair = bitcoin.ECPair.makeRandom();
    const { address } = bitcoin.payments.p2pkh({ pubkey: keyPair.publicKey });

    document.getElementById('address').textContent = address;
    document.getElementById('publicKey').textContent = keyPair.publicKey.toString('hex');
    document.getElementById('privateKey').textContent = keyPair.privateKey.toString('hex');
    document.getElementById('keys').style.display = 'block';
}
