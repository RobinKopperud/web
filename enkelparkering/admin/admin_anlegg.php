<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

// Sjekk admin
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
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

// Håndter opprettelse
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'create') {
    $navn = $_POST['navn'];
    $lat = $_POST['lat'];
    $lng = $_POST['lng'];
    $har_ladere = isset($_POST['har_ladere']) ? 1 : 0;
    $type = $_POST['type'];

    $stmt = $conn->prepare("INSERT INTO anlegg (navn, lat, lng, har_ladere, type, borettslag_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sddisi", $navn, $lat, $lng, $har_ladere, $type, $user['borettslag_id']);
    $stmt->execute();
}


// Håndter sletting
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM anlegg WHERE id = ? AND borettslag_id = ?");
    $stmt->bind_param("ii", $id, $user['borettslag_id']);
    $stmt->execute();
}

// Hent eksisterende anlegg
$stmt = $conn->prepare("SELECT * FROM anlegg WHERE borettslag_id = ?");
$stmt->bind_param("i", $user['borettslag_id']);
$stmt->execute();
$anlegg = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// --- Layout variabler ---
$title = "Anlegg";
ob_start();
?>

<h1>Administrer anlegg</h1>

<div class="admin-container">
  <section class="map-area">
    <div id="map"></div>
  </section>

  <aside class="sidebar">
    <h2>Opprett nytt anlegg</h2>
    <form method="post">
      <input type="hidden" name="action" value="create">

      <label>Navn:</label>
      <input type="text" name="navn" required>

      <label>Breddegrad (lat):</label>
      <input type="text" name="lat" id="lat" readonly required>

      <label>Lengdegrad (lng):</label>
      <input type="text" name="lng" id="lng" readonly required>

      <label>Type anlegg:</label>
        <select name="type" required>
            <option value="ute">Ute</option>
            <option value="garasje">Garasje</option>
        </select>

      <label><input type="checkbox" name="har_ladere"> Har ladere</label><br><br>

      <button type="submit">➕ Opprett anlegg</button>
    </form>

    <h2>Eksisterende anlegg</h2>
    <?php foreach ($anlegg as $a): ?>
  <div class="facility-card">
  <strong><?= htmlspecialchars($a['navn']) ?></strong><br>
  📍 <?= $a['lat'] ?> , <?= $a['lng'] ?><br>
  🏗 Type: <?= ucfirst($a['type']) ?><br>
  ⚡ <?= $a['har_ladere'] ? 'Har ladere' : 'Ingen ladere' ?><br><br>
  <a href="admin_plasser.php?anlegg_id=<?= $a['id'] ?>" class="btn">🔧 Administrer plasser</a><br>
  <a href="?delete=<?= $a['id'] ?>" onclick="return confirm('Sikker på at du vil slette dette anlegget?')">🗑 Slett</a>
</div>

<?php endforeach; ?>

  </aside>
</div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

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

  // Vis eksisterende anlegg som markører
  var anlegg = <?= json_encode($anlegg) ?>;
  anlegg.forEach(function(a) {
    if (a.lat && a.lng) {
      L.marker([a.lat, a.lng])
        .addTo(map)
        .bindPopup(a.navn);
    }
  });
</script>

<?php
$content = ob_get_clean();
include "admin_layout.php";
