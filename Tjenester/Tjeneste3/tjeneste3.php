<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wallet Generator</title>
    <link rel="stylesheet" href="style3.css">
    <link rel="stylesheet" href="../style.css">
    <!-- Include crypto-js and elliptic libraries via CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.0.0/crypto-js.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/elliptic/6.5.4/elliptic.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bip39@3.0.4"></script>
    <script src="https://cdn.jsdelivr.net/npm/xrpl@2.2.0"></script>


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
        <h1>Ta en kopi av alle tall</h1>

        <button id="generateBtcButton">Generate Bitcoin Wallet</button>
        <button id="generateXrpButton">Generate Ripple Wallet</button>
        <div id="keys" style="display:none;">
            
            <p>Du trenger kun denne for å motta<strong>Address:</strong> <span id="address"></span></p>
            <p><strong>Public Key:</strong> <span id="publicKey"></span></p>
            <p>Ta godt vare på denne<strong>Mnemonic Phrase:</strong> <span id="mnemonic"></span></p>
            <p><strong>Private Key:</strong> <span id="privateKey"></span></p>
        </div>
    </main>
    <footer>
        <p>&copy; 2024 Tjenester. All rights reserved. Ingen ansvar for genererte addresser</p>
    </footer>
    <!-- Include custom scripts -->
    <script src="btc.js"></script>
    <script src="xrp.js"></script>
</body>
</html>
