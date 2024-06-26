<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Robin Kopperud - AI Prosjekter</title>
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

    </nav>
    <div id="new-chat">
        <a href="krypto.html#chatbot">Krypto spesialisert<br> Chat</a>
    </div>
    <div class="container">
        <section id="ai-projects">
            <h2>AI Prosjekter</h2>
            <p>Jeg utnytter GAN trening for musikk og video:</p>
            <ul>
                <li>AI Prosjekt 1 - Deepfake Video</li>
                <li>AI Prosjekt 2 - Deepfake Voice.</li>
                <li>AI Prosjekt 2 - Deepfake Faceswap.</li>

            </ul>
        </section>
        <section id="ai-video">
            <h2>AI Faceswap</h2>
            <iframe src="https://www.youtube.com/embed/0NlHO-ZMg-M" title="Lauren x Imposter Intervju" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>        
        </section>
        <section id="ai-video">
            <h2>AI musikk</h2>
            <label for="language-select">Velg språk:</label>
            <select id="language-select">
                <option value="fr">Fransk</option>
                <option value="sv">Svensk</option>
                <option value="no">Norsk</option>
                <option value="da">Dansk</option>
                <option value="en">Engelsk</option>
                <option value="OL">OlavSpesial</option>

            </select>
            <div id="video-container">
                <iframe id="video-frame" src="" title="YouTube Video" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
            </div>
        </section>
        <section id="chatbot">
            <h2>AI spesialisert Chatbot</h2>
            <div id="chat-container">
                <div id="chat-box"></div>
                <input type="text" id="chat-input" placeholder="Skriv en melding..." />
                <button id="send-btn">Send</button>
            </div>
        </section>
        <section id="comments">
            <h2>Legg inn en kommentar</h2>
            <form id="comment-form" action="submit_comment.php" method="POST">
                <label for="username">Brukernavn:</label>
                <input type="text" id="username" name="username" required>
                <label for="comment">Kommentar:</label>
                <textarea id="comment" name="comment" rows="4" required></textarea>
                <button type="submit">Send</button>
            </form>
            <h2>Kommentarer</h2>
            <div id="comment-section">
                <?php include 'fetch_comments.php'; ?>
            </div>
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
    <script src="chatbot.js"></script>
    <script src="video.js"></script>
</body>
</html>
