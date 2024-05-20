document.getElementById('generateXrpButton').addEventListener('click', function() {
    generateRippleWallet();
});

function generateRippleWallet() {
    const api = new ripple.RippleAPI();
    const {address, secret} = api.generateAddress();
    
    document.getElementById('address').textContent = address;
    document.getElementById('publicKey').textContent = ''; // Ripple doesn't have a simple public key
    document.getElementById('privateKey').textContent = secret;
    document.getElementById('keys').style.display = 'block';
}
