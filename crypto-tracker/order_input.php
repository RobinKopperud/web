<?php
// Derive the missing purchase field on the server as well as in the browser.
function resolve_order_input(array $input): ?array
{
    $values = [];
    foreach (['quantity', 'entry_price', 'total_cost'] as $key) {
        $value = trim((string)($input[$key] ?? ''));
        if ($value !== '' && (!is_numeric($value) || !is_finite((float)$value) || (float)$value <= 0)) return null;
        $values[$key] = $value === '' ? null : (float)$value;
    }
    $quantity = $values['quantity'];
    $price = $values['entry_price'];
    $total = $values['total_cost'];
    if ($quantity === null && $price !== null && $total !== null) $quantity = $total / $price;
    if ($price === null && $quantity !== null && $total !== null) $price = $total / $quantity;
    if ($quantity === null || $price === null) return null;
    if (!is_finite($quantity) || !is_finite($price) || $quantity <= 0 || $price <= 0) return null;
    if ($total !== null && abs($quantity * $price - $total) > max(0.0001, $total * 0.000001)) return null;
    return ['quantity' => $quantity, 'entry_price' => $price, 'total_cost' => $quantity * $price];
}
