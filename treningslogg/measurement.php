<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/lib.php';

ensure_logged_in($conn);
$user = fetch_current_user($conn);
$user_name = $user['navn'] ?? 'Bruker';

$measurement_id = (int) ($_GET['id'] ?? 0);
$measurement = fetch_measurement($conn, $measurement_id, (int) $_SESSION['user_id']);

if (!$measurement) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete_entry') {
        $entry_id = (int) ($_POST['entry_id'] ?? 0);
        if ($entry_id <= 0) {
            $error = 'Ugyldig registrering valgt.';
        } else {
            $stmt = $conn->prepare(
                'DELETE e FROM treningslogg_entries e
                 JOIN treningslogg_measurements m ON e.measurement_id = m.id
                 WHERE e.id = ? AND m.id = ? AND m.user_id = ?'
            );
            if ($stmt) {
                $stmt->bind_param('iii', $entry_id, $measurement_id, $_SESSION['user_id']);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    header('Location: measurement.php?id=' . $measurement_id . '&success=deleted');
                    exit;
                }
                $error = 'Fant ikke registreringen du forsøkte å slette.';
            } else {
                $error = 'Kunne ikke slette registreringen. Prøv igjen.';
            }
        }
    }
}

if (($_GET['success'] ?? '') === 'deleted') {
    $success = 'Registreringen er slettet.';
}

$all_entries = fetch_entries($conn, $measurement_id, 300);
$last_entry = fetch_last_entry($conn, $measurement_id);
$delta = fetch_delta_30_days($conn, $measurement_id);
$range = $_GET['range'] ?? '90';
$valid_ranges = ['30' => 30, '90' => 90, 'all' => null];
if (!array_key_exists($range, $valid_ranges)) {
    $range = '90';
}

$entries = $all_entries;
if ($valid_ranges[$range] !== null) {
    $since_date = date('Y-m-d', strtotime('-' . $valid_ranges[$range] . ' days'));
    $entries = array_values(array_filter(
        $all_entries,
        static fn(array $entry): bool => $entry['entry_date'] >= $since_date
    ));
}

$stats = summarize_measurement_entries($entries);
$trend_points = calculate_moving_average_points($entries, 3);
$chart_width = 480;
$chart_height = 160;
$chart = build_chart_path($entries, $chart_width, $chart_height, 16);
$trend_chart = build_chart_path($trend_points, $chart_width, $chart_height, 16);
$average_line_y = null;
if ($chart['min'] !== null && $chart['max'] !== null && $stats['average'] !== null) {
    $range_span = max((float) $chart['max'] - (float) $chart['min'], 1.0);
    $average_line_y = $chart_height - 16 - (((float) $stats['average'] - (float) $chart['min']) / $range_span) * ($chart_height - 32);
}

