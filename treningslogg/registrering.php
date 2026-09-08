<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/lib.php';

ensure_logged_in($conn);
$user = fetch_current_user($conn);
$user_name = $user['navn'] ?? 'Bruker';

$error = '';

function validate_training_photo(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['present' => false];
    }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['present' => true, 'error' => 'Bildet kunne ikke lastes opp. Prøv igjen.'];
    }
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        return ['present' => true, 'error' => 'Bildet kan være maks 5 MB.'];
    }
    if (!is_uploaded_file($file['tmp_name'] ?? '')) {
        return ['present' => true, 'error' => 'Ugyldig bildefil.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime])) {
        return ['present' => true, 'error' => 'Bruk JPG, PNG eller WebP.'];
    }
    if (@getimagesize($file['tmp_name']) === false) {
        return ['present' => true, 'error' => 'Filen ser ikke ut til å være et gyldig bilde.'];
    }

    return [
        'present' => true,
        'tmp_name' => $file['tmp_name'],
        'mime' => $mime,
        'extension' => $extensions[$mime],
        'size' => (int) $file['size'],
    ];
}

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

        $photo = validate_training_photo($_FILES['entry_photo'] ?? []);
        $parsed_date = DateTime::createFromFormat('Y-m-d', $entry_date);
        $date_is_valid = $parsed_date !== false && $parsed_date->format('Y-m-d') === $entry_date && $entry_date <= date('Y-m-d');

        if (!empty($photo['error'])) {
            $error = $photo['error'];
        } elseif ($measurement_id <= 0 || !$date_is_valid || $value === '' || !is_numeric($value) || (float) $value < 0) {
            $error = 'Fyll inn alle feltene for registrering.';
        } elseif (!empty($photo['present']) && !training_images_table_available($conn)) {
            $error = 'Bildelagring er ikke aktivert i databasen ennå. Kjør den nye migreringen først.';
        } else {
            $measurement = fetch_measurement($conn, $measurement_id, (int) $_SESSION['user_id']);
            if (!$measurement) {
                $error = 'Ugyldig måling valgt.';
            } else {
                if (!empty($photo['present'])) {
                    $photoCheck = $conn->prepare('SELECT id FROM treningslogg_entry_images WHERE user_id = ? AND image_date = ? LIMIT 1');
                    if (!$photoCheck) {
                        $error = 'Kunne ikke kontrollere dagens bilde.';
                    } else {
                        $photoCheck->bind_param('is', $_SESSION['user_id'], $entry_date);
                        $photoCheck->execute();
                    }
                    if ($photoCheck && $photoCheck->get_result()->fetch_assoc()) {
                        $error = 'Du har allerede lastet opp ett bilde for denne datoen.';
                    }
                }

                $stmt = !$error ? $conn->prepare('INSERT INTO treningslogg_entries (measurement_id, entry_date, value) VALUES (?, ?, ?)') : null;
                if ($stmt) {
                    $value_float = (float) $value;
                    $stmt->bind_param('isd', $measurement_id, $entry_date, $value_float);
                    if ($stmt->execute()) {
                        $entry_id = (int) $stmt->insert_id;
                        if (!empty($photo['present'])) {
                            $upload_dir = __DIR__ . '/uploads';
                            if (!is_dir($upload_dir) && !mkdir($upload_dir, 0750, true)) {
                                $conn->query('DELETE FROM treningslogg_entries WHERE id = ' . $entry_id);
                                $error = 'Kunne ikke opprette mappe for bilder.';
                            } else {
                                $file_name = bin2hex(random_bytes(20)) . '.' . $photo['extension'];
                                $target = $upload_dir . '/' . $file_name;
                                if (!move_uploaded_file($photo['tmp_name'], $target)) {
                                    $conn->query('DELETE FROM treningslogg_entries WHERE id = ' . $entry_id);
                                    $error = 'Bildet kunne ikke lagres.';
                                } else {
                                    $imageStmt = $conn->prepare('INSERT INTO treningslogg_entry_images (user_id, entry_id, image_date, file_name, mime_type, file_size) VALUES (?, ?, ?, ?, ?, ?)');
                                    if ($imageStmt) {
                                        $imageStmt->bind_param('iisssi', $_SESSION['user_id'], $entry_id, $entry_date, $file_name, $photo['mime'], $photo['size']);
                                    }
                                    if (!$imageStmt || !$imageStmt->execute()) {
                                        $image_errno = $conn->errno;
                                        @unlink($target);
                                        $conn->query('DELETE FROM treningslogg_entries WHERE id = ' . $entry_id);
                                        $error = $image_errno === 1062 ? 'Du har allerede lastet opp ett bilde for denne datoen.' : 'Bildet kunne ikke kobles til registreringen.';
                                    }
                                }
                            }
                        }
                        if ($error) {
                            // Vis feilen i skjemaet i stedet for å sende brukeren videre.
                        } else {
                            header('Location: index.php?success=entry');
                            exit;
                        }
                    } else {
                        if ($conn->errno === 1062) {
                            $error = 'Du har allerede registrert en måling for denne datoen.';
                        } else {
                            $error = 'Kunne ikke lagre målingen. Prøv igjen.';
                        }
                    }
                } elseif (!$error) {
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
      <form class="entry-form" method="post" action="registrering.php" enctype="multipart/form-data">
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
        <label class="photo-field">
          Dagens bilde <span class="optional">Valgfritt · maks ett per dag</span>
          <span class="photo-picker">
            <input type="file" name="entry_photo" id="entryPhoto" accept="image/jpeg,image/png,image/webp" />
            <img id="entryPhotoPreview" alt="Forhåndsvisning av valgt bilde" hidden />
            <span><strong>Velg bilde</strong><small>JPG, PNG eller WebP · maks 5 MB</small></span>
          </span>
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
  <script>
    const photoInput = document.getElementById('entryPhoto');
    const photoPreview = document.getElementById('entryPhotoPreview');
    photoInput?.addEventListener('change', () => {
      const file = photoInput.files?.[0];
      if (!file) { photoPreview.hidden = true; return; }
      photoPreview.src = URL.createObjectURL(file);
      photoPreview.hidden = false;
    });
  </script>
</body>
</html>
