<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wallet Generator</title>
    <link rel="stylesheet" href="style3.css">
    <link rel="stylesheet" href="../style.css">
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
    <script src="btc.js"></script>
    <script src="xrp.js"></script>
</body>
</html>
