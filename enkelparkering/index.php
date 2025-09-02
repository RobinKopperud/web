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
  <style>
    .dashboard { display: flex; height: calc(100vh - 80px); }
    .map-area { flex: 3; padding: 1rem; }
    #map { width: 100%; height: 100%; border-radius: 8px; border: 1px solid #ccc; }
    .sidebar { flex: 2; padding: 1rem; background: #f9f9f9; border-left: 1px solid #ddd; overflow-y: auto; }
    .facility-card { background: white; border: 1px solid #ddd; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; }
    .header { background: #2c3e50; color: white; padding: 1rem; display: flex; justify-content: space-between; }
    .header a { color: white; text-decoration: none; }
  </style>
</head>
<body>
  <div class="header">
    <div>👋 Hei, <?= htmlspecialchars($user['navn']) ?> (<?= $user['rolle'] ?>)</div>
    <div>
      <?php if ($user['rolle'] === 'admin'): ?>
        <a href="admin.php">Adminpanel</a> |
      <?php endif; ?>
      <a href="logout.php">Logg ut</a>
    </div>
  </div>

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
    var map = L.map('map').setView([59.91, 10.75], 13); // Oslo sentrum
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    // Dummy markører for anlegg
    var anleggMarkers = [
      {navn: "Garasjeanlegg A", coords: [59.911, 10.75]},
      {navn: "Utendørs felt B", coords: [59.915, 10.76]},
      {navn: "Blokk 1 kjeller", coords: [59.909, 10.74]}
    ];
    anleggMarkers.forEach(a => {
      L.marker(a.coords).addTo(map).bindPopup(a.navn);
    });
  </script>
</body>
</html>
