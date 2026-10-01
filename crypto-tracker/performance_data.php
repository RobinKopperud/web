<?php
// Shared, user-scoped sale history for annual return calculations.
function performance_closures($conn, int $userId): ?array
{
    $stmt = $conn->prepare('SELECT oc.* FROM order_closures oc JOIN orders o ON o.id = oc.order_id WHERE o.user_id = ? ORDER BY oc.created_at ASC');
    if (!$stmt) return null;
    $stmt->bind_param('i', $userId);
    if (!$stmt->execute()) return null;
    $result = $stmt->get_result();
    if (!$result) return null;
    $groups = [];
    while ($row = $result->fetch_assoc()) $groups[(int)$row['order_id']][] = $row;
    return $groups;
}

function performance_order_data(array $order, ?array $closures): array
{
    $cost = (float)$order['quantity'] * (float)$order['entry_price'] + (float)$order['fee'];
    $sales = [];
    foreach (($closures ?? []) as $closure) {
        $allocatedCost = (float)$order['quantity'] > 0 ? $cost * (float)$closure['close_quantity'] / (float)$order['quantity'] : 0;
        $date = strtotime($closure['created_at']);
        $sales[] = [
            'date' => $date === false ? null : $date * 1000,
            'quantity' => (float)$closure['close_quantity'],
            'amount' => $allocatedCost + (float)$closure['profit'],
            'profit' => (float)$closure['profit'],
        ];
    }
    $purchasedAt = strtotime($order['purchased_at'] ?? $order['created_at']);
    return [
        'historyAvailable' => $closures !== null,
        'id' => (int)$order['id'], 'asset' => strtoupper($order['asset']),
        'currency' => strtoupper($order['currency'] ?? 'USD'),
        'cost' => $cost, 'quantity' => (float)$order['quantity'],
        'remaining' => (float)$order['remaining_quantity'],
        'purchasedAt' => $purchasedAt === false ? null : $purchasedAt * 1000,
        'closures' => $sales,
    ];
}
