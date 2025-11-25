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

function formatDecimal($number)
{
    return number_format((float)$number, 8, '.', '');
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

$currencyOptions = [];
$currencyStmt = $conn->prepare("SELECT DISTINCT UPPER(currency) AS currency FROM orders WHERE user_id = ? AND status = 'OPEN' ORDER BY currency");
if ($currencyStmt) {
    $currencyStmt->bind_param('i', $userId);
    $currencyStmt->execute();
    $currencyResult = $currencyStmt->get_result();
    if ($currencyResult) {
        while ($row = $currencyResult->fetch_assoc()) {
            $currency = $row['currency'];
            if ($currency !== '') {
                $currencyOptions[] = $currency;
            }
        }
    }
}

$assetFilter = isset($_GET['asset']) ? trim($_GET['asset']) : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'open';
$statusFilter = in_array($statusFilter, ['open', 'all']) ? $statusFilter : 'open';

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
    if ($filterTypes === 'is') {
        $ordersStmt->bind_param('is', $userId, $assetFilter);
    } else {
        $ordersStmt->bind_param('i', $userId);
    }
    $ordersStmt->execute();
    $ordersResult = $ordersStmt->get_result();
} else {
    $ordersResult = false;
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
                <input type="number" step="0.00000001" min="0" name="entry_price" id="entry_price" required>
            </div>
            <div class="form-control">
                <label for="currency">Price currency</label>
                <input list="currencyOptionsList" name="currency" id="currency" value="<?php echo h($currencyOptions[0] ?? ''); ?>" placeholder="e.g. USD" required>
                <datalist id="currencyOptionsList">
                    <?php foreach ($currencyOptions as $currency): ?>
                        <option value="<?php echo h($currency); ?>"></option>
                    <?php endforeach; ?>
                </datalist>
                <p class="hint">Used for entry price and all closes on this order. Suggestions are loaded from your open orders.</p>
            </div>
            <div class="form-control">
                <label for="total_cost">Total cost (optional)</label>
                <input type="number" step="0.00000001" min="0" name="total_cost" id="total_cost" placeholder="Auto-calculated">
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
            <?php if ($ordersResult && $ordersResult->num_rows > 0): ?>
                <?php while ($order = $ordersResult->fetch_assoc()): ?>
                    <?php
                    $totalCost = ($order['quantity'] * $order['entry_price']) + $order['fee'];
                    $isClosed = $order['status'] === 'CLOSED';
                    $assetSymbol = strtoupper($order['asset']);
                    ?>
                    <article class="order-card <?php echo $isClosed ? 'status-closed' : 'status-open'; ?>"
                             data-entry-price="<?php echo formatDecimal($order['entry_price']); ?>"
                             data-remaining="<?php echo formatDecimal($order['remaining_quantity']); ?>"
                             data-asset="<?php echo h(strtolower($order['asset'])); ?>"
                             data-asset-symbol="<?php echo h($assetSymbol); ?>"
                             data-status="<?php echo h(strtolower($order['status'])); ?>"
                             data-total-cost="<?php echo formatDecimal($totalCost); ?>"
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
                                <p class="mono"><?php echo formatDecimal($order['entry_price']); ?> <?php echo h(strtoupper($order['currency'] ?? 'USD')); ?></p>
                            </div>
                            <div class="order-stat">
                                <p class="eyebrow">Total cost</p>
                                <p class="mono"><?php echo formatDecimal($totalCost); ?> <?php echo h(strtoupper($order['currency'] ?? 'USD')); ?></p>
                            </div>
                            <div class="order-stat">
                                <p class="eyebrow">Unrealized P/L</p>
                                <p class="mono profit unrealized">-</p>
                            </div>
                        </div>
                        <div class="order-card__actions">
                            <a class="btn ghost" href="order_detail.php?id=<?php echo (int)$order['id']; ?>">Details</a>
                            <?php if (!$isClosed): ?>
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
                    </article>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="muted">No orders yet. Add your first BUY above.</p>
            <?php endif; ?>
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
