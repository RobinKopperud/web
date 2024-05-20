document.getElementById('generateXrpButton').addEventListener('click', generateRippleWallet);

async function generateRippleWallet() {
    const bip39 = window.bip39;
    const { deriveKeypair, deriveAddress, generateSeed } = xrpl;

    // Generate a mnemonic
    const mnemonic = bip39.generateMnemonic();
    
    // Derive seed from mnemonic
    const seed = bip39.mnemonicToSeedSync(mnemonic).toString('hex');

    // Generate a seed for XRPL wallet (this is different from BIP39 seed)
    const xrplSeed = generateSeed({ entropy: Buffer.from(seed, 'hex') });

    // Derive keypair from seed
    const keypair = deriveKeypair(xrplSeed);
    const address = deriveAddress(keypair.publicKey);

    document.getElementById('mnemonic').textContent = mnemonic;
    document.getElementById('address').textContent = address;
    document.getElementById('publicKey').textContent = keypair.publicKey;
    document.getElementById('privateKey').textContent = xrplSeed;
    document.getElementById('keys').style.display = 'block';
}
