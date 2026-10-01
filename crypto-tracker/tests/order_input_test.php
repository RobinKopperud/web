<?php
require_once __DIR__ . '/../order_input.php';
function check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
foreach ([
    ['quantity' => '2', 'entry_price' => '10'],
    ['quantity' => '2', 'total_cost' => '20'],
    ['entry_price' => '10', 'total_cost' => '20'],
] as $input) {
    $result = resolve_order_input($input);
    check($result !== null && $result['quantity'] === 2.0 && $result['entry_price'] === 10.0 && $result['total_cost'] === 20.0, 'Every purchase input pair must work without JavaScript.');
}
check(resolve_order_input(['quantity' => '2']) === null, 'One field cannot define a purchase.');
check(resolve_order_input(['quantity' => '0', 'total_cost' => '20']) === null, 'Zero quantity is invalid.');
check(resolve_order_input(['quantity' => '2', 'entry_price' => '10', 'total_cost' => '30']) === null, 'Inconsistent input must not silently save.');
echo "Purchase input checks passed.\n";
