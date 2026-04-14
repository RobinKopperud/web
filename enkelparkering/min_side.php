<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$borettslag_id = $_SESSION['borettslag_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['oppdater_profil'])) {
    $nyEpost = trim($_POST['epost'] ?? '');
    $nyAdresse = trim($_POST['adresse'] ?? '');

    if (!filter_var($nyEpost, FILTER_VALIDATE_EMAIL)) {
        $message = '❌ Ugyldig e-postadresse.';
    } elseif ($nyAdresse === '') {
        $message = '❌ Adresse kan ikke være tom.';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE epost = ? AND id <> ? LIMIT 1");
        $stmt->bind_param("si", $nyEpost, $user_id);
        $stmt->execute();
        $epostFinnes = $stmt->get_result()->fetch_assoc();

        if ($epostFinnes) {
            $message = '❌ E-postadressen er allerede i bruk av en annen konto.';
        } else {
            $stmt = $conn->prepare("UPDATE users SET epost = ?, adresse = ? WHERE id = ? AND borettslag_id = ?");
            $stmt->bind_param("ssii", $nyEpost, $nyAdresse, $user_id, $borettslag_id);
            if ($stmt->execute()) {
                $message = '✅ Profilen din er oppdatert.';
            } else {
                $message = '❌ Klarte ikke å oppdatere profilen. Prøv igjen.';
            }
        }
    }
}

// Hent navn på innlogget bruker
$stmt = $conn->prepare("SELECT navn, rolle, epost, adresse FROM users WHERE id = ?");
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
  <title>Min side – Plogveien Borettslag</title>
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
      <h2>Min side</h2>

      <?php if ($message): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
      <?php endif; ?>

      <div class="facility-card">
        <h3>👤 Min profil</h3>
        <p>Her kan du oppdatere e-post og adresse. Adressen brukes for å finne nærmeste ledige plass.</p>
        <form method="post" class="profile-form">
          <label for="epost"><strong>E-post</strong></label>
          <input id="epost" type="email" name="epost" value="<?= htmlspecialchars($user['epost'] ?? '') ?>" required>

          <label for="adresse"><strong>Adresse</strong></label>
          <input id="adresse" type="text" name="adresse" value="<?= htmlspecialchars($user['adresse'] ?? '') ?>" required>

          <small>Tips: Bruk full adresse, f.eks. «Johan Hirschs vei 15, Oslo».</small>
          <button type="submit" name="oppdater_profil" value="1">💾 Lagre profil</button>
        </form>
      </div>

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
