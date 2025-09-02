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

// Hent anlegg + oppsummering fra plasser
$sql = "SELECT a.id, a.navn, a.lat, a.lng,
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
      <a href="admin/admin.php">Adminpanel</a> |
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
    <div class="facility-card" id="anlegg-<?= $a['id'] ?>">
        <h3><?= htmlspecialchars($a['navn']) ?></h3>
        <p>🚗 Totalt: <?= $a['total'] ?></p>
        <p>✅ Ledige: <?= $a['ledige'] ?></p>
        <p>🔴 Opptatt: <?= $a['opptatte'] ?></p>
        <p>🟠 Reservert: <?= $a['reserverte'] ?></p>
        <p>⚡ Med lader: <?= $a['med_lader'] ?></p>
    </div>

      <!-- Venteliste-skjema -->
      <form method="post" action="venteliste.php">
        <input type="hidden" name="anlegg_id" value="<?= $a['id'] ?>">
        <label>
          <input type="checkbox" name="ønsker_lader" value="1"> Ønsker lader
        </label><br>
        <button type="submit">Meld meg på venteliste</button>
      </form>
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
      let marker = L.marker([a.lat, a.lng]).addTo(map)
        .bindPopup(`
          <strong>${a.navn}</strong><br>
          🚗 Totalt: ${a.total}<br>
          ✅ Ledige: ${a.ledige}<br>
          🔴 Opptatt: ${a.opptatte}<br>
          🟠 Reservert: ${a.reserverte}<br>
          ⚡ Med lader: ${a.med_lader}
        `);

      // Klikk på markør → scroll sidebar
      marker.on('click', function() {
        var el = document.getElementById("anlegg-" + a.id);
        if (el) {
          el.scrollIntoView({ behavior: 'smooth', block: 'start' });
          el.classList.add("highlight");
          setTimeout(() => el.classList.remove("highlight"), 2000);
        }
      });
    }
  });
</script>


</body>
</html>
