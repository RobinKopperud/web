<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/lib.php';

ensure_logged_in($conn);
$user = fetch_current_user($conn);
$user_name = $user['navn'] ?? 'Bruker';

$flash = '';
$flash_type = 'success';
$debug_details = [];

if (isset($_GET['success'])) {
    if ($_GET['success'] === 'measurement') {
        $flash = 'Målingen er opprettet.';
    }
    if ($_GET['success'] === 'entry') {
        $flash = 'Målingen er lagret.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ai_debug'])) {
    $debug_result = test_openai_connection();
    $flash = $debug_result['message'];
    $flash_type = $debug_result['ok'] ? 'success' : 'error';
    $debug_details = $debug_result['details'] ?? [];
}

$measurements = fetch_measurements($conn, (int) $_SESSION['user_id']);
$total_entries = fetch_user_entry_count($conn, (int) $_SESSION['user_id']);
$current_streak = fetch_user_entry_streak($conn, (int) $_SESSION['user_id']);
$trend_analysis = get_recent_trend_analysis($conn, (int) $_SESSION['user_id']);
$milestones = [10, 30, 50];
$next_milestone = null;
foreach ($milestones as $milestone) {
    if ($total_entries < $milestone) {
        $next_milestone = $milestone;
        break;
    }
}
$progress_target = $next_milestone ?? ($milestones ? end($milestones) : 0);
$progress_value = $progress_target > 0 ? min(100, ($total_entries / $progress_target) * 100) : 0;
$remaining_entries = $next_milestone ? max(0, $next_milestone - $total_entries) : 0;
$badges = [
    [
        'title' => 'Første registrering',
        'earned' => $total_entries >= 1,
        'detail' => 'Du har startet reisen.',
    ],
    [
        'title' => '10 registreringer',
        'earned' => $total_entries >= 10,
        'detail' => 'Stabil oppfølging av målene dine.',
    ],
    [
        'title' => '30 registreringer',
        'earned' => $total_entries >= 30,
        'detail' => 'En hel måned med innsikt.',
    ],
    [
        'title' => '7-dagers streak',
        'earned' => $current_streak >= 7,
        'detail' => 'Kontinuitet en hel uke.',
    ],
    [
        'title' => '30-dagers streak',
        'earned' => $current_streak >= 30,
        'detail' => 'Langsiktig driv og momentum.',
    ],
];