$chart_grid = [
    (int) round($chart_height * 0.3),
    (int) round($chart_height * 0.5),
    (int) round($chart_height * 0.7),
];
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

    <?php if ($success): ?>
      <div class="alert success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

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
        <svg viewBox="0 0 <?php echo $chart_width; ?> <?php echo $chart_height; ?>" aria-hidden="true">
          <defs>
            <linearGradient id="chartFill" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stop-color="#1d4ed8" stop-opacity="0.28"></stop>
              <stop offset="100%" stop-color="#1d4ed8" stop-opacity="0.04"></stop>
            </linearGradient>
          </defs>
          <g class="chart-grid">
            <?php foreach ($chart_grid as $grid_y): ?>
              <line x1="16" y1="<?php echo $grid_y; ?>" x2="464" y2="<?php echo $grid_y; ?>"></line>
            <?php endforeach; ?>
          </g>
          <?php if ($chart['path']): ?>
            <?php
              $first_point = $chart['points'][0];
              $last_point = $chart['points'][count($chart['points']) - 1];
              $area_path = $chart['path']
                . sprintf('L%.1f %.1f ', $last_point['x'], $chart_height - 16)
                . sprintf('L%.1f %.1f Z', $first_point['x'], $chart_height - 16);
            ?>
            <path class="chart-area" d="<?php echo $area_path; ?>"></path>
            <path d="<?php echo $chart['path']; ?>"></path>
            <?php if ($trend_chart['path']): ?>
              <path class="chart-trend" d="<?php echo $trend_chart['path']; ?>"></path>
            <?php endif; ?>
            <?php if ($average_line_y !== null): ?>
              <line class="chart-average" x1="16" y1="<?php echo round($average_line_y, 1); ?>" x2="464" y2="<?php echo round($average_line_y, 1); ?>"></line>
              <text class="chart-label" x="368" y="<?php echo round($average_line_y - 6, 1); ?>">Snitt</text>
            <?php endif; ?>
            <g class="chart-points">
              <?php foreach ($chart['points'] as $point): ?>
                <circle cx="<?php echo $point['x']; ?>" cy="<?php echo $point['y']; ?>" r="2.5"></circle>
              <?php endforeach; ?>
            </g>
            <?php if ($chart['last']): ?>
              <circle class="chart-last" cx="<?php echo $chart['last']['x']; ?>" cy="<?php echo $chart['last']['y']; ?>" r="4"></circle>
            <?php endif; ?>
            <?php if ($chart['min'] !== null && $chart['max'] !== null): ?>
              <text class="chart-label" x="16" y="24">
                Maks <?php echo number_format((float) $chart['max'], 1, ',', ''); ?> cm
              </text>
              <text class="chart-label" x="16" y="<?php echo $chart_height - 8; ?>">
                Min <?php echo number_format((float) $chart['min'], 1, ',', ''); ?> cm
              </text>
            <?php endif; ?>
          <?php else: ?>
            <text x="24" y="80">Ingen data i valgt periode</text>
          <?php endif; ?>
        </svg>
      </div>
      <div class="entry-list">
        <h2>Historikk</h2>
        <p class="subtle">Rader slettes ved å swipe til venstre.</p>
        <div class="range-tabs" role="tablist" aria-label="Tidsperiode for graf">
          <a class="range-tab <?php echo $range === '30' ? 'active' : ''; ?>" href="measurement.php?id=<?php echo $measurement_id; ?>&range=30">30 dager</a>
          <a class="range-tab <?php echo $range === '90' ? 'active' : ''; ?>" href="measurement.php?id=<?php echo $measurement_id; ?>&range=90">90 dager</a>
          <a class="range-tab <?php echo $range === 'all' ? 'active' : ''; ?>" href="measurement.php?id=<?php echo $measurement_id; ?>&range=all">Alle</a>
        </div>
        <div class="detail-stats">
          <article class="detail-card">
            <p class="label">Endring i periode</p>
            <strong><?php echo $stats['change'] !== null ? format_delta((float) $stats['change']) . ' cm' : '–'; ?></strong>
            <span><?php echo $stats['change_percent'] !== null ? format_delta((float) $stats['change_percent']) . ' %' : 'Ingen sammenligning'; ?></span>
          </article>
          <article class="detail-card">
            <p class="label">Gjennomsnitt</p>
            <strong><?php echo $stats['average'] !== null ? number_format((float) $stats['average'], 1, ',', '') . ' cm' : '–'; ?></strong>
            <span>Min <?php echo $stats['min'] !== null ? number_format((float) $stats['min'], 1, ',', '') : '–'; ?> · Maks <?php echo $stats['max'] !== null ? number_format((float) $stats['max'], 1, ',', '') : '–'; ?></span>
          </article>
          <article class="detail-card">
            <p class="label">Registreringsfrekvens</p>
            <strong><?php echo $stats['avg_days_between'] !== null ? number_format((float) $stats['avg_days_between'], 1, ',', '') . ' dager' : '–'; ?></strong>
            <span><?php echo count($entries); ?> målinger på <?php echo (int) $stats['days_span']; ?> dager</span>
          </article>
        </div>
        <?php if (!$entries): ?>
          <p class="subtle">Ingen registreringer enda.</p>
        <?php else: ?>
          <ul>
            <?php foreach (array_reverse($entries) as $entry): ?>
              <li class="entry-row" data-entry-id="<?php echo (int) $entry['id']; ?>">
                <div class="entry-surface">
                  <span><?php echo htmlspecialchars($entry['entry_date'], ENT_QUOTES, 'UTF-8'); ?></span>
                  <strong><?php echo number_format((float) $entry['value'], 1, ',', ''); ?> cm</strong>
                </div>
                <form class="entry-delete" method="post" action="measurement.php?id=<?php echo $measurement_id; ?>&range=<?php echo urlencode($range); ?>">
                  <input type="hidden" name="action" value="delete_entry" />
                  <input type="hidden" name="entry_id" value="<?php echo (int) $entry['id']; ?>" />
                  <button type="submit" class="danger">Slett</button>
                </form>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </section>
  </div>
  <script>
    const rows = Array.from(document.querySelectorAll('.entry-row'));
    const maxTranslate = 84;
    const threshold = 32;

    const closeOthers = (currentRow) => {
      rows.forEach((row) => {
        if (row !== currentRow) {
          row.classList.remove('is-revealed');
          const surface = row.querySelector('.entry-surface');
          if (surface) {
            surface.style.transform = '';
          }
        }
      });
    };

    rows.forEach((row) => {
      const surface = row.querySelector('.entry-surface');
      if (!surface) {
        return;
      }

      let startX = 0;
      let currentX = 0;
      let dragging = false;

      const setTranslate = (value) => {
        surface.style.transform = `translateX(${value}px)`;
      };

      surface.addEventListener('pointerdown', (event) => {
        if (event.pointerType === 'mouse' && event.button !== 0) {
          return;
        }
        closeOthers(row);
        dragging = true;
        startX = event.clientX;
        currentX = 0;
        surface.setPointerCapture(event.pointerId);
        row.classList.add('dragging');
      });

      surface.addEventListener('pointermove', (event) => {
        if (!dragging) {
          return;
        }
        const deltaX = event.clientX - startX;
        const translate = Math.max(-maxTranslate, Math.min(0, deltaX));
        currentX = translate;
        setTranslate(translate);
      });

      const endDrag = (event) => {
        if (!dragging) {
          return;
        }
        dragging = false;
        row.classList.remove('dragging');
        if (currentX <= -threshold) {
          row.classList.add('is-revealed');
          setTranslate(-maxTranslate);
        } else {
          row.classList.remove('is-revealed');
          setTranslate(0);
        }
        surface.releasePointerCapture(event.pointerId);
      };

      surface.addEventListener('pointerup', endDrag);
      surface.addEventListener('pointercancel', endDrag);
    });

    document.addEventListener('click', (event) => {
      const targetRow = event.target.closest('.entry-row');
      if (!targetRow) {
        closeOthers(null);
      }
    });
  </script>
</body>
</html>
