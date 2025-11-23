<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

date_default_timezone_set('UTC');

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function formatDecimal($number)
{
    return number_format((float)$number, 8, '.', '');
}

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($orderId <= 0) {
    header('Location: index.php');
    exit;
}

$orderStmt = $conn->prepare('SELECT * FROM orders WHERE id = ?');
$orderStmt->bind_param('i', $orderId);
$orderStmt->execute();
$orderResult = $orderStmt->get_result();
$order = $orderResult->fetch_assoc();

if (!$order) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Order not found.'];
    header('Location: index.php');
    exit;
}

$closuresStmt = $conn->prepare('SELECT * FROM order_closures WHERE order_id = ? ORDER BY created_at ASC');
$closuresStmt->bind_param('i', $orderId);
$closuresStmt->execute();
$closuresResult = $closuresStmt->get_result();
$closures = $closuresResult->fetch_all(MYSQLI_ASSOC);

$totalClosureProfit = 0;
foreach ($closures as $closure) {
    $totalClosureProfit += $closure['profit'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo (int)$order['id']; ?> details</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
    <header>
        <h1>Order #<?php echo (int)$order['id']; ?> details</h1>
        <p class="subtitle"><a href="index.php" class="link">← Back to list</a></p>
    </header>

    <section class="card">
        <h2>Order summary</h2>
        <div class="detail-grid">
            <div><strong>Asset:</strong> <?php echo h($order['asset']); ?></div>
            <div><strong>Status:</strong> <span class="badge <?php echo strtolower($order['status']); ?>"><?php echo h($order['status']); ?></span></div>
            <div><strong>Quantity:</strong> <?php echo formatDecimal($order['quantity']); ?></div>
            <div><strong>Remaining:</strong> <?php echo formatDecimal($order['remaining_quantity']); ?></div>
            <div><strong>Entry price:</strong> <?php echo formatDecimal($order['entry_price']); ?></div>
            <div><strong>Fee:</strong> <?php echo formatDecimal($order['fee']); ?></div>
            <div><strong>Total cost basis:</strong> <?php echo formatDecimal(($order['quantity'] * $order['entry_price']) + $order['fee']); ?></div>
            <div><strong>Realized profit:</strong> <?php echo $order['status'] === 'CLOSED' ? formatDecimal($order['realized_profit']) : '-'; ?></div>
            <div><strong>Created at:</strong> <?php echo h($order['created_at']); ?></div>
            <div><strong>Closed at:</strong> <?php echo h($order['closed_at']); ?></div>
        </div>
    </section>

    <section class="card">
        <h2>Closure history</h2>
        <?php if (empty($closures)): ?>
            <p class="muted">No closures recorded yet.</p>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Close quantity</th>
                        <th>Close price</th>
                        <th>Fee</th>
                        <th>Profit</th>
                        <th>Date</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($closures as $closure): ?>
                        <tr>
                            <td>#<?php echo (int)$closure['id']; ?></td>
                            <td><?php echo formatDecimal($closure['close_quantity']); ?></td>
                            <td><?php echo formatDecimal($closure['close_price']); ?></td>
                            <td><?php echo formatDecimal($closure['fee']); ?></td>
                            <td class="profit <?php echo $closure['profit'] >= 0 ? 'positive' : 'negative'; ?>"><?php echo formatDecimal($closure['profit']); ?></td>
                            <td><?php echo h($closure['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <th colspan="4" class="text-right">Total realized profit</th>
                        <th colspan="2" class="profit <?php echo $totalClosureProfit >= 0 ? 'positive' : 'negative'; ?>"><?php echo formatDecimal($totalClosureProfit); ?></th>
                    </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
</body>
</html>
