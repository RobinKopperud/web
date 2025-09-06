<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Hent brukerinfo
$stmt = $conn->prepare("SELECT navn, rolle FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Hent plasser tilknyttet brukeren
$stmt = $conn->prepare("SELECT p.nummer, p.har_lader, a.navn AS anlegg_navn FROM plasser p JOIN anlegg a ON p.anlegg_id = a.id WHERE p.beboer_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$plasser = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="no">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Min side – EnkelParkering</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="header">
  <div>👋 Hei, <?= htmlspecialchars($user['navn']) ?> (<?= $user['rolle'] ?>)</div>
  <div>
    <a href="index.php">Hjem</a> |
    <a href="min_venteliste.php">Min venteliste</a> |
    <a href="logout.php">Logg ut</a>
  </div>
</header>

<main class="dashboard">
  <aside class="sidebar">
    <h2>Mine parkeringsplasser</h2>
    <?php if (empty($plasser)): ?>
      <p>Du har ingen tildelte parkeringsplasser.</p>
    <?php else: ?>
      <?php foreach ($plasser as $p): ?>
        <div class="facility-card">
          <h3><?= htmlspecialchars($p['anlegg_navn']) ?> – Plass <?= htmlspecialchars($p['nummer']) ?></h3>
          <p>⚡ Lader: <?= $p['har_lader'] ? 'Ja' : 'Nei' ?></p>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </aside>
</main>
</body>
</html>
