<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mental Racing Team</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="mobile.css" media="screen and (max-width: 768px)">
</head>
<body>
    <header>
        <div class="container">
        <a href="index.php"> <h1>Mental Racing Team</h1></a>
        </div>
        </div>
    </header>

    <button class="menu-toggle" aria-label="Toggle menu">☰</button>

    <nav>
        <a href="#resultater">Resultater</a>
        <a href="ref/timeline.php">Tidslinje</a>
        <a href="ref/galleri.php">Galleri</a>
        <a href="#contact">Kontakt</a>
        <a href="admin/admin.php">Administrator</a>
    </nav>

    <section id="countdown-section" class="content-section">
        <h2>Nedtelling til neste race</h2>
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

    <section id="about" class="content-section">
        <h2>Om Mental Racing Team</h2>
        <p>Mental Racing Team ble grunnlagt av en ung racerfører som gjennomgikk en hjertetransplantasjon i en alder av 16 år. Denne livsendrende erfaringen har formet vår misjon om å bruke racing som en plattform for å rette oppmerksomhet mot menns mentale helse.</p>
    </section>

    <section id="mission" class="content-section">
        <h2>Vår Misjon</h2>
        <p>Vi er dedikert til å bryte ned stigmaet rundt menns mentale helse gjennom kraften av motorsport. Vår visjon er å skape et fellesskap der menn føler seg trygge på å diskutere sine mentale helseproblemer, og hvor støtte alltid er tilgjengelig.</p>
    </section>

    <section id="contact" class="content-section">
        <h2>Kontakt Oss</h2>
        <p>Interessert i å sponse eller støtte vårt oppdrag? Ta kontakt med oss på Instagram eller send oss en e-post.</p>
        <p>Instagram: @Mental.racing22</p>
        <p>E-post: kontakt@mentalracingteam.no</p>
    </section>

    <footer class="content-section">
        <div class="social-links">
            <a href="https://www.instagram.com/Mental.racing22" target="_blank">Instagram</a>
            <a href="https://www.tiktok.com/@mentalracingteam" target="_blank">TikTok</a>
        </div>
        <p>&copy; 2024 Mental Racing Team. Alle rettigheter reservert.</p>
    </footer>

    <script src="script.js"></script>
    <script src="nextrace.js"></script>
</body>
</html>
