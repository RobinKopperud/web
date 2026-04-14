<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$kontraktDir = __DIR__ . '/kontrakter';

function slettKontraktFiler(mysqli $conn, int $venteliste_id, string $kontraktDir): void
{
    $stmt = $conn->prepare("SELECT filnavn FROM kontrakter WHERE venteliste_id = ? AND filnavn IS NOT NULL");
    $stmt->bind_param("i", $venteliste_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($rad = $result->fetch_assoc()) {
        $fil = $kontraktDir . '/' . $rad['filnavn'];
        if ($rad['filnavn'] && is_file($fil)) {
            @unlink($fil);
        }
    }
}

$user_id = $_SESSION['user_id'];
$borettslag_id = $_SESSION['borettslag_id'];

// Fjern bruker fra ventelisten
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'remove'
) {
    $stmt = $conn->prepare("SELECT id FROM venteliste WHERE user_id = ? AND borettslag_id = ? LIMIT 1");
    $stmt->bind_param("ii", $user_id, $borettslag_id);
    $stmt->execute();
    $venteliste = $stmt->get_result()->fetch_assoc();

    if ($venteliste) {
        slettKontraktFiler($conn, (int)$venteliste['id'], $kontraktDir);
        $stmt = $conn->prepare("DELETE FROM venteliste WHERE id = ?");
        $stmt->bind_param("i", $venteliste['id']);
        $stmt->execute();
        $_SESSION['message'] = '✅ Du er fjernet fra ventelisten.';
    } else {
        $_SESSION['message'] = 'Ingen ventelisteoppføring å fjerne.';
    }

    header('Location: min_venteliste.php');
    exit;
}

