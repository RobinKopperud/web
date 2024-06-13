<?php
session_start();
include_once '../../db.php'; // Correct path to include db.php

// Check if the user is logged in
$is_logged_in = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nordkisa Pizza og Grill</title>
    <meta name="description" content="Beste take awway i Nordkisa. Pizza og grillmat på sitt beste!">
    <link rel="stylesheet" href="styles.css">
    <script src="script.js"></script>

</head>
<body>
    <nav>
        <h1>Nordkisa Pizza og Grill</h1>
        <div class="nav-buttons">
            <button onclick="scrollToSection('pizza-section')">Pizza</button>
            <button onclick="scrollToSection('kebab-section')">Kebab</button>
            <button onclick="scrollToSection('grill-section')">Grill</button>
            <button onclick="scrollToSection('special-offers')">Special Offers</button>
            <button onclick="window.location.href='siste-nytt.php'">Siste Nytt</button> <!-- New tab -->
        </div>
    </nav>
    <div class="contact">
        <?php if ($is_logged_in): ?>
            <a href="logout.php">Logg ut</a>
        <?php endif; ?>
        <a href="tel:+1234567890">Ring oss: +1234567890</a>
    </div>

    <?php if ($is_logged_in): ?>
        <div class="edit">
            <div class="form-section">
                <h3>Legg til ny rett</h3>
                <?php
                if (isset($_GET['message'])) {
                    echo '<p>' . htmlspecialchars($_GET['message']) . '</p>';
                }
                ?>
                <form id="addCardForm" action="add_pizza.php" method="POST">
                    <label for="section">Section:</label>
                    <select id="section" name="section">
                        <option value="pizza">Pizza</option>
                        <option value="kebab">Kebab</option>
                        <option value="grill">Grill</option>
                    </select><br>
                    <label for="title">Title:</label>
                    <input type="text" id="title" name="title" required><br>
                    <label for="price">Price:</label>
                    <input type="text" id="price" name="price" required><br>
                    <label for="description">Description:</label>
                    <input type="text" id="description" name="description" required><br>
                    <button type="submit">Add Card</button>
                </form>
            </div>
            <div class="form-section">
                <h3>Fjern en rett</h3>
                <form id="removeCardForm" action="remove_pizza.php" method="POST">
                    <label for="removeNumber">Number:</label>
                    <input type="number" id="removeNumber" name="removeNumber" required><br>
                    <button type="submit">Remove Card</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Include the sections dynamically generated from the database -->
    <?php include 'fetch_pizzas.php'; ?>

    <!-- Photo Gallery Section -->
    <section id="photo-gallery" class="gallery-section">
        <h2>Bilder</h2>
        <div class="gallery-container">
            <!-- Add your images here -->
            <div class="gallery-item">
                <img src="bilder/ter3.png" alt="Photo 1">
            </div>
            <div class="gallery-item">
                <img src="bilder/tes.jpg" alt="Photo 2">
            </div>
            <div class="gallery-item">
                <img src="bilder/tes2.png" alt="Photo 3">
            </div>
            <!-- Add more images as needed -->
        </div>
    </section>

    <section id="special-offers" class="special-offers">
        <h2>Tilbud</h2>
        <div class="offers-container">
            <div class="offer">
                <h3>Kjøp en pizza, få med en brus</h3>
                <p>Order any large pizza and get a medium pizza for free.</p>
                <p class="validity">Gyldig til: June 30, 2024</p>
            </div>
            <div class="offer">
                <h3>20% rabatt på kebab</h3>
                <p>Få 20% rabatt på alt inne kebab</p>
                <p class="validity">Gyldig til:: July 15, 2024</p>
            </div>
            <div class="offer">
                <h3>Familie kombo</h3>
                <p>Kjøp 2 store pizza og få med 4 drikke og 2 dressing</p>
                <p class="validity">Gyldig til: July 31, 2024</p>
            </div>
        </div>
    </section>

    <button id="back-to-top" title="Gå til toppen">⬆</button>

    <footer>
        <div class="footer-container">
            <a href="mailto:contact@nordkisapizzaoggrill.com" class="footer-item">Send oss en mail her</a>
            <div class="opening-hours-footer footer-item">
                <h3>Åpningstider</h3>
                <p>Mandag - Fredag: 10:00 - 22:00</p>
                <p>Lørdag: 12:00 - 23:00</p>
                <p>Søndag: 12:00 - 21:00</p>
            </div>
            <a href="tel:+1234567890" class="footer-item">Ring oss på: +1234567890</a>
        </div>
    </footer>
</body>
</html>
