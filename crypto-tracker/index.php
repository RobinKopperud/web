<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/performance_data.php';

ensure_logged_in();
$currentUser = fetch_current_user($conn);
$userId = (int)($_SESSION['user_id'] ?? 0);

date_default_timezone_set('UTC');

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function formatDecimal($number, int $decimals = 8)
{
    return number_format((float)$number, $decimals, '.', '');
}

function formatDisplay($number, int $decimals = 4)
{
    return number_format((float)$number, $decimals, '.', '');
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Fetch available assets for filter dropdown scoped to the user's open orders
$assetOptions = [];
$assetStmt = $conn->prepare("SELECT DISTINCT asset FROM orders WHERE user_id = ? ORDER BY asset");
if ($assetStmt) {
    $assetStmt->bind_param('i', $userId);
    $assetStmt->execute();
    $assetResult = $assetStmt->get_result();
    if ($assetResult) {
        while ($row = $assetResult->fetch_assoc()) {
            $assetOptions[] = $row['asset'];
        }
    }
}

$assetFilter = isset($_GET['asset']) ? trim($_GET['asset']) : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$statusFilter = in_array($statusFilter, ['open', 'all']) ? $statusFilter : 'all';

$filterSql = "WHERE user_id = ?";
$filterTypes = 'i';
$filterValues = [$userId];

$ordersQuery = "SELECT * FROM orders $filterSql ORDER BY created_at DESC";
$ordersStmt = $conn->prepare($ordersQuery);
if ($ordersStmt) {
    $bindTypes = $filterTypes;
    $bindValues = $filterValues;
    $ordersStmt->bind_param($bindTypes, ...$bindValues);
    $ordersStmt->execute();
    $ordersResult = $ordersStmt->get_result();
    $orders = $ordersResult ? $ordersResult->fetch_all(MYSQLI_ASSOC) : [];
} else {
    $ordersResult = false;
    $orders = [];
}

?>
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kryptooversikt</title>
    <link rel="stylesheet" href="assets/style.css?v=<?php echo filemtime(__DIR__ . '/assets/style.css'); ?>">
</head>
<body>
<div class="container">
    <header>
        <h1>Kryptooversikt</h1>
        <p class="subtitle">Porteføljen din, live og samlet på ett sted.</p>
        <div class="user-meta">
            <span>Innlogget som <strong><?php echo h($currentUser['navn'] ?? $currentUser['epost'] ?? 'User'); ?></strong></span>
            <a class="link" href="logout.php">Logg ut</a>
        </div>
    </header>

    <?php if ($flash): ?>
        <div class="alert <?php echo h($flash['type']); ?>"><?php echo h($flash['message']); ?></div>
    <?php endif; ?>

    <section class="card integrated-filters" aria-label="Filtrer oversikten">
        <form method="GET" class="filter-row" id="integratedFilters">
            <div class="form-control">
                <label for="filter_asset">Kryptovaluta</label>
                <select name="asset" id="filter_asset">
                    <option value="">Alle valutaer</option>
                    <?php foreach ($assetOptions as $assetOption): ?>
                        <option value="<?php echo h($assetOption); ?>" <?php echo $assetFilter === $assetOption ? 'selected' : ''; ?>><?php echo h($assetOption); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-control">
                <label>Status</label>
                <div class="pill-group">
                    <label><input type="radio" name="status" value="open" <?php echo $statusFilter === 'open' ? 'checked' : ''; ?>> Kun åpne</label>
                    <label><input type="radio" name="status" value="all" <?php echo $statusFilter === 'all' ? 'checked' : ''; ?>> Alle ordrer</label>
                </div>
            </div>
            <div class="form-control"><label for="orderSearch">Søk i ordre</label><input type="search" name="q" id="orderSearch" placeholder="Valuta, strategi eller notat" value="<?php echo h($_GET['q'] ?? ''); ?>"></div>
            <div class="form-actions">
                <button type="submit" class="btn">Bruk filtre</button><button type="reset" class="btn ghost">Nullstill</button><button type="button" class="btn" id="refreshPrices">Oppdater priser</button>
            </div>
        </form>
    </section>


    <nav class="top-nav card" aria-label="Visning">
        <button type="button" class="btn nav-btn is-active" data-target="portfolioSection">Portefølje</button>
        <button type="button" class="btn nav-btn" data-target="ordersSection">Ordre</button>
        <button type="button" class="btn nav-btn" data-target="addOrderSection">Legg inn ordre</button>
        <button type="button" class="btn nav-btn" data-target="averagesSection">Gjennomsnitt</button>
    </nav>

    <section class="card portfolio-header view-section" id="portfolioSection">
        <div>
            <h2>Portefølje i NOK</h2>
            <p class="hint">Beregnet fra filtrerte ordrer (valuta konvertert til NOK).</p>
        </div>
        <div class="summary-grid" id="portfolioSummary">
            <div class="stat">
                <p class="eyebrow">Investert</p>
                <p class="mono" id="totalInvestedNok">-</p>
            </div>
            <div class="stat">
                <p class="eyebrow">Realisert resultat</p>
                <p class="mono" id="realizedNok">-</p>
            </div>
            <div class="stat">
                <p class="eyebrow">Urealisert resultat</p>
                <p class="mono" id="unrealizedNok">-</p>
            </div>
            <div class="stat">
                <p class="eyebrow">Avkastning totalt</p>
                <p class="mono" id="lifetimeRoi">-</p>
            </div>
        </div>
        <div class="performance-overview">
            <div class="summary-grid">
                <div class="stat annual-stat"><p class="eyebrow">Årlig avkastning</p><p class="mono profit" id="portfolioAnnualReturn">–</p><p class="hint">Ordre med minst ett års eiertid. Tar hensyn til kjøps- og salgsdato.</p></div>
                <div class="stat"><p class="eyebrow">Samlet resultat</p><p class="mono profit" id="portfolioTotalProfit">–</p><p class="hint">Realisert og urealisert resultat.</p></div>
            </div>
            <p class="hint" id="performanceStatus" role="status">Venter på priser …</p>
            <p class="performance-example">10 % over 2 år tilsvarer <strong>4,88 % per år</strong>.</p>
            <p class="hint">NOK-tall bruker dagens valutakurser. Avkastning per år er annualisert, ikke en prognose. Ordre med under ett års eiertid vises med samlet avkastning, uten omregning til et år.</p>
            <div class="performance-charts">
                <section aria-labelledby="orderReturnTitle"><h3 id="orderReturnTitle">Avkastning og tid</h3><p class="hint">Samlet avkastning sammenlignet med avkastning per år. Ordrene beregnes i sin prisvaluta.</p><div id="orderReturnChart" class="performance-chart">Venter på beregning …</div></section>
                <section aria-labelledby="yearProfitTitle"><h3 id="yearProfitTitle">Realisert resultat per år</h3><p class="hint">Resultat fra registrerte salg, gruppert etter salgsår (UTC). Inkluderer ikke verdiendring i åpne posisjoner.</p><div id="yearProfitChart" class="performance-chart">Ingen registrerte salg.</div></section>
            </div>
        </div>
        <div class="allocation-panel">
            <div class="price-row"><div><h3>Fordeling</h3><p class="hint">Markedsverdi for åpne posisjoner.</p></div><small id="priceUpdated">Venter på priser …</small></div>
            <div id="allocationBreakdown" class="allocation-list"><p class="muted">Fordeling vises når priser er hentet.</p></div>
        </div>
    </section>

    <section class="card view-section is-hidden" id="addOrderSection">
        <h2>Legg til kjøp</h2>
        <form method="POST" action="actions.php" class="form-grid">
            <input type="hidden" name="action" value="create_order">
            <div class="form-control">
                <label for="asset">Valuta</label>
                <input type="text" name="asset" id="asset" value="<?php echo $assetFilter ? h($assetFilter) : 'BTC'; ?>" required>
            </div>
            <div class="form-control">
                <label for="quantity">Antall</label>
                <input type="number" step="0.00000001" min="0" name="quantity" id="quantity">
            </div>
            <div class="form-control">
                <label for="entry_price">Kjøpspris per enhet</label>
                <input type="number" step="any" min="0" name="entry_price" id="entry_price">
            </div>
            <div class="form-control">
                <label for="currency">Prisvaluta</label>
                <select name="currency" id="currency" required>
                    <option value="USD">USD</option>
                    <option value="EUR">EUR</option>
                    <option value="USDC">USDC</option>
                </select>
                <p class="hint">Prisvaluta for kjøp og salg.</p>
            </div>
            <div class="form-control">
                <label for="total_cost">Totalbeløp (valgfritt)</label>
                <input type="number" step="any" min="0" name="total_cost" id="total_cost" placeholder="Beregnes automatisk">
                <p class="hint">Fyll inn to av feltene antall, kjøpspris og totalbeløp, så beregnes det tredje.</p>
            </div>
            <div class="form-control">
                <label for="fee">Gebyr (valgfritt)</label>
                <input type="number" step="0.00000001" min="0" name="fee" id="fee" placeholder="0">
            </div>
            <div class="form-control">
                <label for="purchased_at">Kjøpt</label>
                <input type="datetime-local" name="purchased_at" id="purchased_at" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
            </div>
            <div class="form-control">
                <label for="strategy">Strategi (valgfritt)</label>
                <input type="text" name="strategy" id="strategy" maxlength="60" placeholder="F.eks. månedlig sparing">
            </div>
            <div class="form-control form-control--wide">
                <label for="notes">Investeringsnotat (valgfritt)</label>
                <textarea name="notes" id="notes" rows="3" maxlength="2000" placeholder="Hvorfor kjøpte du, og hva er planen?"></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn primary">Lagre kjøp</button>
            </div>
        </form>
    </section>

    <section class="card view-section is-hidden" id="ordersSection">
        <div class="price-row">
            <div>
                <h2>Posisjoner</h2>
                <p class="hint">Live-priser hentes fra Binance med symboler i formatet ASSETCURRENCY.</p>
            </div>
            <div class="price-actions">
                <div class="live-pill" id="livePulse">Live</div>
            </div>
        </div>

        <div id="ordersTable" class="order-grid">
            <?php if (!empty($orders)): ?>
                <?php foreach ($orders as $order): ?>
                    <?php
                    $totalCost = ($order['quantity'] * $order['entry_price']) + $order['fee'];
                    $isClosed = $order['status'] === 'CLOSED';
                    $assetSymbol = strtoupper($order['asset']);
                    $realizedForOrder = (float)($order['realized_profit'] ?? 0);
                    ?>
                    <article class="order-card <?php echo $isClosed ? 'status-closed' : 'status-open'; ?>"
                             data-performance="<?php echo h(json_encode(performance_order_data($order))); ?>"
                             data-entry-price="<?php echo formatDecimal($order['entry_price']); ?>"
                             data-quantity="<?php echo formatDecimal($order['quantity']); ?>"
                             data-remaining="<?php echo formatDecimal($isClosed ? 0 : $order['quantity']); ?>"
                             data-asset="<?php echo h(strtolower($order['asset'])); ?>"
                             data-asset-symbol="<?php echo h($assetSymbol); ?>"
                             data-search="<?php echo h(strtolower(($order['asset'] ?? '') . ' ' . ($order['strategy'] ?? '') . ' ' . ($order['notes'] ?? ''))); ?>"
                             data-status="<?php echo h(strtolower($order['status'])); ?>"
                             data-total-cost="<?php echo formatDecimal($totalCost); ?>"
                             data-realized-profit="<?php echo formatDecimal($realizedForOrder); ?>"
                             data-currency="<?php echo h(strtoupper($order['currency'] ?? 'USD')); ?>">
                        <header class="order-card__header">
                            <div>
                                <p class="eyebrow">Ordre #<?php echo (int)$order['id']; ?></p>
                                <h3><?php echo h($order['asset']); ?></h3>
                            <span class="badge <?php echo strtolower($order['status']); ?>"><?php echo $order['status'] === 'OPEN' ? 'Åpen' : 'Lukket'; ?></span>
                            </div>
                            <div class="order-card__live">
                                <p class="eyebrow">Livepris</p>
                                <div class="order-live-price">-</div>
                                <p class="chip"><?php echo h(strtoupper($order['currency'] ?? 'USD')); ?></p>
                            </div>
                        </header>
                        <div class="order-card__body">
                            <div class="order-stat">
                                <p class="eyebrow">Antall</p>
                                <p class="mono"><?php echo formatDecimal($order['quantity']); ?></p>
                            </div>
                            <div class="order-stat">
                                <p class="eyebrow">Kjøpspris</p>
                                <p class="mono"><?php echo formatDisplay($order['entry_price']); ?> <?php echo h(strtoupper($order['currency'] ?? 'USD')); ?></p>
                            </div>
                            <div class="order-stat">
                                <p class="eyebrow">Kostpris</p>
                                <p class="mono"><?php echo formatDisplay($totalCost); ?> <?php echo h(strtoupper($order['currency'] ?? 'USD')); ?></p>
                            </div>
                            <div class="order-stat">
                                <p class="eyebrow">Urealisert resultat</p>
                                <p class="mono profit unrealized">-</p>
                            </div>
                        </div>
                        <div class="order-performance">
                            <div><p class="eyebrow">Avkastning totalt</p><p class="mono profit order-total-return">–</p></div>
                            <div><p class="eyebrow">Avkastning per år</p><p class="mono profit order-annual-return">–</p></div>
                            <div><p class="eyebrow">Eiertid</p><p class="mono order-holding-period">–</p></div>
                            <p class="hint order-performance-note">Venter på beregning …</p>
                        </div>
                        <?php if (!empty($order['strategy']) || !empty($order['notes'])): ?>
                            <div class="journal-snippet"><strong><?php echo h($order['strategy'] ?: 'Notat'); ?></strong><?php if (!empty($order['notes'])): ?><span><?php echo h($order['notes']); ?></span><?php endif; ?></div>
                        <?php endif; ?>
                        <div class="order-card__actions">
                            <a class="btn ghost" href="order_detail.php?id=<?php echo (int)$order['id']; ?>">Detaljer</a>
                            <?php if (!$isClosed): ?>
                                <button class="btn ghost preview-toggle" type="button" aria-expanded="false">
                                    Forhåndsvis salg
                                </button>
                                <button class="btn secondary open-close-modal" type="button"
                                        data-order-id="<?php echo (int)$order['id']; ?>"
                                        data-asset="<?php echo h($order['asset']); ?>"
                                        data-remaining="<?php echo formatDecimal($isClosed ? 0 : $order['quantity']); ?>"
                                        data-entry-price="<?php echo formatDecimal($order['entry_price']); ?>"
                                        data-currency="<?php echo h(strtoupper($order['currency'] ?? 'USD')); ?>">
                                    Registrer salg
                                </button>
                            <?php endif; ?>
                        </div>
                        <?php if (!$isClosed): ?>
                            <div class="order-card__preview" hidden>
                                <div class="preview-row">
                                    <label for="preview-price-<?php echo (int)$order['id']; ?>">Pris for salg</label>
                                    <div class="input-with-addon">
                                        <input type="number" step="0.00000001" min="0" class="preview-price-input" id="preview-price-<?php echo (int)$order['id']; ?>" placeholder="0">
                                        <span class="input-addon"><?php echo h(strtoupper($order['currency'] ?? 'USD')); ?></span>
                                    </div>
                                </div>
                                <div class="preview-result">
                                    <p class="eyebrow">Fortjeneste</p>
                                    <p class="mono profit preview-profit">-</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="muted">Ingen ordrer ennå. Legg inn ditt første kjøp.</p>
            <?php endif; ?>
        </div>
    </section>

    <section class="card view-section is-hidden" id="averagesSection">
        <div class="price-row">
            <div>
                <h2>Gjennomsnittspris per asset</h2>
                <p class="hint">Vektet etter gjenstående mengde i filtrerte ordrer.</p>
            </div>
        </div>
        <div id="assetAverages" class="asset-average-grid">
            <p class="muted">Ingen åpne posisjoner i filteret.</p>
        </div>
    </section>

    <div class="modal" id="closeModal" aria-hidden="true" role="dialog" aria-labelledby="closeModalTitle">
        <div class="modal-dialog">
            <div class="modal-header">
                <div>
                    <p class="eyebrow" id="closeModalAsset">Registrer salg</p>
                    <h3 id="closeModalTitle">Ordre</h3>
                </div>
                <button type="button" class="icon-button" id="closeModalDismiss" aria-label="Lukk dialogen">×</button>
            </div>
            <form method="POST" action="actions.php" id="closeModalForm" class="modal-form">
                <input type="hidden" name="action" value="close_order">
                <input type="hidden" name="order_id" id="closeModalOrderId">

                <p class="hint" id="closeRemainingHelper">Hele ordren selges.</p>
                <div class="form-control">
                    <label for="close_price_modal">Salgspris per enhet</label>
                    <div class="input-with-addon">
                        <input type="number" step="0.00000001" min="0" name="close_price" id="close_price_modal" required>
                        <span class="input-addon" id="closeCurrencyBadge">USD</span>
                    </div>
                </div>
                <div class="form-control">
                    <label for="close_fee_modal">Salgsgebyr (valgfritt)</label>
                    <input type="number" step="0.00000001" min="0" name="close_fee" id="close_fee_modal" placeholder="0">
                </div>
                <div class="form-actions modal-actions">
                    <button type="button" class="btn" id="closeModalCancel">Avbryt</button>
                    <button type="submit" class="btn danger">Bekreft salg</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="assets/performance.js?v=<?php echo filemtime(__DIR__ . '/assets/performance.js'); ?>"></script>
<script src="assets/performance-ui.js?v=<?php echo filemtime(__DIR__ . '/assets/performance-ui.js'); ?>"></script>
<script src="assets/order-entry.js?v=<?php echo filemtime(__DIR__ . '/assets/order-entry.js'); ?>"></script>
<script src="assets/app.js?v=<?php echo filemtime(__DIR__ . '/assets/app.js'); ?>"></script>
</body>
</html>

