<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$borettslag_id = $_SESSION['borettslag_id'];

// Hent navn på innlogget bruker
$stmt = $conn->prepare("SELECT navn, rolle FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$navn = $user['navn'] ?? 'Bruker';
$rolle = $user['rolle'] ?? '';

// Hent parkeringsplasser tildelt brukeren
$stmt = $conn->prepare("
    SELECT p.id, p.nummer, p.status, p.har_lader, a.navn AS anlegg_navn
    FROM plasser p
    JOIN anlegg a ON p.anlegg_id = a.id
    WHERE p.beboer_id = ? AND a.borettslag_id = ?
");
$stmt->bind_param("ii", $user_id, $borettslag_id);
$stmt->execute();
$plasser = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

foreach ($plasser as &$plass) {
    $stmt = $conn->prepare("SELECT status, filnavn, tilbudt_dato, signert_dato FROM kontrakter WHERE plass_id = ? AND user_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("ii", $plass['id'], $user_id);
    $stmt->execute();
    $plass['kontrakt'] = $stmt->get_result()->fetch_assoc();
}
unset($plass);
?>
<!DOCTYPE html>
<html lang="no">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mine plasser – Plogveien Borettslag</title>
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
      <h2>Mine parkeringsplasser</h2>

      <?php if (empty($plasser)): ?>
        <p>Du har ingen tildelte plasser.</p>
      <?php else: ?>
        <?php foreach ($plasser as $p): ?>
          <div class="facility-card">
            <h3><?= htmlspecialchars($p['anlegg_navn']) ?> – Plass <?= htmlspecialchars($p['nummer']) ?></h3>
            <p><strong>Status:</strong> <?= htmlspecialchars($p['status']) ?></p>
            <p><strong>Lader:</strong> <?= $p['har_lader'] ? '⚡ Ja' : 'Nei' ?></p>
            <?php if (!empty($p['kontrakt'])): ?>
              <p><strong>Kontrakt:</strong> <?= htmlspecialchars($p['kontrakt']['status']) ?></p>
              <?php if (!empty($p['kontrakt']['signert_dato'])): ?>
                <p><strong>Signert:</strong> <?= htmlspecialchars($p['kontrakt']['signert_dato']) ?></p>
              <?php endif; ?>
              <?php if (!empty($p['kontrakt']['filnavn'])): ?>
                <p><a href="kontrakter/<?= rawurlencode($p['kontrakt']['filnavn']) ?>" target="_blank">📄 Åpne kontrakt</a></p>
              <?php endif; ?>
            <?php else: ?>
              <p><strong>Kontrakt:</strong> Ikke registrert</p>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </aside>
  </main>
</body>
</html>
