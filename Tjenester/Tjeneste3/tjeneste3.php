<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wallet Generator</title>
    <link rel="stylesheet" href="style3.css">
    <link rel="stylesheet" href="../style.css">
    <!-- Include browser-compatible versions of libraries -->
    <script src="https://cdn.jsdelivr.net/npm/elliptic@6.5.4/dist/elliptic.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/crypto-js@4.1.1/crypto-js.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/buffer/6.0.3/buffer.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js" defer></script>
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="../index.php">Home</a></li>
                <li><a href="#">Leaderboard</a></li>
            </ul>
        </nav>
    </header>
    <main class="tjeneste3">
        <h1>Generate Crypto Wallet</h1>
        <button id="generateBtcButton">Generate Bitcoin Wallet</button>
        <button id="generateXrpButton">Generate Ripple Wallet</button>
        <div id="keys" style="display:none;">
            <p><strong>Address:</strong> <span id="address"></span></p>
            <p><strong>Public Key:</strong> <span id="publicKey"></span></p>
            <p><strong>Private Key:</strong> <span id="privateKey"></span></p>
        </div>
    </main>
    <footer>
        <p>&copy; 2024 Tjenester. All rights reserved.</p>
    </footer>
    <!-- Include custom scripts -->
    <script src="btc.js" defer></script>
    <script src="xrp.js" defer></script>
    <script defer>
        document.getElementById('generateBtcButton').addEventListener('click', generateBitcoinWallet);
        document.getElementById('generateXrpButton').addEventListener('click', generateRippleWallet);
    </script>
</body>
</html>
