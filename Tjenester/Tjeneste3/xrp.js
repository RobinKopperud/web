document.getElementById('generateXrpButton').addEventListener('click', generateRippleWallet);

        async function generateRippleWallet() {
            const bip39 = window.bip39;
            const { Wallet } = xrpl;

            // Generate a mnemonic
            const mnemonic = bip39.generateMnemonic();
            
            // Derive seed from mnemonic
            const seed = bip39.mnemonicToSeedSync(mnemonic).toString('hex');
            
            // Generate a wallet from the seed
            const wallet = Wallet.fromSeed(seed);

            document.getElementById('mnemonic').textContent = mnemonic;
            document.getElementById('address').textContent = wallet.address;
            document.getElementById('publicKey').textContent = wallet.publicKey;
            document.getElementById('privateKey').textContent = wallet.seed;
            document.getElementById('keys').style.display = 'block';
        }
