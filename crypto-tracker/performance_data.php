<?php
// Performance is based on whole orders; no separate sale-history lookup is needed.
function performance_order_data(array $order): array
{
    $purchasedAt = strtotime($order['purchased_at'] ?? $order['created_at']);
    $soldAt = !empty($order['closed_at']) ? strtotime($order['closed_at']) : false;
    return [
        'id' => (int)$order['id'], 'asset' => strtoupper($order['asset']),
        'currency' => strtoupper($order['currency'] ?? 'USD'),
        'cost' => (float)$order['quantity'] * (float)$order['entry_price'] + (float)$order['fee'],
        'validQuantity' => $order['status'] === 'CLOSED' ? (float)$order['remaining_quantity'] === 0.0 : abs((float)$order['remaining_quantity'] - (float)$order['quantity']) <= 0.00000001,
        'quantity' => (float)$order['quantity'], 'status' => $order['status'],
        'purchasedAt' => $purchasedAt === false ? null : $purchasedAt * 1000,
        'soldAt' => $soldAt === false ? null : $soldAt * 1000,
        'realizedProfit' => isset($order['realized_profit']) ? (float)$order['realized_profit'] : null,
    ];
}
