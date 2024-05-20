document.getElementById('generateBtcButton').addEventListener('click', function() {
    generateBitcoinWallet();
});

function generateBitcoinWallet() {
    const privateKey = new bitcore.PrivateKey();
    const address = privateKey.toAddress();
    const publicKey = privateKey.toPublicKey();
    
    document.getElementById('address').textContent = address.toString();
    document.getElementById('publicKey').textContent = publicKey.toString();
    document.getElementById('privateKey').textContent = privateKey.toString();
    document.getElementById('keys').style.display = 'block';
}
