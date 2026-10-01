<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/performance_data.php';

ensure_logged_in();
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

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($orderId <= 0) {
    header('Location: index.php');
    exit;
}

$orderStmt = $conn->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');
$orderStmt->bind_param('ii', $orderId, $userId);
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
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ordre #<?php echo (int)$order['id']; ?> · detaljer</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
    <header>
        <h1>Ordre #<?php echo (int)$order['id']; ?></h1>
        <p class="subtitle"><a href="index.php" class="link">← Tilbake til oversikten</a></p>
    </header>

    <?php $flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); ?>
    <?php if ($flash): ?><div class="alert <?php echo h($flash['type']); ?>"><?php echo h($flash['message']); ?></div><?php endif; ?>

    <section class="card">
        <h2>Investeringsjournal</h2>
        <p class="muted">Ta vare på begrunnelsen, planen og faktisk kjøpsdato.</p>
        <form method="POST" action="actions.php" class="form-grid journal-form">
            <input type="hidden" name="action" value="update_journal">
            <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
            <div class="form-control"><label for="purchased_at">Kjøpt</label><input type="datetime-local" id="purchased_at" name="purchased_at" value="<?php echo h(date('Y-m-d\TH:i', strtotime($order['purchased_at'] ?? $order['created_at']))); ?>" required></div>
            <div class="form-control"><label for="strategy">Strategi</label><input type="text" id="strategy" name="strategy" maxlength="60" value="<?php echo h($order['strategy'] ?? ''); ?>"></div>
            <div class="form-control form-control--wide"><label for="notes">Notat</label><textarea id="notes" name="notes" maxlength="2000" rows="4"><?php echo h($order['notes'] ?? ''); ?></textarea></div>
            <div class="form-actions"><button class="btn primary" type="submit">Lagre journal</button></div>
        </form>
    </section>

    <section class="card" id="orderPerformanceDetail" data-performance="<?php echo h(json_encode(performance_order_data($order, $closures))); ?>">
        <h2>Avkastning og eiertid</h2>
        <div class="order-performance">
            <div><p class="eyebrow">Avkastning totalt</p><p class="mono profit order-total-return">–</p></div>
            <div><p class="eyebrow">Avkastning per år</p><p class="mono profit order-annual-return">–</p></div>
            <div><p class="eyebrow">Eiertid</p><p class="mono order-holding-period">–</p></div>
            <p class="hint order-performance-note" role="status">Venter på beregning …</p>
        </div>
        <p class="hint">Avkastning per år tar hensyn til eiertid og eventuelle delsalg. Annualisert avkastning er ikke en prognose.</p>
    </section>

    <section class="card danger">
        <h2>Slett ordre</h2>
        <p>Bruk dette bare hvis ordren ble opprettet ved en feil. Hele salgshistorikken blir også slettet.</p>
        <form method="POST" action="actions.php" onsubmit="return confirm('Slette ordren og hele historikken? Dette kan ikke angres.');">
            <input type="hidden" name="action" value="delete_order">
            <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
            <button type="submit" class="btn danger">Slett ordre</button>
        </form>
    </section>

    <section class="card">
        <h2>Ordresammendrag</h2>
        <div class="detail-grid">
            <div><strong>Valuta:</strong> <?php echo h($order['asset']); ?></div>
            <div><strong>Status:</strong> <span class="badge <?php echo strtolower($order['status']); ?>"><?php echo $order['status'] === 'OPEN' ? 'Åpen' : 'Lukket'; ?></span></div>
            <div><strong>Antall:</strong> <?php echo formatDecimal($order['quantity']); ?></div>
            <div><strong>Gjenstår:</strong> <?php echo formatDecimal($order['remaining_quantity']); ?></div>
            <div><strong>Kjøpspris:</strong> <?php echo formatDecimal($order['entry_price']); ?> <?php echo h($order['currency'] ?? 'USD'); ?></div>
            <div><strong>Prisvaluta:</strong> <?php echo h($order['currency'] ?? 'USD'); ?></div>
            <div><strong>Gebyr:</strong> <?php echo formatDecimal($order['fee']); ?> <?php echo h($order['currency'] ?? 'USD'); ?></div>
            <div><strong>Kostgrunnlag:</strong> <?php echo formatDecimal(($order['quantity'] * $order['entry_price']) + $order['fee']); ?> <?php echo h($order['currency'] ?? 'USD'); ?></div>
            <div><strong>Realisert resultat:</strong> <?php echo $order['status'] === 'CLOSED' ? formatDecimal($order['realized_profit']) . ' ' . h($order['currency'] ?? 'USD') : '-'; ?></div>
            <div><strong>Opprettet:</strong> <?php echo h($order['created_at']); ?></div>
            <div><strong>Lukket:</strong> <?php echo h($order['closed_at']); ?></div>
        </div>
    </section>

    <section class="card">
        <h2>Salgshistorikk</h2>
        <?php if (empty($closures)): ?>
            <p class="muted">Ingen salg er registrert ennå.</p>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Close quantity</th>
                        <th>Close price</th>
                        <th>Currency</th>
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
                            <td><?php echo h($closure['currency'] ?? $order['currency']); ?></td>
                            <td><?php echo formatDecimal($closure['fee']); ?></td>
                            <td class="profit <?php echo $closure['profit'] >= 0 ? 'positive' : 'negative'; ?>"><?php echo formatDecimal($closure['profit']); ?></td>
                            <td><?php echo h($closure['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <th colspan="5" class="text-right">Total realized profit</th>
                        <th class="profit <?php echo $totalClosureProfit >= 0 ? 'positive' : 'negative'; ?>"><?php echo formatDecimal($totalClosureProfit); ?> <?php echo h($order['currency'] ?? 'USD'); ?></th>
                        <th></th>
                    </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
<script src="assets/performance.js"></script>
<script src="assets/performance-ui.js"></script>
</body>
</html>

