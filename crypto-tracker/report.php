<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';
require_once __DIR__ . '/auth.php';

ensure_logged_in();
$userId = (int)($_SESSION['user_id'] ?? 0);
$currentUser = fetch_current_user($conn);

function formatDecimal($number)
{
    return number_format((float)$number, 8, '.', '');
}

$orders = [];
$ordersStmt = $conn->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
if ($ordersStmt) {
    $ordersStmt->bind_param('i', $userId);
    $ordersStmt->execute();
    $orders = $ordersStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$totalInvested = 0.0;
$openCostBasis = 0.0;
$openOrders = 0;
$closedOrders = 0;

foreach ($orders as $order) {
    $cost = ($order['quantity'] * $order['entry_price']) + $order['fee'];
    $totalInvested += $cost;
    if ($order['remaining_quantity'] > 0) {
        $allocation = ($order['remaining_quantity'] / $order['quantity']);
        $openCostBasis += $cost * $allocation;
        $openOrders++;
    } else {
        $closedOrders++;
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

$format = strtolower($_GET['format'] ?? 'csv');

if ($format === 'pdf') {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename=portfolio_report.pdf');

    $lines = [
        'Portfolio report',
        'Generated: ' . date('Y-m-d H:i'),
        'User: ' . ($currentUser['navn'] ?? $currentUser['epost'] ?? ('User #' . $userId)),
        'Total invested: ' . formatDecimal($totalInvested) . ' USD',
        'Realized P/L: ' . formatDecimal($realizedProfit) . ' USD',
        'Open cost basis: ' . formatDecimal($openCostBasis) . ' USD',
        '30d realized P/L: ' . formatDecimal($last30dProfit) . ' USD',
        sprintf('Open orders: %d · Closed orders: %d', $openOrders, $closedOrders),
        '--- Recent orders ---',
    ];

    foreach (array_slice($orders, 0, 12) as $order) {
        $lines[] = sprintf(
            '#%d %s %s @ %s %s · remaining %s',
            $order['id'],
            strtoupper($order['asset']),
            formatDecimal($order['quantity']),
            formatDecimal($order['entry_price']),
            $order['currency'] ?? 'USD',
            formatDecimal($order['remaining_quantity'])
        );
    }

    echo generateSimplePdf($lines);
    exit;
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename=portfolio_report.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Asset', 'Status', 'Quantity', 'Entry price', 'Currency', 'Remaining qty', 'Fee', 'Created', 'Closed at', 'Realized profit']);

foreach ($orders as $order) {
    fputcsv($output, [
        $order['id'],
        $order['asset'],
        $order['status'],
        formatDecimal($order['quantity']),
        formatDecimal($order['entry_price']),
        $order['currency'] ?? 'USD',
        formatDecimal($order['remaining_quantity']),
        formatDecimal($order['fee']),
        $order['created_at'],
        $order['closed_at'],
        $order['realized_profit'],
    ]);
}

fputcsv($output, []);
fputcsv($output, ['Totals', '', '', '', '', '', '']);
fputcsv($output, ['Total invested', formatDecimal($totalInvested)]);
fputcsv($output, ['Realized P/L', formatDecimal($realizedProfit)]);
fputcsv($output, ['Open cost basis', formatDecimal($openCostBasis)]);
fputcsv($output, ['30d realized P/L', formatDecimal($last30dProfit)]);

fclose($output);
exit;

function generateSimplePdf(array $lines): string
{
    $objects = [];

    $escape = static function ($text) {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    };

    $content = "BT\n/F1 12 Tf\n14 TL\n72 740 Td\n";
    foreach ($lines as $line) {
        $content .= '(' . $escape($line) . ') Tj T*\n';
    }
    $content .= "ET";

    $objects[] = "1 0 obj<< /Type /Catalog /Pages 2 0 R >>endobj";
    $objects[] = "2 0 obj<< /Type /Pages /Count 1 /Kids [3 0 R] >>endobj";
    $objects[] = "3 0 obj<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>endobj";
    $objects[] = "5 0 obj<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>endobj";
    $objects[] = "4 0 obj<< /Length " . strlen($content) . " >>stream\n$content\nendstream endobj";

    $pdf = "%PDF-1.4\n";
    $offsets = [];
    foreach ($objects as $object) {
        $offsets[] = strlen($pdf);
        $pdf .= $object . "\n";
    }

    $xrefPosition = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
    foreach ($offsets as $offset) {
        $pdf .= sprintf("%010d 00000 n \n", $offset);
    }
    $pdf .= "trailer<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xrefPosition . "\n%%EOF";

    return $pdf;
}
