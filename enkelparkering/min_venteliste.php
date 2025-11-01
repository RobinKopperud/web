<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$borettslag_id = $_SESSION['borettslag_id'];

// Fjern bruker fra ventelisten
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'remove'
) {
    $stmt = $conn->prepare("DELETE FROM venteliste WHERE user_id = ? AND borettslag_id = ?");
    $stmt->bind_param("ii", $user_id, $borettslag_id);
    $stmt->execute();
    $_SESSION['message'] = '✅ Du er fjernet fra ventelisten.';
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
    SELECT v.id, v.anlegg_id, v.onsker_lader, v.registrert, a.navn AS anlegg_navn
    FROM venteliste v
    LEFT JOIN anlegg a ON v.anlegg_id = a.id
    WHERE v.user_id = ? AND v.borettslag_id = ?
    LIMIT 1
");

$stmt->bind_param("ii", $user_id, $borettslag_id);
$stmt->execute();
$oppføring = $stmt->get_result()->fetch_assoc();
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
      <a href="min_side.php">🚗 Mine plasser</a>
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
