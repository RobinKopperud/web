<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/lib.php';

ensure_logged_in();
$user = fetch_current_user($conn);
$user_name = $user['navn'] ?? 'Bruker';

$flash = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_measurement') {
        $name = trim($_POST['measurement_name'] ?? '');

        if ($name === '') {
            $error = 'Du må gi målingen et navn.';
        } else {
            $stmt = $conn->prepare('SELECT id FROM treningslogg_measurements WHERE user_id = ? AND name = ?');
            if ($stmt) {
                $stmt->bind_param('is', $_SESSION['user_id'], $name);
                $stmt->execute();
                $exists = $stmt->get_result()->fetch_assoc();
                if ($exists) {
                    $error = 'Denne målingen finnes allerede.';
                } else {
                    $stmt = $conn->prepare('INSERT INTO treningslogg_measurements (user_id, name) VALUES (?, ?)');
                    if ($stmt) {
                        $stmt->bind_param('is', $_SESSION['user_id'], $name);
                        if ($stmt->execute()) {
                            header('Location: index.php?success=measurement');
                            exit;
                        }
                    }
                    $error = $error ?: 'Kunne ikke opprette måling. Prøv igjen.';
                }
            }
        }
    }

    if ($action === 'add_entry') {
        $measurement_id = (int) ($_POST['measurement_id'] ?? 0);
        $entry_date = trim($_POST['entry_date'] ?? '');
        $value = str_replace(',', '.', trim($_POST['value'] ?? ''));

        if ($measurement_id <= 0 || $entry_date === '' || $value === '') {
            $error = 'Fyll inn alle feltene for registrering.';
        } else {
            $measurement = fetch_measurement($conn, $measurement_id, (int) $_SESSION['user_id']);
            if (!$measurement) {
                $error = 'Ugyldig måling valgt.';
            } else {
                $stmt = $conn->prepare('INSERT INTO treningslogg_entries (measurement_id, entry_date, value) VALUES (?, ?, ?)');
                if ($stmt) {
                    $value_float = (float) $value;
                    $stmt->bind_param('isd', $measurement_id, $entry_date, $value_float);
                    if ($stmt->execute()) {
                        header('Location: index.php?success=entry');
                        exit;
                    }

                    if ($conn->errno === 1062) {
                        $error = 'Du har allerede registrert en måling for denne datoen.';
                    } else {
                        $error = 'Kunne ikke lagre målingen. Prøv igjen.';
                    }
                } else {
                    $error = 'Kunne ikke lagre målingen. Prøv igjen.';
                }
            }
        }
    }
}

if (isset($_GET['success'])) {
    if ($_GET['success'] === 'measurement') {
        $flash = 'Målingen er opprettet.';
    }
    if ($_GET['success'] === 'entry') {
        $flash = 'Målingen er lagret.';
    }
}

$measurements = fetch_measurements($conn, (int) $_SESSION['user_id']);

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
        <a class="ghost" href="logout.php">Logg ut</a>
      </div>
    </header>

    <?php if ($flash): ?>
      <div class="alert success"><?php echo htmlspecialchars($flash, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
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
        <button class="ghost" type="button" onclick="document.getElementById('new-measurement').scrollIntoView({behavior: 'smooth'})">Opprett ny måling</button>
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
            $chart = build_chart_path($entries);
            $delta_value = $delta ? $delta['delta'] : null;
            $delta_class = $delta_value === null ? 'neutral' : ($delta_value < 0 ? 'positive' : 'neutral');
          ?>
          <article class="measurement-card">
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
            <div class="delta <?php echo $delta_class; ?>">
              <?php if ($delta_value !== null): ?>
                <?php echo format_delta($delta_value); ?> cm siste 30 dager
              <?php else: ?>
                Ingen 30-dagers data
              <?php endif; ?>
            </div>
            <svg class="chart" viewBox="0 0 220 80" aria-hidden="true">
              <?php if ($chart['path']): ?>
                <path d="<?php echo $chart['path']; ?>"></path>
                <?php if ($chart['last']): ?>
                  <circle cx="<?php echo $chart['last']['x']; ?>" cy="<?php echo $chart['last']['y']; ?>" r="3"></circle>
                <?php endif; ?>
              <?php else: ?>
                <text x="16" y="40">Ingen data</text>
              <?php endif; ?>
            </svg>
            <a class="secondary" href="measurement.php?id=<?php echo (int) $measurement['id']; ?>">Se detalj</a>
          </article>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="registration" id="new-entry">
      <div>
        <h2>Ny registrering</h2>
        <p class="subtle">Én måling per dag per målingstype. Dato er obligatorisk.</p>
      </div>
      <form class="entry-form" method="post" action="index.php">
        <input type="hidden" name="action" value="add_entry" />
        <label>
          Måling
          <select name="measurement_id" required>
            <option value="">Velg måling</option>
            <?php foreach ($measurements as $measurement): ?>
              <option value="<?php echo (int) $measurement['id']; ?>">
                <?php echo htmlspecialchars($measurement['name'], ENT_QUOTES, 'UTF-8'); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>
          Dato
          <input type="date" name="entry_date" value="<?php echo date('Y-m-d'); ?>" required />
        </label>
        <label>
          Verdi (cm)
          <input type="number" name="value" step="0.1" min="0" placeholder="Eks. 82,4" required />
        </label>
        <button class="primary" type="submit">Lagre måling</button>
      </form>
    </section>

    <section class="registration" id="new-measurement">
      <div>
        <h2>Opprett ny måling</h2>
        <p class="subtle">Lag dine egne kategorier, som Mage, Biceps eller Lår.</p>
      </div>
      <form class="entry-form" method="post" action="index.php">
        <input type="hidden" name="action" value="create_measurement" />
        <label>
          Navn på måling
          <input type="text" name="measurement_name" placeholder="Eksempel: Mage" required />
        </label>
        <button class="primary" type="submit">Opprett måling</button>
      </form>
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
  </div>
</body>
</html>
