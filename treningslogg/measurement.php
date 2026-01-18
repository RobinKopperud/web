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

$entries = fetch_entries($conn, $measurement_id, 20);
$last_entry = fetch_last_entry($conn, $measurement_id);
$delta = fetch_delta_30_days($conn, $measurement_id);
$chart_width = 480;
$chart_height = 160;
$chart = build_chart_path($entries, $chart_width, $chart_height, 16);
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
          <g class="chart-grid">
            <?php foreach ($chart_grid as $grid_y): ?>
              <line x1="16" y1="<?php echo $grid_y; ?>" x2="464" y2="<?php echo $grid_y; ?>"></line>
            <?php endforeach; ?>
          </g>
          <?php if ($chart['path']): ?>
            <path d="<?php echo $chart['path']; ?>"></path>
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
            <text x="24" y="80">Ingen data</text>
          <?php endif; ?>
        </svg>
      </div>
      <div class="entry-list">
        <h2>Historikk</h2>
        <p class="subtle">Rader slettes ved å swipe til venstre.</p>
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
                <form class="entry-delete" method="post" action="measurement.php?id=<?php echo $measurement_id; ?>">
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
