<?php
session_start();
require '../../db.php';
?>
<!DOCTYPE html>
<html lang="nb">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parkering - Borettslaget</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-gray">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Borettslag Parkering</a>
            <div class="navbar-nav">
                <a class="nav-link" href="index.php">Hjem</a>
                <a class="nav-link" href="parking.php">Parkeringsplasser</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a class="nav-link" href="profile.php">Min side</a>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a class="nav-link" href="admin.php">Admin</a>
                    <?php endif; ?>
                    <a class="nav-link" href="logout.php">Logg ut</a>
                <?php else: ?>
                    <a class="nav-link" href="login.php">Logg inn</a>
                    <a class="nav-link" href="register.php">Registrer</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>Velkommen til borettslagets parkeringsoversikt</h1>
        <div class="map-container">
            <div id="map"></div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialiser Leaflet-kart
        var map = L.map('map').setView([59.8974, 10.8105], 15); // Sentrer mellom anleggene
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        // Hent anlegg fra PHP
        fetch('get_facilities.php')
            .then(response => response.json())
            .then(data => {
                data.forEach(facility => {
                    L.marker([facility.coordinates.lat, facility.coordinates.lng])
                        .addTo(map)
                        .bindPopup(`<b>${facility.name}</b><br><a href="parking.php?facility_id=${facility.facility_id}">Se plasser</a>`);
                });
            });
    </script>
</body>
</html>