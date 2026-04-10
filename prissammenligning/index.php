<?php
// index.php
// En enkel opplastingsside for to tilbuds-PDF-er.

require_once __DIR__ . '/db.php';

// Hent butikker fra databasen. Brukeren velger to butikker før prosessering.
$stores = [];
$result = $mysqli->query('SELECT id, name FROM stores ORDER BY name ASC');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $stores[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tilbudssammenligning (MVP)</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="container">
        <h1>Sammenlign tilbud mellom to butikker</h1>
        <p class="lead">Last opp to PDF-er, én per butikk, og få en enkel sammenligningstabell.</p>

        <form id="upload-form" action="process.php" method="POST" enctype="multipart/form-data">
            <section class="card">
                <h2>Butikk 1</h2>
                <label for="store_1">Velg butikk</label>
                <select name="store_1" id="store_1" required>
                    <option value="">-- Velg --</option>
                    <?php foreach ($stores as $store): ?>
                        <option value="<?= (int) $store['id']; ?>"><?= htmlspecialchars($store['name']); ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="pdf_1">Last opp PDF</label>
                <input type="file" name="pdf_1" id="pdf_1" accept="application/pdf" required>
            </section>

            <section class="card">
                <h2>Butikk 2</h2>
                <label for="store_2">Velg butikk</label>
                <select name="store_2" id="store_2" required>
                    <option value="">-- Velg --</option>
                    <?php foreach ($stores as $store): ?>
                        <option value="<?= (int) $store['id']; ?>"><?= htmlspecialchars($store['name']); ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="pdf_2">Last opp PDF</label>
                <input type="file" name="pdf_2" id="pdf_2" accept="application/pdf" required>
            </section>

            <button type="submit" class="run-btn">Kjør</button>
            <p id="validation-message" class="validation-message" aria-live="polite"></p>
        </form>
    </main>

    <script src="script.js"></script>
</body>
</html>
