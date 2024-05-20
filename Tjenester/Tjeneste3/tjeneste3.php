<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wallet Generator</title>
    <link rel="stylesheet" href="style3.css">
    <link rel="stylesheet" href="../style.css">

    <!-- Include required libraries -->
    <script src="https://cdn.jsdelivr.net/npm/elliptic/dist/elliptic.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/crypto-js@4.1.1/crypto-js.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/buffer/index.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/crypto-browserify/index.js"></script>
</head>
<body>
    <button id="generateButton">Generate Wallet</button>
    <div id="keys" style="display:none;">
        <p><strong>Address:</strong> <span id="address"></span></p>
        <p><strong>Public Key:</strong> <span id="publicKey"></span></p>
        <p><strong>Private Key:</strong> <span id="privateKey"></span></p>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/elliptic/dist/elliptic.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/crypto-js@4.1.1/crypto-js.js"></script>
    <script src="script.js"></script>
</body>
</html>
