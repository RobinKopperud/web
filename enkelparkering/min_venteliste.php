<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$borettslag_id = $_SESSION['borettslag_id'];

// Hent oppføring for brukeren
$stmt = $conn->prepare("
    SELECT v.id, v.anlegg_id, v.ønsker_lader, v.registrert, a.navn AS anlegg_navn
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
  <title>Min venteliste – EnkelParkering</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header class="header">
    <div>👋 Hei, du er innlogget</div>
    <div>
      <a href="index.php">Hjem</a> |
      <a href="logout.php">Logg ut</a>
    </div>
  </header>

  <main class="dashboard">
    <aside class="sidebar">
      <h2>Min venteliste</h2>

      <?php if (!$oppføring): ?>
        <p>Du står ikke på ventelisten i ditt borettslag.</p>
      <?php else: ?>
        <div class="facility-card">
          <h3>📋 Status</h3>
          <p><strong>Anlegg:</strong> <?= $oppføring['anlegg_navn'] ?? 'Første ledige' ?></p>
          <p><strong>Ønsker lader:</strong> <?= $oppføring['ønsker_lader'] ? '⚡ Ja' : 'Nei' ?></p>
          <p><strong>Registrert:</strong> <?= $oppføring['registrert'] ?></p>

          <?php
          // Beregn plass i køen
          if ($oppføring['anlegg_id']) {
              // Spesifikt anlegg
              $stmt = $conn->prepare("
                  SELECT COUNT(*) AS foran
                  FROM venteliste
                  WHERE borettslag_id = ? AND anlegg_id = ? 
                    AND registrert < ?
              ");
              $stmt->bind_param("iis", $borettslag_id, $oppføring['anlegg_id'], $oppføring['registrert']);
          } else {
              // Første ledige
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
          $posisjon = $pos['foran'] + 1; // brukeren selv
          
          // Tell totalt antall i samme kø
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
        </div>
      <?php endif; ?>
    </aside>
  </main>
</body>
</html>