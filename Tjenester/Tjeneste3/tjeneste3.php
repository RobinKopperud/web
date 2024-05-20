<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tjeneste 3</title>
    <link rel="stylesheet" href="style3.css">
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
        <label for="crypto-select">Choose a cryptocurrency:</label>
        <select id="crypto-select">
            <option value="btc">Bitcoin (BTC)</option>
            <option value="xrp">Ripple (XRP)</option>
        </select>
        <button id="generateButton">Generate Wallet</button>
        <div id="keys" style="display:none;">
            <p><strong>Address:</strong> <span id="address"></span></p>
            <p><strong>Public Key:</strong> <span id="publicKey"></span></p>
            <p><strong>Private Key:</strong> <span id="privateKey"></span></p>
        </div>
    </main>
    <footer>
        <p>&copy; 2024 Tjenester. All rights reserved.</p>
    </footer>
    <!-- Ensure elliptic and other required libraries are included -->
    <script src="https://cdn.jsdelivr.net/npm/elliptic/dist/elliptic.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/crypto-js@4.1.1/crypto-js.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/buffer/index.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/crypto-browserify/index.js"></script>
    <script>
        document.getElementById('crypto-select').addEventListener('change', function () {
            const crypto = this.value;
            if (crypto === 'btc') {
                loadScript('btc.js');
            } else if (crypto === 'xrp') {
                loadScript('xrp.js');
            }
        });

        function loadScript(scriptName) {
            const script = document.createElement('script');
            script.src = scriptName;
            document.head.appendChild(script);
        }
    </script>
</body>
</html>
