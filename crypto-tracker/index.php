<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';
require_once __DIR__ . '/auth.php';

ensure_logged_in();
$currentUser = fetch_current_user($conn);
$userId = (int)($_SESSION['user_id'] ?? 0);

date_default_timezone_set('UTC');
$currencyOptions = ['USD', 'EUR', 'NOK', 'USDT', 'GBP'];

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

// Fetch available assets for filter dropdown scoped to the user
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

// Fetch all user orders for portfolio calculations
$allOrdersStmt = $conn->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
$allOrders = [];
if ($allOrdersStmt) {
    $allOrdersStmt->bind_param('i', $userId);
    $allOrdersStmt->execute();
    $allOrders = $allOrdersStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$portfolioTotals = [
    'totalInvested' => 0.0,
    'openCostBasis' => 0.0,
];

foreach ($allOrders as $order) {
    $totalCost = ($order['quantity'] * $order['entry_price']) + $order['fee'];
    $portfolioTotals['totalInvested'] += $totalCost;

    if ($order['remaining_quantity'] > 0) {
        $allocation = ($order['remaining_quantity'] / $order['quantity']);
        $portfolioTotals['openCostBasis'] += $totalCost * $allocation;
    }
}

$realizedProfit = 0.0;
$realizedStmt = $conn->prepare('SELECT COALESCE(SUM(oc.profit), 0) AS realized FROM order_closures oc JOIN orders o ON oc.order_id = o.id WHERE o.user_id = ?');
if ($realizedStmt) {
    $realizedStmt->bind_param('i', $userId);
    $realizedStmt->execute();
    $realizedResult = $realizedStmt->get_result();
    if ($realizedResult) {
        $row = $realizedResult->fetch_assoc();
        $realizedProfit = (float)$row['realized'];
    }
}

$last30dProfit = 0.0;
$profit30Stmt = $conn->prepare('SELECT COALESCE(SUM(oc.profit), 0) AS profit_30d FROM order_closures oc JOIN orders o ON oc.order_id = o.id WHERE o.user_id = ? AND oc.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)');
if ($profit30Stmt) {
    $profit30Stmt->bind_param('i', $userId);
    $profit30Stmt->execute();
    $profit30Result = $profit30Stmt->get_result();
    if ($profit30Result) {
        $row = $profit30Result->fetch_assoc();
        $last30dProfit = (float)$row['profit_30d'];
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

    <section class="card portfolio" id="portfolioSummary"
             data-total-invested="<?php echo formatDecimal($portfolioTotals['totalInvested']); ?>"
             data-open-cost-basis="<?php echo formatDecimal($portfolioTotals['openCostBasis']); ?>"
             data-realized="<?php echo formatDecimal($realizedProfit); ?>"
             data-realized-currency="USD">
        <div class="portfolio-header">
            <div>
                <p class="eyebrow">Portfolio overview</p>
                <h2>Live P/L and ROI</h2>
            </div>
            <div class="report-actions">
                <a class="btn secondary" href="report.php?format=csv">Download CSV</a>
                <a class="btn" href="report.php?format=pdf">Download PDF</a>
            </div>
        </div>
        <div class="summary-grid">
            <div class="stat">
                <p class="eyebrow">Total invested</p>
                <h3 class="mono" id="totalInvestedValue"><?php echo formatDecimal($portfolioTotals['totalInvested']); ?> USD</h3>
                <p class="hint">All historical cost basis (fees included).</p>
            </div>
            <div class="stat">
                <p class="eyebrow">Realized P/L</p>
                <h3 class="mono" id="realizedValue"><?php echo formatDecimal($realizedProfit); ?> USD</h3>
                <p class="hint">Closed trades to date.</p>
            </div>
            <div class="stat">
                <p class="eyebrow">Unrealized P/L (live)</p>
                <h3 class="mono" id="unrealizedValue">-</h3>
                <p class="hint" id="liveStatus">Waiting for price feed…</p>
            </div>
            <div class="stat">
                <p class="eyebrow">Portfolio value</p>
                <h3 class="mono" id="portfolioValue">-</h3>
                <p class="hint">Marked to market for open positions.</p>
            </div>
            <div class="stat">
                <p class="eyebrow">Lifetime ROI</p>
                <h3 class="mono" id="roiValue">-</h3>
                <p class="hint">(Realized + unrealized) / total invested.</p>
            </div>
            <div class="stat">
                <p class="eyebrow">Last 30 days</p>
                <h3 class="mono"><?php echo formatDecimal($last30dProfit); ?> USD</h3>
                <p class="hint">Realized P/L in the past 30 days.</p>
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
                <input type="number" step="0.00000001" min="0" name="entry_price" id="entry_price" required>
            </div>
            <div class="form-control">
                <label for="currency">Price currency</label>
                <select name="currency" id="currency">
                    <?php foreach ($currencyOptions as $currency): ?>
                        <option value="<?php echo h($currency); ?>" <?php echo $currency === 'USD' ? 'selected' : ''; ?>><?php echo h($currency); ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="hint">Used for entry price and all closes on this order.</p>
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
                <label for="currentPrice">Live price feed</label>
                <input type="number" step="0.00000001" id="currentPrice" placeholder="Override price for selected asset">
                <button type="button" class="btn" id="updatePrice">Apply override</button>
                <button type="button" class="btn secondary" id="refreshPrices">Refresh now</button>
                <p class="hint">Prices auto-refresh every 60 seconds. Manual override applies to the filtered asset only.</p>
            </div>
            <div class="live-pill" id="livePulse">Live</div>
        </div>

        <div class="table-wrapper">
            <table id="ordersTable">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Asset</th>
                    <th>Status</th>
                    <th>Quantity</th>
                    <th>Entry price</th>
                    <th>Live price</th>
                    <th>Total cost</th>
                    <th>Unrealized P/L</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($ordersResult && $ordersResult->num_rows > 0): ?>
                    <?php while ($order = $ordersResult->fetch_assoc()): ?>
                        <?php
                        $totalCost = ($order['quantity'] * $order['entry_price']) + $order['fee'];
                        $isClosed = $order['status'] === 'CLOSED';
                        $rowClass = $isClosed ? 'status-closed' : 'status-open';
                        $assetSymbol = strtoupper($order['asset']);
                        $remainingCostBasis = $isClosed ? 0 : $totalCost * ($order['remaining_quantity'] / $order['quantity']);
                        ?>
                        <tr class="<?php echo $rowClass; ?>" data-entry-price="<?php echo formatDecimal($order['entry_price']); ?>" data-remaining="<?php echo formatDecimal($order['remaining_quantity']); ?>" data-asset="<?php echo h(strtolower($order['asset'])); ?>" data-asset-symbol="<?php echo h($assetSymbol); ?>" data-status="<?php echo h(strtolower($order['status'])); ?>" data-open-cost="<?php echo formatDecimal($remainingCostBasis); ?>" data-total-cost="<?php echo formatDecimal($totalCost); ?>" data-currency="<?php echo h($order['currency'] ?? 'USD'); ?>">
                            <td data-label="ID"><a href="order_detail.php?id=<?php echo (int)$order['id']; ?>">#<?php echo (int)$order['id']; ?></a></td>
                            <td data-label="Asset"><?php echo h($order['asset']); ?></td>
                            <td data-label="Status"><span class="badge <?php echo strtolower($order['status']); ?>"><?php echo h($order['status']); ?></span></td>
                            <td data-label="Quantity" class="mono"><?php echo formatDecimal($order['quantity']); ?></td>
                            <td data-label="Entry price">
                                <div class="cell-stack">
                                    <span class="mono"><?php echo formatDecimal($order['entry_price']); ?></span>
                                    <span class="chip"><?php echo h($order['currency'] ?? 'USD'); ?></span>
                                </div>
                            </td>
                            <td data-label="Live price" class="live-price">-</td>
                            <td data-label="Total cost">
                                <div class="cell-stack">
                                    <span class="mono"><?php echo formatDecimal($totalCost); ?></span>
                                    <span class="chip"><?php echo h($order['currency'] ?? 'USD'); ?></span>
                                </div>
                            </td>
                            <td data-label="Unrealized P/L" class="profit unrealized">-</td>
                            <td data-label="Actions" class="actions">
                                <a class="btn ghost" href="order_detail.php?id=<?php echo (int)$order['id']; ?>">Details</a>
                                <?php if (!$isClosed): ?>
                                    <button class="btn secondary open-close-modal" type="button"
                                            data-order-id="<?php echo (int)$order['id']; ?>"
                                            data-asset="<?php echo h($order['asset']); ?>"
                                            data-remaining="<?php echo formatDecimal($order['remaining_quantity']); ?>"
                                            data-entry-price="<?php echo formatDecimal($order['entry_price']); ?>"
                                            data-currency="<?php echo h($order['currency'] ?? 'USD'); ?>">
                                        Close
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="muted">No orders yet. Add your first BUY above.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
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