$latest_overall = null;
foreach ($measurements as $measurement) {
    $last_entry = fetch_last_entry($conn, (int) $measurement['id']);
    if ($last_entry && (!$latest_overall || $last_entry['entry_date'] > $latest_overall['entry_date'])) {
        $latest_overall = array_merge($last_entry, [
            'measurement_name' => $measurement['name'],
        ]);
    }
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Treningslogg – målebasert utvikling</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <div class="app">
    <header class="topbar">
      <div>
        <p class="eyebrow">Treningslogg</p>
        <h1>Målebasert utvikling uten støy.</h1>
      </div>
      <div class="topbar-actions">
        <span class="user-pill">Hei, <?php echo htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8'); ?></span>
        <form method="post">
          <input type="hidden" name="ai_debug" value="1" />
          <button class="ghost" type="submit">AI-debug</button>
        </form>
        <a class="ghost" href="logout.php">Logg ut</a>
      </div>
    </header>

    <?php if ($flash): ?>
      <div class="alert <?php echo htmlspecialchars($flash_type, ENT_QUOTES, 'UTF-8'); ?>">
        <?php echo htmlspecialchars($flash, ENT_QUOTES, 'UTF-8'); ?>
        <?php if ($debug_details): ?>
          <ul class="alert-details">
            <?php foreach ($debug_details as $detail): ?>
              <li><?php echo htmlspecialchars((string) $detail, ENT_QUOTES, 'UTF-8'); ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <section class="hero">
      <div>
        <p class="lead">
          Hold orden på kroppsmålene dine i centimeter. Se siste måling, endring siste 30 dager
          og utvikling over tid – helt uten kompliserte økter eller kosthold.
        </p>
      </div>
      <div class="hero-card">
        <p class="label">Siste måling</p>
        <?php if ($latest_overall): ?>
          <div class="hero-value">
            <span><?php echo number_format((float) $latest_overall['value'], 1, ',', ''); ?></span>
            <small>cm</small>
          </div>
          <p class="hero-meta"><?php echo htmlspecialchars($latest_overall['measurement_name'], ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars($latest_overall['entry_date'], ENT_QUOTES, 'UTF-8'); ?></p>
        <?php else: ?>
          <p class="hero-empty">Ingen målinger registrert ennå.</p>
        <?php endif; ?>
        <div class="pill neutral">Maks én registrering per dag per måling</div>
      </div>
    </section>

    <section class="measurements">
      <div class="section-title">
        <h2>Dine målinger</h2>
        <a class="ghost" href="registrering.php">Ny registrering</a>
      </div>
      <div class="measurement-grid">
        <?php if (!$measurements): ?>
          <div class="empty-card">
            <p>Du har ingen målinger ennå. Opprett en ny måling for å komme i gang.</p>
          </div>
        <?php endif; ?>
        <?php foreach ($measurements as $measurement): ?>
          <?php
            $last_entry = fetch_last_entry($conn, (int) $measurement['id']);
            $delta = fetch_delta_30_days($conn, (int) $measurement['id']);
            $entries = fetch_entries($conn, (int) $measurement['id'], 10);
            $chart_width = 220;
            $chart_height = 80;
            $chart = build_chart_path($entries, $chart_width, $chart_height, 12);
            $chart_grid = [
              (int) round($chart_height * 0.3),
              (int) round($chart_height * 0.5),
              (int) round($chart_height * 0.7),
            ];
            $delta_value = $delta ? $delta['delta'] : null;
            $delta_class = $delta_value === null ? 'neutral' : ($delta_value < 0 ? 'positive' : 'neutral');
          ?>
          <details class="measurement-card">
            <summary class="card-summary">
              <div class="card-summary-main">
                <div class="card-header">
                  <h3><?php echo htmlspecialchars($measurement['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                  <span class="unit">cm</span>
                </div>
                <p class="card-value">
                  <?php echo $last_entry ? number_format((float) $last_entry['value'], 1, ',', '') : '–'; ?>
                </p>
                <p class="card-meta">
                  <?php echo $last_entry ? 'Sist registrert ' . htmlspecialchars($last_entry['entry_date'], ENT_QUOTES, 'UTF-8') : 'Ingen registreringer enda.'; ?>
                </p>
              </div>
              <span class="summary-toggle">Detaljer</span>
            </summary>
            <div class="card-details">
              <div class="delta <?php echo $delta_class; ?>">
                <?php if ($delta_value !== null): ?>
                  <?php echo format_delta($delta_value); ?> cm siste 30 dager
                <?php else: ?>
                  Ingen 30-dagers data
                <?php endif; ?>
              </div>
              <svg class="chart" viewBox="0 0 <?php echo $chart_width; ?> <?php echo $chart_height; ?>" aria-hidden="true">
                <g class="chart-grid">
                  <?php foreach ($chart_grid as $grid_y): ?>
                    <line x1="12" y1="<?php echo $grid_y; ?>" x2="208" y2="<?php echo $grid_y; ?>"></line>
                  <?php endforeach; ?>
                </g>
                <?php if ($chart['path']): ?>
                  <path d="<?php echo $chart['path']; ?>"></path>
                  <g class="chart-points">
                    <?php foreach ($chart['points'] as $point): ?>
                      <circle cx="<?php echo $point['x']; ?>" cy="<?php echo $point['y']; ?>" r="2"></circle>
                    <?php endforeach; ?>
                  </g>
                  <?php if ($chart['last']): ?>
                    <circle class="chart-last" cx="<?php echo $chart['last']['x']; ?>" cy="<?php echo $chart['last']['y']; ?>" r="3.5"></circle>
                  <?php endif; ?>
                <?php else: ?>
                  <text x="16" y="40">Ingen data</text>
                <?php endif; ?>
              </svg>
              <a class="secondary" href="measurement.php?id=<?php echo (int) $measurement['id']; ?>">Se detalj</a>
            </div>
          </details>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="trend">
      <div class="section-title">
        <div>
          <h2>Automatisk trendanalyse</h2>
          <p class="subtle">En samlet AI-oppsummering av registreringene dine de siste 10 dagene.</p>
        </div>
      </div>
      <div class="trend-grid">
        <?php if (!$measurements): ?>
          <div class="trend-card">
            <p class="label">Ingen data</p>
            <p class="trend-summary">Legg inn første måling for å få trendanalyse.</p>
          </div>
        <?php else: ?>
          <article class="trend-card">
            <p class="label">Samlet trend</p>
            <p class="trend-summary"><?php echo htmlspecialchars($trend_analysis['summary'], ENT_QUOTES, 'UTF-8'); ?></p>
          </article>
        <?php endif; ?>
      </div>
    </section>

    <section class="insight">
      <div>
        <h2>Innsikt siste 30 dager</h2>
        <p class="subtle">Reduksjon vises i grønt. Ingen vurdering – bare tall.</p>
      </div>
      <div class="insight-grid">
        <?php if (!$measurements): ?>
          <div class="insight-card">
            <p class="label">Ingen data</p>
            <p class="insight-meta">Legg inn første måling for å se innsikt.</p>
          </div>
        <?php endif; ?>
        <?php foreach ($measurements as $measurement): ?>
          <?php
            $delta = fetch_delta_30_days($conn, (int) $measurement['id']);
            $delta_value = $delta ? $delta['delta'] : null;
            $delta_class = $delta_value === null ? 'neutral' : ($delta_value < 0 ? 'positive' : 'neutral');
          ?>
          <div class="insight-card">
            <p class="label"><?php echo htmlspecialchars($measurement['name'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="insight-value <?php echo $delta_class; ?>">
              <?php echo $delta_value !== null ? format_delta($delta_value) . ' cm' : '–'; ?>
            </p>
            <p class="insight-meta">Siste 30 dager</p>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="motivation">
      <div class="section-title">
        <div>
          <h2>Milestoner og badges</h2>
          <p class="subtle">Bygg momentum med små mål og synlige belønninger.</p>
        </div>
      </div>
      <div class="motivation-grid">
        <div class="progress-card">
          <p class="label">Registreringer</p>
          <p class="progress-value"><?php echo $total_entries; ?> av <?php echo $progress_target; ?> registreringer</p>
          <div class="progress-bar" role="progressbar" aria-valuenow="<?php echo (int) $progress_value; ?>" aria-valuemin="0" aria-valuemax="100">
            <span style="width: <?php echo $progress_value; ?>%"></span>
          </div>
          <p class="subtle">
            <?php if ($next_milestone): ?>
              <?php echo $remaining_entries; ?> registreringer igjen til neste milestone.
            <?php else: ?>
              Du har nådd alle milepælene 🎉
            <?php endif; ?>
          </p>
        </div>
        <div class="progress-card">
          <p class="label">Streak</p>
          <p class="progress-value"><?php echo $current_streak; ?> dager på rad</p>
          <div class="pill <?php echo $current_streak >= 7 ? 'positive' : 'neutral'; ?>">
            <?php echo $current_streak >= 7 ? 'Streaken din holder seg!' : 'Neste badge ved 7 dager.'; ?>
          </div>
          <p class="subtle">Måles på dager med minst én registrering.</p>
        </div>
        <div class="badge-wall">
          <?php foreach ($badges as $badge): ?>
            <div class="badge-card <?php echo $badge['earned'] ? 'earned' : ''; ?>">
              <p class="badge-title"><?php echo htmlspecialchars($badge['title'], ENT_QUOTES, 'UTF-8'); ?></p>
              <p class="badge-detail"><?php echo htmlspecialchars($badge['detail'], ENT_QUOTES, 'UTF-8'); ?></p>
              <span class="badge-status"><?php echo $badge['earned'] ? 'Opplåst' : 'Låst'; ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  </div>
</body>
</html>
