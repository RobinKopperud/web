<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';
require_once __DIR__ . '/auth.php';

ensure_logged_in();
$currentUser = fetch_current_user($conn);
$userId = (int)($_SESSION['user_id'] ?? 0);

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function nok($value): string
{
    return number_format((float)$value, 0, ',', ' ') . ' kr';
}

$categoryLabels = [
    'property' => 'Bolig og eiendom',
    'crypto' => 'Krypto',
    'cash' => 'Cash og bank',
    'other' => 'Annet',
];

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$stmt = $conn->prepare('SELECT * FROM assets WHERE user_id = ? ORDER BY category, updated_at DESC');
$assets = [];
if ($stmt) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $assets = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

$total = 0;
$categoryTotals = array_fill_keys(array_keys($categoryLabels), 0);
foreach ($assets as $asset) {
    $netValue = (float)$asset['gross_value'] * ((float)$asset['ownership_percent'] / 100);
    $total += $netValue;
    $categoryTotals[$asset['category']] = ($categoryTotals[$asset['category']] ?? 0) + $netValue;
}

$editId = (int)($_GET['edit'] ?? 0);
$editing = null;
foreach ($assets as $asset) {
    if ((int)$asset['id'] === $editId) {
        $editing = $asset;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mine verdier</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="shell">
    <header class="hero">
        <div>
            <p class="eyebrow">Personlig eiendelsoversikt</p>
            <h1>Mine verdier</h1>
            <p>Registrer bolig, krypto, cash og andre eiendeler med verdi, leverandør og eierandel.</p>
        </div>
        <div class="user-box">
            <span>Innlogget som <strong><?php echo h($currentUser['navn'] ?? $currentUser['epost'] ?? 'bruker'); ?></strong></span>
            <a href="logout.php">Logg ut</a>
        </div>
    </header>

    <?php if ($flash): ?>
        <div class="alert <?php echo h($flash['type']); ?>"><?php echo h($flash['message']); ?></div>
    <?php endif; ?>

    <section class="summary-grid" aria-label="Oppsummering">
        <article class="card total-card">
            <p class="eyebrow">Total nettoverdi</p>
            <strong><?php echo h(nok($total)); ?></strong>
            <span>Basert på verdi × eierandel.</span>
        </article>
        <?php foreach ($categoryLabels as $key => $label): ?>
            <article class="card stat-card">
                <p><?php echo h($label); ?></p>
                <strong><?php echo h(nok($categoryTotals[$key] ?? 0)); ?></strong>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="content-grid">
        <article class="card">
            <h2><?php echo $editing ? 'Endre eiendel' : 'Legg til eiendel'; ?></h2>
            <form method="POST" action="actions.php" class="asset-form">
                <input type="hidden" name="action" value="save_asset">
                <input type="hidden" name="asset_id" value="<?php echo h($editing['id'] ?? '0'); ?>">
                <label>Type
                    <select name="category" required>
                        <?php foreach ($categoryLabels as $key => $label): ?>
                            <option value="<?php echo h($key); ?>" <?php echo ($editing['category'] ?? '') === $key ? 'selected' : ''; ?>><?php echo h($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Navn
                    <input type="text" name="name" value="<?php echo h($editing['name'] ?? ''); ?>" placeholder="F.eks. Leilighet, BTC, DNB brukskonto" required>
                </label>
                <label>Leverandør / lokasjon
                    <input type="text" name="provider" value="<?php echo h($editing['provider'] ?? ''); ?>" placeholder="F.eks. bank, børs eller adresse">
                </label>
                <div class="split">
                    <label>Verdi
                        <input type="number" step="0.01" min="0" name="gross_value" value="<?php echo h($editing['gross_value'] ?? ''); ?>" required>
                    </label>
                    <label>Valuta
                        <input type="text" name="currency" maxlength="10" value="<?php echo h($editing['currency'] ?? 'NOK'); ?>" required>
                    </label>
                </div>
                <div class="split">
                    <label>Eierandel %
                        <input type="number" step="0.01" min="0" max="100" name="ownership_percent" value="<?php echo h($editing['ownership_percent'] ?? '100'); ?>" required>
                    </label>
                    <label>Verdidato
                        <input type="date" name="valuation_date" value="<?php echo h($editing['valuation_date'] ?? date('Y-m-d')); ?>">
                    </label>
                </div>
                <label>Notater
                    <textarea name="notes" rows="3" placeholder="Valgfritt"><?php echo h($editing['notes'] ?? ''); ?></textarea>
                </label>
                <div class="actions-row">
                    <button type="submit" class="btn primary">Lagre</button>
                    <?php if ($editing): ?><a class="btn ghost" href="index.php">Avbryt</a><?php endif; ?>
                </div>
            </form>
        </article>

        <article class="card list-card">
            <h2>Registrerte eiendeler</h2>
            <?php if (empty($assets)): ?>
                <p class="empty">Ingen eiendeler er registrert ennå.</p>
            <?php else: ?>
                <div class="asset-list">
                    <?php foreach ($assets as $asset): ?>
                        <?php $netValue = (float)$asset['gross_value'] * ((float)$asset['ownership_percent'] / 100); ?>
                        <section class="asset-row">
                            <div>
                                <span class="pill"><?php echo h($categoryLabels[$asset['category']] ?? 'Annet'); ?></span>
                                <h3><?php echo h($asset['name']); ?></h3>
                                <p><?php echo h($asset['provider'] ?: 'Ingen leverandør'); ?> · <?php echo h($asset['ownership_percent']); ?> % eid</p>
                                <?php if (!empty($asset['notes'])): ?><p class="notes"><?php echo h($asset['notes']); ?></p><?php endif; ?>
                            </div>
                            <div class="value-box">
                                <strong><?php echo h(nok($netValue)); ?></strong>
                                <span>Brutto <?php echo h(number_format((float)$asset['gross_value'], 2, ',', ' ') . ' ' . $asset['currency']); ?></span>
                                <div class="row-actions">
                                    <a href="?edit=<?php echo (int)$asset['id']; ?>">Endre</a>
                                    <form method="POST" action="actions.php" onsubmit="return confirm('Slette denne eiendelen?');">
                                        <input type="hidden" name="action" value="delete_asset">
                                        <input type="hidden" name="asset_id" value="<?php echo (int)$asset['id']; ?>">
                                        <button type="submit">Slett</button>
                                    </form>
                                </div>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>
    </section>
</main>
</body>
</html>
