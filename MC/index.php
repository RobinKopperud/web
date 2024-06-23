<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mental Racing Team</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="mobile.css" media="screen and (max-width: 768px)">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>Mental Racing Team</h1>
        </div>
    </header>

    <nav>
        <a href="#about">Om Oss</a>
        <a href="#mission">Vår Misjon</a>
        <a href="#gallery">Galleri</a>
        <a href="#contact">Kontakt</a>
        <a href="admin.php">Administrator</a>

    </nav>

    <section class="hero">
        <h2>Mer enn bare racing</h2>
        <p>Vi kjører for å skape bevissthet rundt menns mentale helse</p>
        <div id="countdown" class="countdown">
            <div>
                <span id="days"></span>
                <div class="smalltext">Dager</div>
            </div>
            <div>
                <span id="hours"></span>
                <div class="smalltext">Timer</div>
            </div>
            <div>
                <span id="minutes"></span>
                <div class="smalltext">Minutter</div>
            </div>
            <div>
                <span id="seconds"></span>
                <div class="smalltext">Sekunder</div>
            </div>
        </div>
        <p>til neste race!</p>
    </section>

    <main class="container">
        <section id="about" class="content-section">
            <h2>Om Mental Racing Team</h2>
            <p>Mental Racing Team ble grunnlagt av en ung racerfører som gjennomgikk en hjertetransplantasjon i en alder av 16 år. Denne livsendrende erfaringen har formet vår misjon om å bruke racing som en plattform for å rette oppmerksomhet mot menns mentale helse.</p>
        </section>

        <section id="mission" class="content-section">
            <h2>Vår Misjon</h2>
            <p>Vi er dedikert til å bryte ned stigmaet rundt menns mentale helse gjennom kraften av motorsport. Vår visjon er å skape et fellesskap der menn føler seg trygge på å diskutere sine mentale helseproblemer, og hvor støtte alltid er tilgjengelig.</p>
        </section>

        <section id="gallery" class="content-section">
            <h2>Galleri</h2>
            <div class="image-gallery">
                <a href="images/v1.jpg" data-lightbox="gallery" data-title="Racing bilde 1">
                    <img src="images/v1.jpg" alt="Racing bilde 1">
                </a>
                <a href="images/v2.jpg" data-lightbox="gallery" data-title="Racing bilde 2">
                    <img src="images/v2.jpg" alt="Racing bilde 2">
                </a>
                <a href="images/v3.jpg" data-lightbox="gallery" data-title="Racing bilde 3">
                    <img src="images/v3.jpg" alt="Racing bilde 3">
                </a>
            </div>
        </section>

        <section id="contact" class="content-section">
            <h2>Kontakt Oss</h2>
            <p>Interessert i å sponse eller støtte vårt oppdrag? Ta kontakt med oss på Instagram eller send oss en e-post.</p>
            <p>Instagram: @Mental.racing22</p>
            <p>E-post: kontakt@mentalracingteam.no</p>
        </section>
    </main>

    <footer>
        <div class="social-links">
            <a href="https://www.instagram.com/Mental.racing22" target="_blank">Instagram</a>
            <a href="https://www.tiktok.com/@mentalracingteam" target="_blank">TikTok</a>
        </div>
        <p>&copy; 2024 Mental Racing Team. Alle rettigheter reservert.</p>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox-plus-jquery.min.js"></script>
    <script src="script.js"></script>
</body>
</html>