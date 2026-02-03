<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';
require_once __DIR__ . '/auth.php';

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
$assetStmt = $conn->prepare("SELECT DISTINCT asset FROM orders WHERE user_id = ? AND status = 'OPEN' ORDER BY asset");
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

if ($assetFilter !== '') {
    $filterSql .= " AND asset = ?";
    $filterTypes .= 's';
    $filterValues[] = $assetFilter;
}

if ($statusFilter === 'open') {
    $filterSql .= " AND status = 'OPEN'";
}

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

$closureProfits = [];

if (!empty($orders)) {
    $orderIds = array_column($orders, 'id');
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $profitQuery = "SELECT oc.order_id, oc.currency, COALESCE(SUM(oc.profit), 0) AS realized_profit FROM order_closures oc JOIN ord"
        . "ers o ON oc.order_id = o.id WHERE o.user_id = ? AND oc.order_id IN ($placeholders) GROUP BY oc.order_id, oc.currency";
    $profitStmt = $conn->prepare($profitQuery);

    if ($profitStmt) {
        $types = 'i' . str_repeat('i', count($orderIds));
        $bindParams = array_merge([$userId], $orderIds);
        $profitStmt->bind_param($types, ...$bindParams);
        $profitStmt->execute();
        $profitResult = $profitStmt->get_result();
        if ($profitResult) {
            while ($row = $profitResult->fetch_assoc()) {
                $closureProfits[(int)$row['order_id']] = (float)$row['realized_profit'];
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manual Crypto Order Tracker</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
    <section class="debug-panel" aria-live="polite">
        <div class="debug-title">API debug</div>
        <ul class="debug-list" id="apiDebugList">
            <li><span class="debug-label">Live-priser:</span> <code>Ingen spørring utført ennå.</code></li>
        </ul>
    </section>
    <header>
        <h1>Manual Crypto Order Tracker</h1>
        <p class="subtitle">Separate lines for each BUY, easy partial closes, clear profit per order.</p>
        <div class="user-meta">
            <span>Signed in as <strong><?php echo h($currentUser['navn'] ?? $currentUser['epost'] ?? 'User'); ?></strong></span>
            <a class="link" href="logout.php">Log out</a>
        </div>
    </header>

    <?php if ($flash): ?>
        <div class="alert <?php echo h($flash['type']); ?>"><?php echo h($flash['message']); ?></div>
    <?php endif; ?>

    <section class="card portfolio-header">
        <div>
            <h2>Portefølje i NOK</h2>
            <p class="hint">Beregnet fra filtrerte ordrer (valuta konvertert til NOK).</p>
        </div>
        <div class="summary-grid" id="portfolioSummary">
            <div class="stat">
                <p class="eyebrow">Total invested</p>
                <p class="mono" id="totalInvestedNok">-</p>
            </div>
            <div class="stat">
                <p class="eyebrow">Realized P/L</p>
                <p class="mono" id="realizedNok">-</p>
            </div>
            <div class="stat">
                <p class="eyebrow">Unrealized P/L</p>
                <p class="mono" id="unrealizedNok">-</p>
            </div>
            <div class="stat">
                <p class="eyebrow">Lifetime ROI</p>
                <p class="mono" id="lifetimeRoi">-</p>
            </div>
        </div>
    </section>

    <section class="card">
        <h2>Add a new BUY order</h2>
        <form method="POST" action="actions.php" class="form-grid">
            <input type="hidden" name="action" value="create_order">
            <div class="form-control">
                <label for="asset">Asset</label>
                <input type="text" name="asset" id="asset" value="<?php echo $assetFilter ? h($assetFilter) : 'BTC'; ?>" required>
            </div>
            <div class="form-control">
                <label for="quantity">Quantity</label>
                <input type="number" step="0.00000001" min="0" name="quantity" id="quantity" required>
            </div>
            <div class="form-control">
                <label for="entry_price">Entry price per unit</label>
                <input type="number" step="0.0001" min="0" name="entry_price" id="entry_price" required>
            </div>
            <div class="form-control">
                <label for="currency">Price currency</label>
                <select name="currency" id="currency" required>
                    <option value="USD">USD</option>
                    <option value="EUR">EUR</option>
                    <option value="USDC">USDC</option>
                </select>
                <p class="hint">Brukes for entry price og alle closes. Kun USD, EUR og USDC er tillatt.</p>
            </div>
            <div class="form-control">
                <label for="total_cost">Total cost (optional)</label>
                <input type="number" step="0.0001" min="0" name="total_cost" id="total_cost" placeholder="Auto-calculated">
                <p class="hint">Fill any two of quantity, entry price, and total to auto-calculate the third.</p>
            </div>
            <div class="form-control">
                <label for="fee">Fee (optional)</label>
                <input type="number" step="0.00000001" min="0" name="fee" id="fee" placeholder="0">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn primary">Add order</button>
            </div>
        </form>
    </section>

    <section class="card filters">
        <form method="GET" class="filter-row">
            <div class="form-control">
                <label for="filter_asset">Filter asset</label>
                <select name="asset" id="filter_asset">
                    <option value="">All assets</option>
                    <?php foreach ($assetOptions as $assetOption): ?>
                        <option value="<?php echo h($assetOption); ?>" <?php echo $assetFilter === $assetOption ? 'selected' : ''; ?>><?php echo h($assetOption); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-control">
                <label>Status</label>
                <div class="pill-group">
                    <label><input type="radio" name="status" value="open" <?php echo $statusFilter === 'open' ? 'checked' : ''; ?>> Open only</label>
                    <label><input type="radio" name="status" value="all" <?php echo $statusFilter === 'all' ? 'checked' : ''; ?>> All orders</label>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn">Apply filters</button>
            </div>
        </form>
    </section>

    <section class="card">
        <div class="price-row">
            <div>
                <h2>Orders with live prices</h2>
                <p class="hint">Live-priser hentes fra Binance med symboler i formatet ASSETCURRENCY.</p>
            </div>
            <div class="price-actions">
                <button type="button" class="btn" id="refreshPrices">Refresh now</button>
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
                    $realizedForOrder = $closureProfits[(int)$order['id']] ?? 0.0;
                    ?>
                    <article class="order-card <?php echo $isClosed ? 'status-closed' : 'status-open'; ?>"
                             data-entry-price="<?php echo formatDecimal($order['entry_price']); ?>"
                             data-quantity="<?php echo formatDecimal($order['quantity']); ?>"
                             data-remaining="<?php echo formatDecimal($order['remaining_quantity']); ?>"
                             data-asset="<?php echo h(strtolower($order['asset'])); ?>"
                             data-asset-symbol="<?php echo h($assetSymbol); ?>"
                             data-status="<?php echo h(strtolower($order['status'])); ?>"
                             data-total-cost="<?php echo formatDecimal($totalCost); ?>"
                             data-realized-profit="<?php echo formatDecimal($realizedForOrder); ?>"
                             data-currency="<?php echo h(strtoupper($order['currency'] ?? 'USD')); ?>">
                        <header class="order-card__header">
                            <div>
                                <p class="eyebrow">Order #<?php echo (int)$order['id']; ?></p>
                                <h3><?php echo h($order['asset']); ?></h3>
                                <span class="badge <?php echo strtolower($order['status']); ?>"><?php echo h($order['status']); ?></span>
                            </div>
                            <div class="order-card__live">
                                <p class="eyebrow">Live price</p>
                                <div class="order-live-price">-</div>
                                <p class="chip"><?php echo h(strtoupper($order['currency'] ?? 'USD')); ?></p>
                            </div>
                        </header>
                        <div class="order-card__body">
                            <div class="order-stat">
                                <p class="eyebrow">Quantity</p>
                                <p class="mono"><?php echo formatDecimal($order['quantity']); ?></p>
                            </div>
                            <div class="order-stat">
                                <p class="eyebrow">Entry price</p>
                                <p class="mono"><?php echo formatDisplay($order['entry_price']); ?> <?php echo h(strtoupper($order['currency'] ?? 'USD')); ?></p>
                            </div>
                            <div class="order-stat">
                                <p class="eyebrow">Total cost</p>
                                <p class="mono"><?php echo formatDisplay($totalCost); ?> <?php echo h(strtoupper($order['currency'] ?? 'USD')); ?></p>
                            </div>
                            <div class="order-stat">
                                <p class="eyebrow">Unrealized P/L</p>
                                <p class="mono profit unrealized">-</p>
                            </div>
                        </div>
                        <div class="order-card__actions">
                            <a class="btn ghost" href="order_detail.php?id=<?php echo (int)$order['id']; ?>">Details</a>
                            <?php if (!$isClosed): ?>
                                <button class="btn ghost preview-toggle" type="button" aria-expanded="false">
                                    Forhåndsvis salg
                                </button>
                                <button class="btn secondary open-close-modal" type="button"
                                        data-order-id="<?php echo (int)$order['id']; ?>"
                                        data-asset="<?php echo h($order['asset']); ?>"
                                        data-remaining="<?php echo formatDecimal($order['remaining_quantity']); ?>"
                                        data-entry-price="<?php echo formatDecimal($order['entry_price']); ?>"
                                        data-currency="<?php echo h(strtoupper($order['currency'] ?? 'USD')); ?>">
                                    Close
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
                <p class="muted">No orders yet. Add your first BUY above.</p>
            <?php endif; ?>
        </div>
    </section>

    <section class="card">
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
                    <p class="eyebrow" id="closeModalAsset">Close order</p>
                    <h3 id="closeModalTitle">Order</h3>
                </div>
                <button type="button" class="icon-button" id="closeModalDismiss" aria-label="Close close order dialog">×</button>
            </div>
            <form method="POST" action="actions.php" id="closeModalForm" class="modal-form">
                <input type="hidden" name="action" value="close_order">
                <input type="hidden" name="order_id" id="closeModalOrderId">

                <div class="form-control">
                    <label for="close_quantity_modal">Close quantity <span class="hint" id="closeRemainingHelper"></span></label>
                    <input type="number" step="0.00000001" min="0" name="close_quantity" id="close_quantity_modal" required>
                </div>
                <div class="form-control">
                    <label for="close_price_modal">Close price per unit</label>
                    <div class="input-with-addon">
                        <input type="number" step="0.00000001" min="0" name="close_price" id="close_price_modal" required>
                        <span class="input-addon" id="closeCurrencyBadge">USD</span>
                    </div>
                </div>
                <div class="form-control">
                    <label for="close_fee_modal">Close fee (optional)</label>
                    <input type="number" step="0.00000001" min="0" name="close_fee" id="close_fee_modal" placeholder="0">
                </div>
                <div class="form-actions modal-actions">
                    <button type="button" class="btn" id="closeModalCancel">Cancel</button>
                    <button type="submit" class="btn danger">Confirm close</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="assets/app.js"></script>
</body>
</html>