// Hent navn på innlogget bruker
$stmt = $conn->prepare("SELECT navn, rolle FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$navn = $user['navn'] ?? 'Bruker';
$rolle = $user['rolle'] ?? '';

// Hent oppføring for brukeren
$stmt = $conn->prepare("
    SELECT v.id, v.anlegg_id, (v.onsker_lader + 0) AS onsker_lader, v.registrert, a.navn AS anlegg_navn
    FROM venteliste v
    LEFT JOIN anlegg a ON v.anlegg_id = a.id
    WHERE v.user_id = ? AND v.borettslag_id = ?
    LIMIT 1
");

$stmt->bind_param("ii", $user_id, $borettslag_id);
$stmt->execute();
$oppføring = $stmt->get_result()->fetch_assoc();

$kontrakt = null;
if ($oppføring) {
    $stmt = $conn->prepare("SELECT * FROM kontrakter WHERE venteliste_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("i", $oppføring['id']);
    $stmt->execute();
    $kontrakt = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Min venteliste – Plogveien Borettslag</title>
  <link rel="stylesheet" href="style.css">
  <script src="js.js" defer></script>
</head>
<body>
  <header class="header">
    <div class="logo">👋 Hei, <?= htmlspecialchars($navn) ?><?= $rolle === 'admin' ? ' (admin)' : '' ?></div>
    <button class="menu-toggle" id="menuToggle">☰</button>
    <nav class="nav">
      <a href="index.php">🏠 Hjem</a>
      <a href="min_side.php">👤 Min side</a>
      <a href="min_venteliste.php">📋 Min venteliste</a>
      <?php if ($rolle === 'admin'): ?>
        <a href="admin/admin.php">Adminpanel</a>
      <?php endif; ?>
      <a href="logout.php">Logg ut</a>
    </nav>
  </header>

  <main class="dashboard">
    <aside class="sidebar">
      <h2>Min venteliste</h2>

      <?php if (isset($_SESSION['message'])): ?>
        <p class="message"><?= htmlspecialchars($_SESSION['message']) ?></p>
        <?php unset($_SESSION['message']); ?>
      <?php endif; ?>

      <?php if (!$oppføring): ?>
        <p>Du står ikke på ventelisten i ditt borettslag.</p>
      <?php else: ?>
        <div class="facility-card">
          <h3>📋 Status</h3>
          <p><strong>Anlegg:</strong> <?= htmlspecialchars($oppføring['anlegg_navn'] ?? 'Første ledige') ?></p>
          <p><strong>Ønsker lader:</strong> <?= $oppføring['onsker_lader'] ? '⚡ Ja' : 'Nei' ?></p>
          <p><strong>Registrert:</strong> <?= htmlspecialchars($oppføring['registrert']) ?></p>

          <?php
          // Beregn posisjon i kø
          if ($oppføring['anlegg_id']) {
              $stmt = $conn->prepare("
                  SELECT COUNT(*) AS foran
                  FROM venteliste
                  WHERE borettslag_id = ? AND anlegg_id = ? 
                    AND registrert < ?
              ");
              $stmt->bind_param("iis", $borettslag_id, $oppføring['anlegg_id'], $oppføring['registrert']);
          } else {
              $stmt = $conn->prepare("
                  SELECT COUNT(*) AS foran
                  FROM venteliste
                  WHERE borettslag_id = ? AND anlegg_id IS NULL 
                    AND registrert < ?
              ");
              $stmt->bind_param("is", $borettslag_id, $oppføring['registrert']);
          }
          $stmt->execute();
          $pos = $stmt->get_result()->fetch_assoc();
          $posisjon = $pos['foran'] + 1;

          // Tell totalt i samme kø
          if ($oppføring['anlegg_id']) {
              $stmt = $conn->prepare("SELECT COUNT(*) AS totalt FROM venteliste WHERE borettslag_id = ? AND anlegg_id = ?");
              $stmt->bind_param("ii", $borettslag_id, $oppføring['anlegg_id']);
          } else {
              $stmt = $conn->prepare("SELECT COUNT(*) AS totalt FROM venteliste WHERE borettslag_id = ? AND anlegg_id IS NULL");
              $stmt->bind_param("i", $borettslag_id);
          }
          $stmt->execute();
          $totalt = $stmt->get_result()->fetch_assoc();
          ?>

          <p><strong>Din posisjon:</strong> <?= $posisjon ?> av <?= $totalt['totalt'] ?></p>

          <div class="contract-section">
            <h4>✉️ Tilbud og kontrakt</h4>
            <?php if ($kontrakt): ?>
              <?php
              $statusTekster = [
                'tilbud' => 'Tilbud sendt – vi venter på din godkjenning',
                'signert' => 'Signert – avventer endelig bekreftelse fra admin',
                'fullfort' => 'Ferdigstilt'
              ];
              $statusLabel = $statusTekster[$kontrakt['status']] ?? ucfirst($kontrakt['status']);
              ?>
              <p><strong>Status:</strong> <?= htmlspecialchars($statusLabel) ?></p>
              <?php if (!empty($kontrakt['tilbudt_dato'])): ?>
                <p><small>Tilbud sendt: <?= htmlspecialchars($kontrakt['tilbudt_dato']) ?></small></p>
              <?php endif; ?>
              <?php if (!empty($kontrakt['signert_dato'])): ?>
                <p><small>Signert: <?= htmlspecialchars($kontrakt['signert_dato']) ?></small></p>
              <?php endif; ?>

              <?php if ($kontrakt['status'] === 'tilbud'): ?>
                <p>Godkjenn tilbudet og last opp signert kontrakt for å bekrefte at du ønsker plassen.</p>
                <form method="post" action="bruker_kontrakt_last_opp.php" enctype="multipart/form-data">
                  <input type="hidden" name="kontrakt_id" value="<?= $kontrakt['id'] ?>">
                  <label class="file-label">Velg signert kontrakt (PDF/JPG/PNG)
                    <input type="file" name="signert_kontrakt" accept="application/pdf,image/*" required>
                  </label>
                  <label class="checkbox-label">
                    <input type="checkbox" name="bekreft" value="1" required>
                    Jeg godkjenner tilbudet og bekrefter at kontrakten er signert.
                  </label>
                  <button type="submit">📤 Last opp signert kontrakt</button>
                </form>
              <?php elseif ($kontrakt['status'] === 'signert'): ?>
                <p>Vi har mottatt signert kontrakt. Du får beskjed så snart admin har godkjent tildelingen.</p>
                <?php if (!empty($kontrakt['filnavn'])): ?>
                  <p><a href="kontrakter/<?= rawurlencode($kontrakt['filnavn']) ?>" target="_blank">📄 Åpne kontrakt</a></p>
                <?php endif; ?>
              <?php else: ?>
                <p>Kontrakten er ferdigstilt. Følg med på Min side for oppdatert status.</p>
                <?php if (!empty($kontrakt['filnavn'])): ?>
                  <p><a href="kontrakter/<?= rawurlencode($kontrakt['filnavn']) ?>" target="_blank">📄 Åpne kontrakt</a></p>
                <?php endif; ?>
              <?php endif; ?>
            <?php else: ?>
              <p>Ingen tilbud er sendt ennå. Du får beskjed så snart det er din tur.</p>
            <?php endif; ?>
          </div>

          <form method="post" onsubmit="return confirm('Er du sikker på at du vil melde deg av?');">
            <input type="hidden" name="action" value="remove">
            <button type="submit">🚫 Meld meg av ventelisten</button>
          </form>
        </div>
      <?php endif; ?>
    </aside>
  </main>
</body>
</html>
