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
$sql = "SELECT u.*, b.navn AS borettslag_navn FROM users u
        LEFT JOIN borettslag b ON u.borettslag_id = b.id
        WHERE u.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Sjekk om bruker står på ventelisten
$stmt = $conn->prepare("SELECT id, anlegg_id FROM venteliste WHERE user_id = ? AND borettslag_id = ?");
$stmt->bind_param("ii", $user_id, $user['borettslag_id']);
$stmt->execute();
$venteliste_entry = $stmt->get_result()->fetch_assoc();
$er_på_venteliste = !empty($venteliste_entry);
$venteliste_anlegg_id = isset($venteliste_entry['anlegg_id']) ? (int)$venteliste_entry['anlegg_id'] : null;
$har_global_venteliste = $er_på_venteliste && $venteliste_anlegg_id === 0;


// Hent anlegg + oppsummering fra plasser
$sql = "SELECT a.id, a.navn, a.type, a.lat, a.lng, a.har_ladere,
        COUNT(p.id) as total,
        SUM(p.status = 'ledig') as ledige,
        SUM(p.status = 'opptatt') as opptatte,
        SUM(p.status = 'ledig' AND p.har_lader = 1) as ledige_med_lader
        FROM anlegg a
        LEFT JOIN plasser p ON a.id = p.anlegg_id
        WHERE a.borettslag_id = ?
        GROUP BY a.id";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user['borettslag_id']);
$stmt->execute();
$anlegg = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$finnes_ladere_i_borettslag = false;
foreach ($anlegg as $anleggData) {
    if (!empty($anleggData['har_ladere'])) {
        $finnes_ladere_i_borettslag = true;
        break;
    }
}

?>
<!DOCTYPE html>
<html lang="no">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hjem – Plogveien Borettslag</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <script src="js.js" defer></script>
</head>
<body>
  <?php $rolle = $user['rolle'] ?? ''; ?>
  <header class="header">
    <div class="logo">👋 Hei, <?= htmlspecialchars($user['navn']) ?><?= $rolle === 'admin' ? ' (admin)' : '' ?></div>
    <button class="menu-toggle" id="menuToggle">☰</button>
    <nav class="nav">
      <a href="index.php">🏠 Hjem</a>
      <a href="min_side.php">👤 Min side</a>
      <a href="min_venteliste.php">📋 Min venteliste</a>
      <?php if ($user['rolle'] === 'admin'): ?>
        <a href="admin/admin.php">Adminpanel</a>
      <?php endif; ?>
      <a href="logout.php">Logg ut</a>
    </nav>
  </header>

  <div class="search-container">
    <input type="text" id="anleggSok" class="search-box" placeholder="Søk etter anlegg...">
  </div>


  <main class="dashboard">
  <section class="map-area">
    <div id="map"></div>
  </section>
  <aside class="sidebar">
  <h2>Anlegg</h2>
    <?php if (empty($user['adresse'])): ?>
      <p class="message">📍 Legg til adresse når du registrerer bruker/profil for å få forslag til nærmeste ledige plass.</p>
    <?php else: ?>
      <div class="facility-card nearest-card">
        <h3>📍 Nærmeste ledige plasser</h3>
        <p id="nearestSpotInfo"
           data-address="<?= htmlspecialchars($user['adresse']) ?>"
           data-borettslag="<?= htmlspecialchars($user['borettslag_navn'] ?? '') ?>"
           data-anlegg='<?= htmlspecialchars(json_encode($anlegg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>'>
          Finner nærmeste ledige plass og nærmeste ledige el-plass basert på adressen din…
        </p>
      </div>
    <?php endif; ?>
  <!-- Global venteliste-boks -->
    <div class="facility-card waitlist-hero-card">
      <h3>⏳ Første ledige plass</h3>
      <p>Meld deg på én samlet kø for første ledige plass i borettslaget.</p>
      <form method="post" action="venteliste.php">
      <input type="hidden" name="anlegg_id" value="">
      <label>
          <input
            type="checkbox"
            name="onsker_lader"
            value="1"
            <?= ($er_på_venteliste || !$finnes_ladere_i_borettslag) ? 'disabled' : '' ?>
          >
          Ønsker lader
      </label>
      <?php if (!$finnes_ladere_i_borettslag): ?>
        <small>Det finnes ingen anlegg med ladere i borettslaget.</small>
      <?php endif; ?>
      <button type="submit" class="global-waitlist-button" <?= $er_på_venteliste ? 'disabled' : '' ?>>
          <?=
            $har_global_venteliste
              ? '✔️ Du står på venteliste for første ledige plass i borettslaget'
              : ($er_på_venteliste ? 'Du står på venteliste for et spesifikt anlegg' : '➕ Meld meg på venteliste for første ledige plass i borettslaget')
          ?>
      </button>
      </form>
    </div>

    <!-- Liste over anlegg -->
  <?php foreach ($anlegg as $a): ?>
    <div class="facility-card" id="anlegg-<?= $a['id'] ?>">
      <h3><?= htmlspecialchars($a['navn']) ?></h3>
      <p>🏗 Type: <?= ucfirst($a['type']) ?></p>
      <p>🚗 Totalt: <?= $a['total'] ?></p>
      <p>✅ Ledige: <?= $a['ledige'] ?></p>
      <p>🔴 Opptatt: <?= $a['opptatte'] ?></p>
      <p>⚡ Ledige med lader: <?= $a['ledige_med_lader'] ?></p>

      <!-- Venteliste-skjema -->
      <form method="post" action="venteliste.php">
        <input type="hidden" name="anlegg_id" value="<?= $a['id'] ?>">
        <label>
            <input
              type="checkbox"
              name="onsker_lader"
              value="1"
              <?= ($er_på_venteliste || !$a['har_ladere']) ? 'disabled' : '' ?>
            >
            Ønsker lader
        </label>
        <?php if (!$a['har_ladere']): ?>
          <small>Dette anlegget har ingen ladere.</small>
        <?php endif; ?>
        <button type="submit" <?= $er_på_venteliste ? 'disabled' : '' ?>>
            <?=
              ($er_på_venteliste && $venteliste_anlegg_id === (int)$a['id'])
                ? '✔️ Du står på venteliste for dette anlegget'
                : ($er_på_venteliste ? 'Du står på venteliste for et annet valg' : '➕ Meld meg på venteliste for dette anlegget')
            ?>
        </button>
        </form>
    </div>
  <?php endforeach; ?>
