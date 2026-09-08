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

$valuationHistory = [];
$historyTable = $conn->query("SHOW TABLES LIKE 'asset_valuations'");
if ($historyTable && $historyTable->num_rows > 0) {
    $historyStmt = $conn->prepare('SELECT asset_id, gross_value, loan_amount, ownership_percent, valuation_date FROM asset_valuations WHERE user_id = ? ORDER BY asset_id, valuation_date DESC, id DESC');
    if ($historyStmt) {
        $historyStmt->bind_param('i', $userId);
        $historyStmt->execute();
        $historyResult = $historyStmt->get_result();
        while ($historyResult && ($row = $historyResult->fetch_assoc())) {
            $assetHistory = &$valuationHistory[(int)$row['asset_id']];
            if (count($assetHistory ?? []) < 2) {
                $assetHistory[] = $row;
            }
        }
    }
}

$total = 0;
$totalLoan = 0;
$totalAssets = 0;
$categoryTotals = array_fill_keys(array_keys($categoryLabels), 0);
foreach ($assets as $asset) {
    $ownedValue = (float)$asset['gross_value'] * ((float)$asset['ownership_percent'] / 100);
    $loanAmount = (float)($asset['loan_amount'] ?? 0);
    $totalAssets += $ownedValue;
    $totalLoan += $loanAmount;
    $netValue = $ownedValue - $loanAmount;
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
            <p class="eyebrow">Din økonomi</p>
            <h1>Mine verdier</h1>
            <p>En enkel oversikt over det du eier, det du skylder og formuen din.</p>
        </div>
        <div class="user-box">
            <span>Innlogget som <strong><?php echo h($currentUser['navn'] ?? $currentUser['epost'] ?? 'bruker'); ?></strong></span>
            <a href="logout.php">Logg ut</a>
        </div>
    </header>

    <?php if ($flash): ?>
        <div class="alert <?php echo h($flash['type']); ?>"><?php echo h($flash['message']); ?></div>
    <?php endif; ?>

    <section class="wealth-card" aria-label="Formue">
        <p class="eyebrow">Din formue</p>
        <strong><?php echo h(nok($total)); ?></strong>
        <div class="wealth-breakdown">
            <span><small>Verdier</small><?php echo h(nok($totalAssets)); ?></span>
            <span><small>Gjeld</small><?php echo h(nok($totalLoan)); ?></span>
        </div>
    </section>

    <section class="category-overview card" aria-labelledby="categoryTitle">
        <div class="section-heading"><div><p class="eyebrow">Fordeling</p><h2 id="categoryTitle">Hvor formuen ligger</h2></div><span><?php echo count($assets); ?> eiendeler</span></div>
        <div class="category-bars">
            <?php $positiveTotal = max(1, array_sum(array_map(static fn($value) => max(0, $value), $categoryTotals))); ?>
            <?php foreach ($categoryLabels as $key => $label): ?>
                <?php $share = max(0, $categoryTotals[$key]) / $positiveTotal * 100; ?>
                <div class="category-bar"><div><span><?php echo h($label); ?></span><strong><?php echo h(nok($categoryTotals[$key])); ?></strong></div><span class="bar-track"><i style="width:<?php echo min(100, $share); ?>%"></i></span></div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="content-grid">
        <article class="card">
            <h2><?php echo $editing ? 'Endre eiendel' : 'Legg til eiendel'; ?></h2>
            <form method="POST" action="actions.php" class="asset-form">
                <input type="hidden" name="action" value="save_asset">
                <input type="hidden" name="asset_id" value="<?php echo h($editing['id'] ?? '0'); ?>">
                <input type="hidden" name="currency" value="NOK">
                <label>Hva gjelder det?
                    <select name="category" required><?php foreach ($categoryLabels as $key => $label): ?><option value="<?php echo h($key); ?>" <?php echo ($editing['category'] ?? '') === $key ? 'selected' : ''; ?>><?php echo h($label); ?></option><?php endforeach; ?></select>
                </label>
                <label>Navn<input type="text" name="name" value="<?php echo h($editing['name'] ?? ''); ?>" placeholder="F.eks. Bolig eller sparekonto" required></label>
                <label>Verdi<input type="number" inputmode="decimal" step="0.01" min="0" name="gross_value" value="<?php echo h($editing['gross_value'] ?? ''); ?>" placeholder="0 kr" required></label>
                <label>Gjeld knyttet til verdien<input type="number" inputmode="decimal" step="0.01" min="0" name="loan_amount" value="<?php echo h($editing['loan_amount'] ?? '0'); ?>" placeholder="0 kr"></label>
                <details class="form-details" <?php echo $editing ? 'open' : ''; ?>>
                    <summary>Flere detaljer <span>Gir en mer presis oversikt</span></summary>
                    <div class="detail-fields">
                        <label>Type<input type="text" name="asset_type" value="<?php echo h($editing['asset_type'] ?? ''); ?>" placeholder="F.eks. leilighet eller indeksfond"></label>
                        <label>Bank / leverandør<input type="text" name="provider" value="<?php echo h($editing['provider'] ?? ''); ?>" placeholder="F.eks. DNB"></label>
                        <label>Eierandel (%)<input type="number" name="ownership_percent" min="0" max="100" step="0.01" value="<?php echo h($editing['ownership_percent'] ?? '100'); ?>"></label>
                        <label>Verdsatt dato<input type="date" name="valuation_date" value="<?php echo h($editing['valuation_date'] ?? date('Y-m-d')); ?>"></label>
                        <label class="wide">Notater<textarea name="notes" rows="3" placeholder="Forsikring, kontonummer, forutsetninger …"><?php echo h($editing['notes'] ?? ''); ?></textarea></label>
                    </div>
                </details>
                <div class="actions-row"><button type="submit" class="btn primary"><?php echo $editing ? 'Oppdater' : 'Legg til'; ?></button><?php if ($editing): ?><a class="btn ghost" href="index.php">Avbryt</a><?php endif; ?></div>
            </form>
        </article>

        <article class="card list-card">
            <div class="section-heading"><h2>Verdier og gjeld</h2><span id="assetResultCount"><?php echo count($assets); ?> vist</span></div>
            <div class="list-tools">
                <label>Søk<input type="search" id="assetSearch" placeholder="Navn, type eller leverandør"></label>
                <label>Kategori<select id="assetCategory"><option value="">Alle</option><?php foreach ($categoryLabels as $key => $label): ?><option value="<?php echo h($key); ?>"><?php echo h($label); ?></option><?php endforeach; ?></select></label>
                <label>Sorter<select id="assetSort"><option value="value-desc">Høyest formue</option><option value="name">Navn A–Å</option><option value="updated">Sist oppdatert</option></select></label>
            </div>
            <?php if (empty($assets)): ?>
                <p class="empty">Ingen eiendeler er registrert ennå.</p>
            <?php else: ?>
                <div class="asset-list">
                    <?php foreach ($assets as $asset): ?>
                        <?php
                            $ownedValue = (float)$asset['gross_value'] * ((float)$asset['ownership_percent'] / 100);
                            $loanAmount = (float)($asset['loan_amount'] ?? 0);
                            $netValue = $ownedValue - $loanAmount;
                        ?>
                        <?php
                            $history = $valuationHistory[(int)$asset['id']] ?? [];
                            $historyDelta = null;
                            if (count($history) >= 2) {
                                $latestNet = (float)$history[0]['gross_value'] * ((float)$history[0]['ownership_percent'] / 100) - (float)$history[0]['loan_amount'];
                                $previousNet = (float)$history[1]['gross_value'] * ((float)$history[1]['ownership_percent'] / 100) - (float)$history[1]['loan_amount'];
                                $historyDelta = $latestNet - $previousNet;
                            }
                        ?>
                        <section class="asset-row" data-category="<?php echo h($asset['category']); ?>" data-name="<?php echo h(($asset['name'] ?? '') . ' ' . ($asset['asset_type'] ?? '') . ' ' . ($asset['provider'] ?? '')); ?>" data-value="<?php echo $netValue; ?>" data-updated="<?php echo h($asset['updated_at'] ?? ''); ?>">
                            <div>
                                <span class="pill"><?php echo h($categoryLabels[$asset['category']] ?? 'Annet'); ?></span>
                                <?php if (!empty($asset['asset_type'])): ?><span class="pill muted-pill"><?php echo h($asset['asset_type']); ?></span><?php endif; ?>
                                <h3><?php echo h($asset['name']); ?></h3>
                                <p>Verdi <?php echo h(nok($ownedValue)); ?></p>
                                <?php if (!empty($asset['provider'])): ?><p><?php echo h($asset['provider']); ?> · verdsatt <?php echo h($asset['valuation_date'] ?: 'uten dato'); ?></p><?php endif; ?>
                                <?php if (!empty($asset['notes'])): ?><p class="notes"><?php echo h($asset['notes']); ?></p><?php endif; ?>
                            </div>
                            <div class="value-box">
                                <strong><?php echo h(nok($netValue)); ?></strong>
                                <span>Formue fra denne: <?php echo h(nok($netValue)); ?></span>
                                <?php if ($loanAmount > 0): ?><span class="debt">Gjeld <?php echo h(nok($loanAmount)); ?></span><?php endif; ?>
                                <?php if ($historyDelta !== null): ?><span class="history-delta <?php echo $historyDelta >= 0 ? 'up' : 'down'; ?>"><?php echo $historyDelta >= 0 ? '+' : ''; ?><?php echo h(nok($historyDelta)); ?> siden forrige verdi</span><?php endif; ?>
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
<script>
(() => {
    const search = document.getElementById('assetSearch');
    const category = document.getElementById('assetCategory');
    const sort = document.getElementById('assetSort');
    const list = document.querySelector('.asset-list');
    const count = document.getElementById('assetResultCount');
    if (!list) return;
    const rows = Array.from(list.querySelectorAll('.asset-row'));
    const update = () => {
        const term = (search?.value || '').trim().toLowerCase();
        const selectedCategory = category?.value || '';
        const visible = rows.filter(row => (!term || row.dataset.name.toLocaleLowerCase('nb-NO').includes(term)) && (!selectedCategory || row.dataset.category === selectedCategory));
        rows.forEach(row => row.hidden = !visible.includes(row));
        visible.sort((a, b) => {
            if (sort?.value === 'name') return a.dataset.name.localeCompare(b.dataset.name, 'nb');
            if (sort?.value === 'updated') return b.dataset.updated.localeCompare(a.dataset.updated);
            return Number(b.dataset.value) - Number(a.dataset.value);
        }).forEach(row => list.appendChild(row));
        if (count) count.textContent = `${visible.length} vist`;
    };
    [search, category, sort].forEach(element => element?.addEventListener(element === search ? 'input' : 'change', update));
    update();
})();
</script>
</body>
</html>
