<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tjeneste 2</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="style2.css"> <!-- Add this line to link style2.css -->

    <script defer src="tjeneste2.js"></script>
    <script defer src="gettext.js"></script>
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
    <main>
        <h1>Tjeneste 2 - Bilskilt</h1>
        <input type="text" id="input-value" placeholder="skriv inn bilskilt, 2 tall og 5 bokstaver i ett">
        <button id="fetch-data">Finn ut hvor mye lån personen har på bilen</button>
        <div>
            <input type="file" id="image-upload" accept="image/*">
            <button id="upload-image">Upload Image</button>
        </div>
        <div id="result"></div>
        <div id="resultAI"></div>

    </main>
    <footer>
        <p>&copy; 2024 Tjenester. All rights reserved.</p>
    </footer>
</body>
</html>
