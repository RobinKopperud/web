<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kopperud</title>
    <link rel="stylesheet" href="styles.css">

</head>
<body>
    <header>
        <h1>Robin Kopperud</h1>
        <p>Nyutdannet innen IT | Nettverk | Brukerstøtte | IoT | AI | M365 |</p>
    </header>
    
    <nav>
        <a href="index.php">Om Meg</a>
        <a href="ai.php">AI Prosjekter</a>
        <a href="krypto.html">KryptoTjeneste</a>
        <a href="Tjenester/index.php">Tjenester</a>
        <a href="pizza/index.php">Pizza</a>


        <?php if ($is_logged_in): ?>
            <a href="experimental.php">Experimental</a>
            <a href="logout.php">Logg ut</a>
        <?php else: ?>
            <a href="loginout.html">Logg inn/Registrer</a>
        <?php endif; ?>
    </nav>
    <div id="new-chat">
        <a href=krypto.html#chatbot> Prøv chatten</a>
    </div>
    <div class="container">
        <section id="about">
            <h2>Om Meg</h2>
            <p>Jeg er nyutdannet innen IT med sterke ferdigheter innen nettverk, brukerstøtte, IoT og AI. Jeg er på jakt etter spennende jobbmuligheter hvor jeg kan bruke min kompetanse til å bidra til innovative prosjekter.</p>
        </section>
        <section id="portfolio">
            <h2>Portefølje</h2>
            <p>Her kan du se noen av mine prosjekter:</p>
            <ul>
                <li>Prosjekt 1 - Deepfake.</li>
                <li>Prosjekt 2 - IOT.</li>
                <li>Prosjekt 3 - OpenAI API, Chatbot som går ut ifra ditt gitte humør når den svarer</li>
                <li>Prosjekt x - Under utvikling</li>

            </ul>
        </section>
        <section id="contact">
            <h2>Kontakt</h2>
            <p>Du kan kontakte meg via følgende kanaler:</p>
            <ul>
                <li>Email: <a href="mailto:robinkopperud@robinkopperud.no">robinkopperud@gmail.com</a></li>
                <li>LinkedIn: <a href="https://www.linkedin.com/in/robin-kopperud-33615b2bb/" target="_blank">Robin Kopperud</a></li>
            </ul>
        </section>
    </div>
    <footer>
        <div class="footer-left"></div>
        <div class="footer-center">
            <p>&copy; 2024 Robin Kopperud. All rights reserved.</p>
        </div>
        <div class="footer-right">
            <span class="version">Versjon 0.9</span>
        </div>
    </footer>
    
</body>
</html>
