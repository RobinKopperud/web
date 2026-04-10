<?php
// result.php
// Viser sammenligningstabell for én import.

require_once __DIR__ . '/db.php';

$importId = isset($_GET['import_id']) ? (int) $_GET['import_id'] : 0;
if ($importId <= 0) {
    die('Mangler gyldig import_id.');
}

// Finn de to butikkene som var med i denne importen.
$sqlStores = 'SELECT DISTINCT s.id, s.name
              FROM import_files f
              INNER JOIN stores s ON s.id = f.store_id
              WHERE f.import_id = ?
              ORDER BY s.name ASC';
$stmtStores = $mysqli->prepare($sqlStores);
$stmtStores->bind_param('i', $importId);
$stmtStores->execute();
$resStores = $stmtStores->get_result();
$stores = $resStores->fetch_all(MYSQLI_ASSOC);
$stmtStores->close();

if (count($stores) < 2) {
    die('Fant ikke to butikker i denne importen.');
}

$store1 = $stores[0];
$store2 = $stores[1];

// Hent alle tilbud i importen.
$sqlOffers = 'SELECT o.id, o.store_id, o.normalized_name, o.price, o.match_group, o.confidence
              FROM offers o
              INNER JOIN import_files f ON f.id = o.import_file_id
              WHERE f.import_id = ?
              ORDER BY o.id ASC';
$stmtOffers = $mysqli->prepare($sqlOffers);
$stmtOffers->bind_param('i', $importId);
$stmtOffers->execute();
$resOffers = $stmtOffers->get_result();
$offers = $resOffers->fetch_all(MYSQLI_ASSOC);
$stmtOffers->close();

// Bygg rader for visning:
// - match_group brukes når den finnes
// - ellers bygg unik nøkkel med offer-id (for varer uten match)
$rows = [];
foreach ($offers as $offer) {
    $key = $offer['match_group'] ?: ('single_' . $offer['id']);

    if (!isset($rows[$key])) {
        $rows[$key] = [
            'normalized_name' => $offer['normalized_name'],
            'price_store_1' => null,
            'price_store_2' => null,
            'confidence' => (float) $offer['confidence'],
        ];
    }

    if ((int) $offer['store_id'] === (int) $store1['id']) {
        $rows[$key]['price_store_1'] = (float) $offer['price'];
    } elseif ((int) $offer['store_id'] === (int) $store2['id']) {
        $rows[$key]['price_store_2'] = (float) $offer['price'];
    }

    // Vis høyeste confidence hvis flere rader påvirker samme match_group.
    $rows[$key]['confidence'] = max($rows[$key]['confidence'], (float) $offer['confidence']);
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultat - Tilbudssammenligning</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="container">
        <h1>Resultat</h1>
        <p class="lead">Import #<?= (int) $importId; ?> sammenligner <strong><?= htmlspecialchars($store1['name']); ?></strong> og <strong><?= htmlspecialchars($store2['name']); ?></strong>.</p>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Produktnavn (normalisert)</th>
                        <th>Pris <?= htmlspecialchars($store1['name']); ?></th>
                        <th>Pris <?= htmlspecialchars($store2['name']); ?></th>
                        <th>Match confidence</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="4">Ingen tilbud funnet i denne importen.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['normalized_name']); ?></td>
                                <td><?= $row['price_store_1'] !== null ? number_format((float) $row['price_store_1'], 2, ',', ' ') . ' kr' : ''; ?></td>
                                <td><?= $row['price_store_2'] !== null ? number_format((float) $row['price_store_2'], 2, ',', ' ') . ' kr' : ''; ?></td>
                                <td><?= number_format((float) $row['confidence'], 1, '.', ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <p><a class="back-link" href="index.php">← Tilbake til opplasting</a></p>
    </main>
</body>
</html>
