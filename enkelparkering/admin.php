<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

// Sjekk innlogging
if (!isset($_SESSION['user_id']) || $_SESSION['rolle'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Hent anlegg og plasser for borettslaget
$borettslag_id = $_SESSION['borettslag_id'];

$sql = "SELECT a.id AS anlegg_id, a.navn AS anlegg_navn,
        p.id AS plass_id, p.nummer, p.status, p.har_lader,
        u.navn AS beboer, p.kontrakt_nr, p.fra_dato, p.til_dato
        FROM anlegg a
        LEFT JOIN plasser p ON a.id = p.anlegg_id
        LEFT JOIN users u ON p.beboer_id = u.id
        WHERE a.borettslag_id = ?
        ORDER BY a.id, p.nummer";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $borettslag_id);
$stmt->execute();
$result = $stmt->get_result();

$anlegg = [];
while ($row = $result->fetch_assoc()) {
    $anlegg[$row['anlegg_navn']][] = $row;
}

// Hent venteliste
$sql = "SELECT v.id, u.navn, u.epost, v.anlegg_id, v.ønsker_lader, v.registrert, a.navn AS anlegg_navn
        FROM venteliste v
        JOIN users u ON v.user_id = u.id
        LEFT JOIN anlegg a ON v.anlegg_id = a.id
        WHERE v.borettslag_id = ?
        ORDER BY v.registrert ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $borettslag_id);
$stmt->execute();
$venteliste = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="no">
<head>
  <meta charset="UTF-8">
  <title>Adminpanel – EnkelParkering</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body { font-family: Arial, sans-serif; background: #f5f7fa; padding: 1rem; }
    h1, h2 { color: #2c3e50; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 2rem; }
    th, td { padding: 0.5rem; border: 1px solid #ddd; text-align: left; }
    th { background: #2c3e50; color: white; }
    tr:nth-child(even) { background: #f9f9f9; }
    .btn { padding: 0.4rem 0.8rem; border: none; border-radius: 4px; cursor: pointer; }
    .btn-frigi { background: #e74c3c; color: white; }
    .btn-tildel { background: #27ae60; color: white; }
  </style>
</head>
<body>
  <h1>Adminpanel – <?= htmlspecialchars($_SESSION['rolle']) ?></h1>
  <p><a href="index.php">⬅ Tilbake til oversikt</a> | <a href="logout.php">Logg ut</a></p>

  <?php foreach ($anlegg as $anleggNavn => $plasser): ?>
    <h2><?= htmlspecialchars($anleggNavn) ?></h2>
    <table>
      <thead>
        <tr>
          <th>Plassnr</th>
          <th>Status</th>
          <th>Lader</th>
          <th>Beboer</th>
          <th>Kontrakt</th>
          <th>Fra</th>
          <th>Til</th>
          <th>Handling</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($plasser as $p): ?>
          <tr>
            <td><?= htmlspecialchars($p['nummer']) ?></td>
            <td><?= htmlspecialchars($p['status']) ?></td>
            <td><?= $p['har_lader'] ? "⚡" : "–" ?></td>
            <td><?= $p['beboer'] ? htmlspecialchars($p['beboer']) : "-" ?></td>
            <td><?= $p['kontrakt_nr'] ?: "-" ?></td>
            <td><?= $p['fra_dato'] ?: "-" ?></td>
            <td><?= $p['til_dato'] ?: "-" ?></td>
            <td>
              <?php if ($p['status'] === 'opptatt'): ?>
                <button class="btn btn-frigi">Frigi</button>
              <?php else: ?>
                <button class="btn btn-tildel">Tildel</button>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endforeach; ?>

  <h2>Venteliste</h2>
  <table>
    <thead>
      <tr>
        <th>Navn</th>
        <th>E-post</th>
        <th>Anlegg</th>
        <th>Ønsker lader</th>
        <th>Registrert</th>
        <th>Handling</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($venteliste as $v): ?>
        <tr>
          <td><?= htmlspecialchars($v['navn']) ?></td>
          <td><?= htmlspecialchars($v['epost']) ?></td>
          <td><?= $v['anlegg_navn'] ?: "Generell" ?></td>
          <td><?= $v['ønsker_lader'] ? "Ja" : "Nei" ?></td>
          <td><?= $v['registrert'] ?></td>
          <td><button class="btn btn-tildel">Tildel plass</button></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</body>
</html>
