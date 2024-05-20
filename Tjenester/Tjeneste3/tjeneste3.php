<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tjeneste 3 - Crypto Wallet Generator</title>
    <link rel="stylesheet" href="../style.css"> <!-- Link to global style.css -->
    <link rel="stylesheet" href="style3.css"> <!-- Link to specific style3.css -->
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
    <div class="tjeneste3">
        <h1>Bitcoin Wallet Generator</h1>
        <button id="generateButton">Generate Wallet</button>
        <div id="keys" style="display: none;">
            <p><strong>Address:</strong> <span id="address"></span></p>
            <p><strong>Private Key:</strong> <span id="privateKey"></span></p>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bitcoinjs-lib/5.2.0/bitcoinjs.min.js"></script>
    <script src="tjeneste3.js"></script>
</body>
</html>