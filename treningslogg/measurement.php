<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/lib.php';

ensure_logged_in();
$user = fetch_current_user($conn);
$user_name = $user['navn'] ?? 'Bruker';

$measurement_id = (int) ($_GET['id'] ?? 0);
$measurement = fetch_measurement($conn, $measurement_id, (int) $_SESSION['user_id']);

if (!$measurement) {
    header('Location: index.php');
    exit;
}

$entries = fetch_entries($conn, $measurement_id, 20);
$last_entry = fetch_last_entry($conn, $measurement_id);
$delta = fetch_delta_30_days($conn, $measurement_id);
$chart = build_chart_path($entries, 480, 160, 16);
$delta_value = $delta ? $delta['delta'] : null;
$delta_class = $delta_value === null ? 'neutral' : ($delta_value < 0 ? 'positive' : 'neutral');
?>
<!DOCTYPE html>
<html lang="no">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo htmlspecialchars($measurement['name'], ENT_QUOTES, 'UTF-8'); ?> · Treningslogg</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <div class="app">
    <header class="topbar">
      <div>
        <p class="eyebrow">Treningslogg</p>
        <h1><?php echo htmlspecialchars($measurement['name'], ENT_QUOTES, 'UTF-8'); ?></h1>
      </div>
      <div class="topbar-actions">
        <span class="user-pill">Hei, <?php echo htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8'); ?></span>
        <a class="ghost" href="index.php">Tilbake</a>
      </div>
    </header>

    <section class="hero">
      <div>
        <p class="lead">Oversikt over utviklingen din i centimeter over tid.</p>
      </div>
      <div class="hero-card">
        <p class="label">Siste måling</p>
        <?php if ($last_entry): ?>
          <div class="hero-value">
            <span><?php echo number_format((float) $last_entry['value'], 1, ',', ''); ?></span>
            <small>cm</small>
          </div>
          <p class="hero-meta"><?php echo htmlspecialchars($last_entry['entry_date'], ENT_QUOTES, 'UTF-8'); ?></p>
        <?php else: ?>
          <p class="hero-empty">Ingen registreringer enda.</p>
        <?php endif; ?>
        <div class="pill <?php echo $delta_class; ?>">
          <?php echo $delta_value !== null ? format_delta($delta_value) . ' cm siste 30 dager' : 'Ingen 30-dagers data'; ?>
        </div>
      </div>
    </section>

    <section class="measurement-detail">
      <div class="chart-large">
        <svg viewBox="0 0 480 160" aria-hidden="true">
          <?php if ($chart['path']): ?>
            <path d="<?php echo $chart['path']; ?>"></path>
            <?php if ($chart['last']): ?>
              <circle cx="<?php echo $chart['last']['x']; ?>" cy="<?php echo $chart['last']['y']; ?>" r="4"></circle>
            <?php endif; ?>
          <?php else: ?>
            <text x="24" y="80">Ingen data</text>
          <?php endif; ?>
        </svg>
      </div>
      <div class="entry-list">
        <h2>Historikk</h2>
        <?php if (!$entries): ?>
          <p class="subtle">Ingen registreringer enda.</p>
        <?php else: ?>
          <ul>
            <?php foreach (array_reverse($entries) as $entry): ?>
              <li>
                <span><?php echo htmlspecialchars($entry['entry_date'], ENT_QUOTES, 'UTF-8'); ?></span>
                <strong><?php echo number_format((float) $entry['value'], 1, ',', ''); ?> cm</strong>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </section>
  </div>
</body>
</html>
