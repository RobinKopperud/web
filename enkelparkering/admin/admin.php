<?php
$title = "Dashboard";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

$user_id = $_SESSION['user_id'] ?? null;
$stmt = $conn->prepare("SELECT borettslag_id FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$borettslag_id = (int)($admin['borettslag_id'] ?? 0);

$stat = [
    'brukere' => 0,
    'anlegg' => 0,
    'plasser_totalt' => 0,
    'plasser_ledig' => 0,
    'plasser_opptatt' => 0,
    'venteliste_totalt' => 0,
    'venteliste_global' => 0,
    'venteliste_anlegg' => 0,
];

$stmt = $conn->prepare("SELECT COUNT(*) AS antall FROM users WHERE borettslag_id = ?");
$stmt->bind_param("i", $borettslag_id);
$stmt->execute();
$stat['brukere'] = (int)$stmt->get_result()->fetch_assoc()['antall'];

$stmt = $conn->prepare("SELECT COUNT(*) AS antall FROM anlegg WHERE borettslag_id = ?");
$stmt->bind_param("i", $borettslag_id);
$stmt->execute();
$stat['anlegg'] = (int)$stmt->get_result()->fetch_assoc()['antall'];

$stmt = $conn->prepare("
    SELECT
      COUNT(*) AS plasser_totalt,
      SUM(status = 'ledig') AS plasser_ledig,
      SUM(status = 'opptatt') AS plasser_opptatt
    FROM plasser p
    JOIN anlegg a ON p.anlegg_id = a.id
    WHERE a.borettslag_id = ?
");
$stmt->bind_param("i", $borettslag_id);
$stmt->execute();
$plassStat = $stmt->get_result()->fetch_assoc();
$stat['plasser_totalt'] = (int)($plassStat['plasser_totalt'] ?? 0);
$stat['plasser_ledig'] = (int)($plassStat['plasser_ledig'] ?? 0);
$stat['plasser_opptatt'] = (int)($plassStat['plasser_opptatt'] ?? 0);

$stmt = $conn->prepare("
    SELECT
      COUNT(*) AS venteliste_totalt,
      SUM(v.anlegg_id IS NULL OR v.anlegg_id = 0) AS venteliste_global,
      SUM(v.anlegg_id IS NOT NULL AND v.anlegg_id <> 0) AS venteliste_anlegg
    FROM venteliste v
    WHERE v.borettslag_id = ?
");
$stmt->bind_param("i", $borettslag_id);
$stmt->execute();
$ventelisteStat = $stmt->get_result()->fetch_assoc();
$stat['venteliste_totalt'] = (int)($ventelisteStat['venteliste_totalt'] ?? 0);
$stat['venteliste_global'] = (int)($ventelisteStat['venteliste_global'] ?? 0);
$stat['venteliste_anlegg'] = (int)($ventelisteStat['venteliste_anlegg'] ?? 0);

$andelLedig = $stat['plasser_totalt'] > 0 ? round(($stat['plasser_ledig'] / $stat['plasser_totalt']) * 100) : 0;
$andelOpptatt = $stat['plasser_totalt'] > 0 ? round(($stat['plasser_opptatt'] / $stat['plasser_totalt']) * 100) : 0;
$andelGlobalKo = $stat['venteliste_totalt'] > 0 ? round(($stat['venteliste_global'] / $stat['venteliste_totalt']) * 100) : 0;
$andelAnleggKo = $stat['venteliste_totalt'] > 0 ? round(($stat['venteliste_anlegg'] / $stat['venteliste_totalt']) * 100) : 0;

ob_start();
?>
<h1>Dashboard</h1>
<p>Oversikt over brukere, plasser, anlegg og kø i ditt borettslag.</p>

<div class="stats-grid">
  <article class="stat-card">
    <h3>👥 Brukere</h3>
    <p class="stat-number"><?= $stat['brukere'] ?></p>
  </article>
  <article class="stat-card">
    <h3>🅿️ Anlegg</h3>
    <p class="stat-number"><?= $stat['anlegg'] ?></p>
  </article>
  <article class="stat-card">
    <h3>🚗 Plasser totalt</h3>
    <p class="stat-number"><?= $stat['plasser_totalt'] ?></p>
  </article>
  <article class="stat-card">
    <h3>⏳ I kø</h3>
    <p class="stat-number"><?= $stat['venteliste_totalt'] ?></p>
  </article>
</div>

<div class="charts-grid">
  <article class="chart-card">
    <h3>Plass-status</h3>
    <p>Ledig: <?= $stat['plasser_ledig'] ?> · Opptatt: <?= $stat['plasser_opptatt'] ?></p>
    <div class="progress">
      <span class="segment ledig" style="width: <?= $andelLedig ?>%"></span>
      <span class="segment opptatt" style="width: <?= $andelOpptatt ?>%"></span>
    </div>
    <small><?= $andelLedig ?>% ledige / <?= $andelOpptatt ?>% opptatte</small>
  </article>

  <article class="chart-card">
    <h3>Kø-fordeling</h3>
    <p>Første ledige: <?= $stat['venteliste_global'] ?> · Spesifikt anlegg: <?= $stat['venteliste_anlegg'] ?></p>
    <div class="progress">
      <span class="segment global" style="width: <?= $andelGlobalKo ?>%"></span>
      <span class="segment anlegg" style="width: <?= $andelAnleggKo ?>%"></span>
    </div>
    <small><?= $andelGlobalKo ?>% første ledige / <?= $andelAnleggKo ?>% spesifikt anlegg</small>
  </article>
</div>
<?php
$content = ob_get_clean();
include "admin_layout.php";
