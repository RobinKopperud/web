<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

// Sjekk admin
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT rolle, borettslag_id FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user['rolle'] !== 'admin') {
    die("Ingen tilgang.");
}

// Håndter skjema
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $navn = $_POST['navn'];
    $lat = $_POST['lat'];
    $lng = $_POST['lng'];
    $har_ladere = isset($_POST['har_ladere']) ? 1 : 0;

    $stmt = $conn->prepare("INSERT INTO anlegg (navn, lat, lng, har_ladere, borettslag_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sddii", $navn, $lat, $lng, $har_ladere, $user['borettslag_id']);
    $stmt->execute();
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
  <meta charset="UTF-8">
  <title>Adminpanel – EnkelParkering</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <style>
    .admin-container { display: flex; height: calc(100vh - 60px); }
    .map-area { flex: 2; }
    #map { width: 100%; height: 100%; }
    .sidebar { flex: 1; padding: 1rem; overflow-y: auto; background: #f9f9f9; border-left: 1px solid #ddd; }
    form input, form button { width: 100%; margin-bottom: 1rem; padding: 8px; }
  </style>
</head>
<body>
  <header class="header">
    <div>👋 Adminpanel</div>
    <div><a href="index.php">Tilbake</a></div>
  </header>

  <main class="admin-container">
    <section class="map-area">
      <div id="map"></div>
    </section>

    <aside class="sidebar">
      <h2>Opprett nytt anlegg</h2>
      <form method="post">
        <label>Navn:</label>
        <input type="text" name="navn" required>

        <label>Breddegrad (lat):</label>
        <input type="text" name="lat" id="lat" readonly required>

        <label>Lengdegrad (lng):</label>
        <input type="text" name="lng" id="lng" readonly required>

        <label><input type="checkbox" name="har_ladere"> Har ladere</label><br><br>

        <button type="submit">➕ Opprett anlegg</button>
      </form>
    </aside>
  </main>

  <script>
    var map = L.map('map').setView([59.91, 10.75], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    var tempMarker;

    // Klikk på kart → sett markør og oppdater input
    map.on('click', function(e) {
      if (tempMarker) {
        map.removeLayer(tempMarker);
      }
      tempMarker = L.marker(e.latlng).addTo(map);
      document.getElementById('lat').value = e.latlng.lat.toFixed(6);
      document.getElementById('lng').value = e.latlng.lng.toFixed(6);
    });
  </script>
</body>
</html>
