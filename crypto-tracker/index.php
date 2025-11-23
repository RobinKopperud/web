<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

date_default_timezone_set('UTC');
$currencyOptions = ['USD', 'EUR', 'NOK', 'USDT', 'GBP'];

// Fetch available assets for filter dropdown
$assetOptions = [];
$assetResult = $conn->query("SELECT DISTINCT asset FROM orders ORDER BY asset");
if ($assetResult) {
    while ($row = $assetResult->fetch_assoc()) {
        $assetOptions[] = $row['asset'];
    }
}

$assetFilter = isset($_GET['asset']) ? trim($_GET['asset']) : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'open';
$statusFilter = in_array($statusFilter, ['open', 'all']) ? $statusFilter : 'open';

$ordersResult = $conn->query('SELECT * FROM orders ORDER BY created_at DESC');

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
                <label for="currentPrice">Current price for selected asset</label>
                <input type="number" step="0.00000001" id="currentPrice" placeholder="Enter current price">
                <button type="button" class="btn" id="updatePrice">Update unrealized P/L</button>
            </div>
            <p class="hint">Unrealized profit = remaining quantity × (current price − entry price)</p>
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
                    <th>Total cost</th>
                    <th>Unrealized P/L</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php while ($order = $ordersResult->fetch_assoc()): ?>
                    <?php
                    $totalCost = ($order['quantity'] * $order['entry_price']) + $order['fee'];
                    $isClosed = $order['status'] === 'CLOSED';
                    $rowClass = $isClosed ? 'status-closed' : 'status-open';
                    ?>
                    <tr class="<?php echo $rowClass; ?>" data-entry-price="<?php echo formatDecimal($order['entry_price']); ?>" data-remaining="<?php echo formatDecimal($order['remaining_quantity']); ?>" data-asset="<?php echo h(strtolower($order['asset'])); ?>" data-status="<?php echo h(strtolower($order['status'])); ?>">
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
