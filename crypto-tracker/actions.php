<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/order_input.php';

ensure_logged_in();
$userId = (int)($_SESSION['user_id'] ?? 0);

date_default_timezone_set('UTC');

function sanitize_currency(string $currency): string
{
    $cleaned = strtoupper(trim($currency));
    $cleaned = preg_replace('/[^A-Z0-9]/', '', $cleaned);

    $allowed = ['USD', 'EUR', 'USDC'];
    if (in_array($cleaned, $allowed, true)) {
        return $cleaned;
    }

    return '';
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
    $purchase = resolve_order_input($_POST);
    if ($purchase === null) redirect_with_flash('error', 'Fyll inn to av antall, pris per enhet og totalbeløp. Verdiene må stemme overens.');
    $quantity = $purchase['quantity'];
    $entryPrice = $purchase['entry_price'];
    $fee = $_POST['fee'] ?? '0';
    $currency = sanitize_currency($_POST['currency'] ?? 'USD');
    $purchasedAtInput = trim($_POST['purchased_at'] ?? '');
    $strategy = trim($_POST['strategy'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $purchasedTimestamp = $purchasedAtInput !== '' ? strtotime($purchasedAtInput) : time();

    if ($currency === '') {
        redirect_with_flash('error', 'Prisvaluta må være USD, EUR eller USDC.');
    }

    if ($asset === '' || !is_numeric($quantity) || !is_numeric($entryPrice) || $quantity <= 0 || $entryPrice < 0) {
        redirect_with_flash('error', 'Fyll inn gyldig kryptovaluta, antall og kjøpspris.');
    }
    if ($purchasedTimestamp === false || strlen($strategy) > 120 || strlen($notes) > 8000) {
        redirect_with_flash('error', 'Kontroller kjøpsdato, strategi og notat.');
    }

    $quantity = (float)$quantity;
    $entryPrice = (float)$entryPrice;
    $fee = is_numeric($fee) ? (float)$fee : 0;
    $purchasedAt = date('Y-m-d H:i:s', $purchasedTimestamp);
    $strategy = $strategy !== '' ? $strategy : null;
    $notes = $notes !== '' ? $notes : null;

    $stmt = $conn->prepare("INSERT INTO orders (user_id, asset, side, quantity, entry_price, fee, currency, status, remaining_quantity, created_at, realized_profit, purchased_at, strategy, notes) VALUES (?, ?, 'BUY', ?, ?, ?, ?, 'OPEN', ?, NOW(), NULL, ?, ?, ?)");
    if (!$stmt) {
        redirect_with_flash('error', 'Kunne ikke klargjøre lagringen.');
    }

    $stmt->bind_param('isdddsdsss', $userId, $asset, $quantity, $entryPrice, $fee, $currency, $quantity, $purchasedAt, $strategy, $notes);
    if ($stmt->execute()) {
        redirect_with_flash('success', 'Kjøpet ble lagret.');
    }

    redirect_with_flash('error', 'Kunne ikke lagre kjøpet.');
}

if ($action === 'update_journal') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $strategy = trim($_POST['strategy'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $purchasedAtInput = trim($_POST['purchased_at'] ?? '');
    $purchasedTimestamp = strtotime($purchasedAtInput);
    if ($orderId <= 0 || $purchasedTimestamp === false || strlen($strategy) > 120 || strlen($notes) > 8000) {
        redirect_with_flash('error', 'Kontroller journalfeltene.', 'order_detail.php?id=' . $orderId);
    }
    $purchasedAt = date('Y-m-d H:i:s', $purchasedTimestamp);
    $strategy = $strategy !== '' ? $strategy : null;
    $notes = $notes !== '' ? $notes : null;
    $stmt = $conn->prepare('UPDATE orders SET purchased_at = ?, strategy = ?, notes = ? WHERE id = ? AND user_id = ?');
    if (!$stmt) {
        redirect_with_flash('error', 'Kunne ikke oppdatere journalen.', 'order_detail.php?id=' . $orderId);
    }
    $stmt->bind_param('sssii', $purchasedAt, $strategy, $notes, $orderId, $userId);
    $stmt->execute();
    redirect_with_flash($stmt->affected_rows >= 0 ? 'success' : 'error', $stmt->affected_rows >= 0 ? 'Journalen ble oppdatert.' : 'Kunne ikke oppdatere journalen.', 'order_detail.php?id=' . $orderId);
}

if ($action === 'close_order') {
    $orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    $closePrice = $_POST['close_price'] ?? '';
    $closeFee = $_POST['close_fee'] ?? '0';

    if ($orderId <= 0 || !is_numeric($closePrice) || !is_finite((float)$closePrice) || $closePrice < 0) {
        redirect_with_flash('error', 'Fyll inn gyldig antall og salgspris.');
    }

    $closePrice = (float)$closePrice;
    $closeFee = is_numeric($closeFee) ? (float)$closeFee : 0;

    $conn->begin_transaction();
    // Lock the order so a duplicate submission cannot sell it twice.
    $fetch = $conn->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ? FOR UPDATE');
    $fetch->bind_param('ii', $orderId, $userId);
    $fetch->execute();
    $orderResult = $fetch->get_result();
    $order = $orderResult->fetch_assoc();

    if (!$order) {
        redirect_with_flash('error', 'Fant ikke ordren.');
    }

    if ($order['status'] === 'CLOSED') {
        redirect_with_flash('error', 'Ordren er allerede lukket.');
    }

    if (abs((float)$order['remaining_quantity'] - (float)$order['quantity']) > 0.00000001) {
        $conn->rollback();
        redirect_with_flash('error', 'Ordren har inkonsistent mengde. Korriger ordren før salg.');
    }
    $closeQuantity = (float)$order['quantity'];
    $cost = $closeQuantity * (float)$order['entry_price'] + (float)$order['fee'];
    $proceeds = $closeQuantity * $closePrice - $closeFee;
    $profit = $proceeds - $cost;

    $orderCurrency = sanitize_currency($order['currency'] ?? 'USD');

    if ($orderCurrency === '') {
        redirect_with_flash('error', 'Unsupported currency on order.');
    }

    $insertClosure = $conn->prepare('INSERT INTO order_closures (order_id, close_quantity, close_price, currency, fee, profit, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
    if (!$insertClosure) {
        redirect_with_flash('error', 'Could not prepare closure statement.');
    }

    $insertClosure->bind_param('iddsdd', $orderId, $closeQuantity, $closePrice, $orderCurrency, $closeFee, $profit);
    if (!$insertClosure->execute()) {
        redirect_with_flash('error', 'Failed to record closure.');
    }

    $update = $conn->prepare("UPDATE orders SET remaining_quantity = 0, status = 'CLOSED', closed_at = NOW(), realized_profit = ? WHERE id = ? AND user_id = ?");
    if (!$update) {
        $conn->rollback();
        redirect_with_flash('error', 'Kunne ikke lagre salget.');
    }
    $update->bind_param('dii', $profit, $orderId, $userId);
    if ($update->execute()) {
        $conn->commit();
        redirect_with_flash('success', 'Hele ordren ble solgt.');
    }
    $conn->rollback();
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

