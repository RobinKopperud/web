document.getElementById('generateXrpButton').addEventListener('click', generateRippleWallet);

async function generateRippleWallet() {
    const { Wallet } = xrpl;
    
    // Generate a new wallet
    const wallet = Wallet.generate();
    
    document.getElementById('address').textContent = wallet.address;
    document.getElementById('publicKey').textContent = wallet.publicKey;
    document.getElementById('privateKey').textContent = wallet.seed;
    document.getElementById('keys').style.display = 'block';
}
