document.getElementById('generateXrpButton').addEventListener('click', function() {
    generateRippleWallet();
});

function generateRippleWallet() {
    const { generateSeed, deriveKeypair, deriveAddress } = rippleKeypairs;
    
    const seed = generateSeed();
    const keypair = deriveKeypair(seed);
    const address = deriveAddress(keypair.publicKey);
    
    document.getElementById('address').textContent = address;
    document.getElementById('publicKey').textContent = keypair.publicKey;
    document.getElementById('privateKey').textContent = seed;
    document.getElementById('keys').style.display = 'block';
}
