<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

// Sjekk innlogging
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Hent brukerinfo
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Hent anlegg + plasser
$sql = "SELECT a.id, a.navn, a.har_ladere,
        COUNT(p.id) as total,
        SUM(p.status = 'ledig') as ledige,
        SUM(p.status = 'opptatt') as opptatte,
        SUM(p.status = 'reservert') as reserverte,
        SUM(p.har_lader = 1) as med_lader
        FROM anlegg a
        LEFT JOIN plasser p ON a.id = p.anlegg_id
        WHERE a.borettslag_id = ?
        GROUP BY a.id";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user['borettslag_id']);
$stmt->execute();
$anlegg = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="no">
<head>
  <meta charset="UTF-8">
  <title>Hjem – EnkelParkering</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
</head>
<body>
  <header class="header">
  <div>👋 Hei, <?= htmlspecialchars($user['navn']) ?> (<?= $user['rolle'] ?>)</div>
  <div>
    <?php if ($user['rolle'] === 'admin'): ?>
      <a href="admin.php">Adminpanel</a> |
    <?php endif; ?>
    <a href="logout.php">Logg ut</a>
  </div>
</header>

<main class="dashboard">
  <section class="map-area">
    <div id="map"></div>
  </section>
  <aside class="sidebar">
    <h2>Anlegg</h2>
    <?php foreach ($anlegg as $a): ?>
      <div class="facility-card">
        <h3><?= htmlspecialchars($a['navn']) ?></h3>
        <p>🚗 Totalt: <?= $a['total'] ?></p>
        <p>✅ Ledige: <?= $a['ledige'] ?></p>
        <p>🔴 Opptatt: <?= $a['opptatte'] ?></p>
        <p>🟠 Reservert: <?= $a['reserverte'] ?></p>
        <?php if ($a['har_ladere']): ?>
          <p>⚡ Med lader: <?= $a['med_lader'] ?></p>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </aside>
</main>


  <script>
  var map = L.map('map').setView([59.91, 10.75], 13);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap'
  }).addTo(map);

  var anlegg = <?= json_encode($anlegg) ?>;

  anlegg.forEach(function(a) {
    if (a.lat && a.lng) {
      L.marker([a.lat, a.lng])
        .addTo(map)
        .bindPopup(a.navn + "<br>Ledige: " + a.ledige + "<br>Opptatt: " + a.opptatte);
    }
  });
</script>

</body>
</html>
