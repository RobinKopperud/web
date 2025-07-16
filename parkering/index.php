<?php
session_start();
include_once '../../db.php'; // Adjust the path as needed
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
    <?php include 'nav.php'; ?>

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
        var map = L.map('map').setView([59.897, 10.810], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        fetch('get_facilities.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Nettverksfeil ved henting av get_facilities.php: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    console.error('Feil fra get_facilities.php:', data.error);
                    return;
                }
                if (!Array.isArray(data)) {
                    console.error('Uventet responsformat:', data);
                    return;
                }
                data.forEach(facility => {
                    if (facility.lat && facility.lng) {
                        L.marker([facility.lat, facility.lng])
                            .addTo(map)
                            .bindPopup(`<b>${facility.name}</b><br><a href="parking.php?facility_id=${facility.facility_id}">Se plasser</a>`);
                    } else {
                        console.warn('Manglende koordinater for anlegg:', facility);
                    }
                });
            })
            .catch(error => {
                console.error('Feil ved henting av anlegg:', error);
            });
    </script>
</body>
</html>