</aside>

</main>


  <script>
  var map = L.map('map').setView([59.8985, 10.8084], 17);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap'
  }).addTo(map);

  var anlegg = <?= json_encode($anlegg) ?>;

  anlegg.forEach(function(a) {
    if (a.lat && a.lng) {
      let marker = L.marker([a.lat, a.lng]).addTo(map)
        .bindPopup(`
          <strong>${a.navn}</strong><br>
          🏗 Type: ${a.type}<br>
          🚗 Totalt: ${a.total}<br>
          ✅ Ledige: ${a.ledige}<br>
          🔴 Opptatt: ${a.opptatte}<br>
          ⚡ Ledige med lader: ${a.ledige_med_lader}
        `);

      // Klikk på markør → scroll sidebar
      marker.on('click', function() {
        var el = document.getElementById("anlegg-" + a.id);
        if (el) {
          el.scrollIntoView({ behavior: 'smooth', block: 'start' });
          el.classList.add("highlight");
          setTimeout(() => el.classList.remove("highlight"), 4000);
        }
      });
    }
  });

  // Filtrering av anlegg
  document.getElementById('anleggSok').addEventListener('input', function () {
    var q = this.value.toLowerCase();
    document.querySelectorAll('.facility-card').forEach(function (card) {
      var navn = card.querySelector('h3').textContent.toLowerCase();
      card.style.display = navn.includes(q) ? '' : 'none';
    });
  });

  (function visNarmesteLedigPlass() {
    var infoElement = document.getElementById('nearestSpotInfo');
    if (!infoElement) return;

    var adresse = infoElement.dataset.address;
    var borettslagNavn = (infoElement.dataset.borettslag || '').trim();
    var anleggListe = [];
    try {
      anleggListe = JSON.parse(infoElement.dataset.anlegg || '[]');
    } catch (e) {
      infoElement.textContent = 'Kunne ikke lese anleggsdata.';
      return;
    }

    var kandidater = anleggListe.filter(function (a) {
      return Number(a.ledige) > 0 && a.lat && a.lng;
    });
    if (!kandidater.length) {
      infoElement.textContent = 'Ingen ledige plasser med kartposisjon er tilgjengelige akkurat nå.';
      return;
    }

    var geoKandidater = lagAdresseKandidater(adresse, borettslagNavn);

    finnKoordinater(geoKandidater)
      .then(function (posisjon) {
        if (!posisjon) {
          infoElement.textContent = 'Fant ikke koordinater for adressen din. Sjekk at adressen er komplett, eller oppdater den på Min side.';
          return;
        }

        var brukerLat = Number(posisjon.lat);
        var brukerLng = Number(posisjon.lon);
        var naermest = null;
        var naermestEl = null;

        kandidater.forEach(function (a) {
          var avstandKm = kalkulerAvstandKm(brukerLat, brukerLng, Number(a.lat), Number(a.lng));
          if (!naermest || avstandKm < naermest.avstandKm) {
            naermest = { navn: a.navn, avstandKm: avstandKm, ledige: Number(a.ledige) };
          }
          if (Number(a.ledige_med_lader) > 0 && (!naermestEl || avstandKm < naermestEl.avstandKm)) {
            naermestEl = { navn: a.navn, avstandKm: avstandKm, ledigeMedLader: Number(a.ledige_med_lader) };
          }
        });

        if (!naermest) {
          infoElement.textContent = 'Fant ingen ledig plass med beregnbar avstand.';
          return;
        }

        var html = '';
        html += '<strong>Nærmeste ledige plass:</strong> ' + naermest.navn + ' (' +
          naermest.avstandKm.toFixed(2) + ' km unna, ' + naermest.ledige + ' ledig).';
        html += '<br>';
        if (naermestEl) {
          html += '<strong>Nærmeste ledige el-plass:</strong> ' + naermestEl.navn + ' (' +
            naermestEl.avstandKm.toFixed(2) + ' km unna, ' + naermestEl.ledigeMedLader + ' ledig med lader).';
        } else {
          html += '<strong>Nærmeste ledige el-plass:</strong> Ingen ledige el-plasser akkurat nå.';
        }

        infoElement.innerHTML = html;
      })
      .catch(function () {
        infoElement.textContent = 'Kunne ikke beregne avstand akkurat nå. Prøv igjen senere.';
      });
  })();

  function lagAdresseKandidater(adresse, borettslagNavn) {
    var base = (adresse || '').trim();
    if (!base) return [];

    var kandidater = [base];
    if (borettslagNavn) kandidater.push(base + ', ' + borettslagNavn);
    kandidater.push(base + ', Oslo');
    kandidater.push(base + ', Oslo, Norge');
    kandidater.push(base + ', Norge');

    return Array.from(new Set(kandidater.map(function (k) { return k.trim(); }).filter(Boolean)));
  }

  function finnKoordinater(kandidater) {
    if (!kandidater.length) return Promise.resolve(null);

    var query = kandidater[0];
    var geoUrl = 'https://ws.geonorge.no/adresser/v1/sok?sok=' + encodeURIComponent(query) +
      '&fuzzy=true&treffPerSide=1&side=0';

    return fetch(geoUrl, { headers: { 'Accept': 'application/json' } })
      .then(function (response) {
        if (!response.ok) {
          throw new Error('Geonorge-feil');
        }
        return response.json();
      })
      .then(function (data) {
        var treff = Array.isArray(data && data.adresser) ? data.adresser : [];
        if (treff.length) {
          var punkt = treff[0].representasjonspunkt || {};
          if (typeof punkt.lat !== 'undefined' && typeof punkt.lon !== 'undefined') {
            return { lat: punkt.lat, lon: punkt.lon };
          }
        }
        return finnKoordinater(kandidater.slice(1));
      })
      .catch(function () {
        return finnKoordinater(kandidater.slice(1));
      });
  }

  function kalkulerAvstandKm(lat1, lng1, lat2, lng2) {
    var R = 6371;
    var dLat = (lat2 - lat1) * Math.PI / 180;
    var dLng = (lng2 - lng1) * Math.PI / 180;
    var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
      Math.sin(dLng / 2) * Math.sin(dLng / 2);
    var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
  }
</script>
</body>
</html>
