<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';
require_once __DIR__ . '/auth.php';

ensure_logged_in();
$userId = (int)($_SESSION['user_id'] ?? 0);

date_default_timezone_set('UTC');

function sanitize_currency(string $currency): string
{
    $allowed = ['USD', 'EUR', 'NOK', 'USDT', 'GBP'];
    $cleaned = strtoupper(trim($currency));

    return in_array($cleaned, $allowed, true) ? $cleaned : 'USD';
}

function redirect_with_flash(string $type, string $message, string $location = 'index.php')
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
    header('Location: ' . $location);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_flash('error', 'Unsupported request method.');
}

$action = $_POST['action'] ?? '';

if ($action === 'create_order') {
    $asset = trim($_POST['asset'] ?? '');
    $quantity = $_POST['quantity'] ?? '';
    $entryPrice = $_POST['entry_price'] ?? '';
    $fee = $_POST['fee'] ?? '0';
    $currency = sanitize_currency($_POST['currency'] ?? 'USD');

    if ($asset === '' || !is_numeric($quantity) || !is_numeric($entryPrice) || $quantity <= 0 || $entryPrice < 0) {
        redirect_with_flash('error', 'Please provide a valid asset, quantity, and entry price.');
    }

    $quantity = (float)$quantity;
    $entryPrice = (float)$entryPrice;
    $fee = is_numeric($fee) ? (float)$fee : 0;

    $stmt = $conn->prepare("INSERT INTO orders (user_id, asset, side, quantity, entry_price, fee, currency, status, remaining_quantity, created_at, realized_profit) VALUES (?, ?, 'BUY', ?, ?, ?, ?, 'OPEN', ?, NOW(), NULL)");
    if (!$stmt) {
        redirect_with_flash('error', 'Could not prepare insert statement.');
    }

    $stmt->bind_param('isdddsd', $userId, $asset, $quantity, $entryPrice, $fee, $currency, $quantity);
    if ($stmt->execute()) {
        redirect_with_flash('success', 'Order added successfully.');
    }

    redirect_with_flash('error', 'Failed to save order.');
}

if ($action === 'close_order') {
    $orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    $closeQuantity = $_POST['close_quantity'] ?? '';
    $closePrice = $_POST['close_price'] ?? '';
    $closeFee = $_POST['close_fee'] ?? '0';

    if ($orderId <= 0 || !is_numeric($closeQuantity) || !is_numeric($closePrice) || $closeQuantity <= 0 || $closePrice < 0) {
        redirect_with_flash('error', 'Please provide valid closing data.');
    }

    $closeQuantity = (float)$closeQuantity;
    $closePrice = (float)$closePrice;
    $closeFee = is_numeric($closeFee) ? (float)$closeFee : 0;

    // Fetch order to close
    $fetch = $conn->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');
    $fetch->bind_param('ii', $orderId, $userId);
    $fetch->execute();
    $orderResult = $fetch->get_result();
    $order = $orderResult->fetch_assoc();

    if (!$order) {
        redirect_with_flash('error', 'Order not found.');
    }

    if ($order['status'] === 'CLOSED') {
        redirect_with_flash('error', 'Order is already closed.');
    }

    if ($closeQuantity > (float)$order['remaining_quantity']) {
        redirect_with_flash('error', 'Close quantity exceeds remaining amount.');
    }

    $originalCostBasis = ($order['quantity'] * $order['entry_price']) + $order['fee'];
    $allocatedCost = ($closeQuantity / $order['quantity']) * $originalCostBasis;
    $proceeds = ($closeQuantity * $closePrice) - $closeFee;
    $profit = $proceeds - $allocatedCost;

    $orderCurrency = sanitize_currency($order['currency'] ?? 'USD');

    $insertClosure = $conn->prepare('INSERT INTO order_closures (order_id, close_quantity, close_price, currency, fee, profit, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
    if (!$insertClosure) {
        redirect_with_flash('error', 'Could not prepare closure statement.');
    }

    $insertClosure->bind_param('iddsdd', $orderId, $closeQuantity, $closePrice, $orderCurrency, $closeFee, $profit);
    if (!$insertClosure->execute()) {
        redirect_with_flash('error', 'Failed to record closure.');
    }

    // Update order state
    $newRemaining = round((float)$order['remaining_quantity'] - $closeQuantity, 8);
    if ($newRemaining < 0) {
        $newRemaining = 0;
    }

    $status = $newRemaining <= 0 ? 'CLOSED' : 'OPEN';
    $closedAt = $status === 'CLOSED' ? date('Y-m-d H:i:s') : null;
    $realizedProfit = null;

    if ($status === 'CLOSED') {
        $sumStmt = $conn->prepare('SELECT COALESCE(SUM(profit), 0) as total_profit FROM order_closures WHERE order_id = ?');
        $sumStmt->bind_param('i', $orderId);
        $sumStmt->execute();
        $sumResult = $sumStmt->get_result()->fetch_assoc();
        $realizedProfit = $sumResult['total_profit'];

        $update = $conn->prepare('UPDATE orders SET remaining_quantity = ?, status = ?, closed_at = ?, realized_profit = ? WHERE id = ?');
        $update->bind_param('dssdi', $newRemaining, $status, $closedAt, $realizedProfit, $orderId);
    } else {
        $update = $conn->prepare('UPDATE orders SET remaining_quantity = ? WHERE id = ?');
        $update->bind_param('di', $newRemaining, $orderId);
    }

    if ($update->execute()) {
        redirect_with_flash('success', 'Order updated with closing trade.');
    }

    redirect_with_flash('error', 'Failed to update order.');
}

if ($action === 'delete_order') {
    $orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;

    if ($orderId <= 0) {
        redirect_with_flash('error', 'Invalid order ID.');
    }

    $fetch = $conn->prepare('SELECT id FROM orders WHERE id = ? AND user_id = ?');
    if (!$fetch) {
        redirect_with_flash('error', 'Unable to validate order.');
    }
    $fetch->bind_param('ii', $orderId, $userId);
    $fetch->execute();
    $orderResult = $fetch->get_result();

    if (!$orderResult || !$orderResult->fetch_assoc()) {
        redirect_with_flash('error', 'Order not found.');
    }

    $conn->begin_transaction();

    $deleteClosures = $conn->prepare('DELETE oc FROM order_closures oc JOIN orders o ON oc.order_id = o.id WHERE oc.order_id = ? AND o.user_id = ?');
    $deleteOrder = $conn->prepare('DELETE FROM orders WHERE id = ? AND user_id = ?');

    if (!$deleteClosures || !$deleteOrder) {
        $conn->rollback();
        redirect_with_flash('error', 'Unable to prepare delete statements.');
    }

    $deleteClosures->bind_param('ii', $orderId, $userId);
    $deleteOrder->bind_param('ii', $orderId, $userId);

    $closuresOk = $deleteClosures->execute();
    $orderOk = $deleteOrder->execute();

    if ($closuresOk && $orderOk && $deleteOrder->affected_rows > 0) {
        $conn->commit();
        redirect_with_flash('success', 'Order deleted successfully.');
    }

    $conn->rollback();
    redirect_with_flash('error', 'Failed to delete order.');
}

redirect_with_flash('error', 'Unknown action.');
