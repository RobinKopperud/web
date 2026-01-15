<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/lib.php';

ensure_logged_in();
$user = fetch_current_user($conn);
$user_name = $user['navn'] ?? 'Bruker';

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

$measurements = fetch_measurements($conn, (int) $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="no">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Ny registrering – Treningslogg</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <div class="app">
    <header class="topbar">
      <div>
        <p class="eyebrow">Treningslogg</p>
        <h1>Registrer nye målinger.</h1>
      </div>
      <div class="topbar-actions">
        <span class="user-pill">Hei, <?php echo htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8'); ?></span>
        <a class="ghost" href="index.php">Til oversikten</a>
      </div>
    </header>

    <?php if ($error): ?>
      <div class="alert error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <section class="registration registration-page">
      <div>
        <h2>Ny registrering</h2>
        <p class="subtle">Én måling per dag per målingstype. Dato er obligatorisk.</p>
      </div>
      <form class="entry-form" method="post" action="registrering.php">
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

    <section class="registration registration-page compact">
      <div>
        <h2>Opprett ny måling</h2>
        <p class="subtle">Lag egne kategorier, som Mage, Biceps eller Lår.</p>
      </div>
      <form class="entry-form compact-form" method="post" action="registrering.php">
        <input type="hidden" name="action" value="create_measurement" />
        <label>
          Navn på måling
          <input type="text" name="measurement_name" placeholder="Eksempel: Mage" required />
        </label>
        <button class="primary" type="submit">Opprett måling</button>
      </form>
    </section>
  </div>
</body>
</html>
