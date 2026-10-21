<?php
// Older installations may not have applied the order-journal migration.
function ensure_order_journal_schema(mysqli $conn): void
{
    $result = $conn->query('SHOW COLUMNS FROM orders');
    if (!$result) throw new RuntimeException('Could not inspect order schema.');
    $existing = array_column($result->fetch_all(MYSQLI_ASSOC), 'Field');
    $columns = [
        // NULL preserves the created_at fallback for historical orders.
        'purchased_at' => 'DATETIME NULL DEFAULT NULL',
        'strategy' => 'VARCHAR(60) NULL',
        'notes' => 'TEXT NULL',
    ];
    foreach ($columns as $column => $definition) {
        if (in_array($column, $existing, true)) continue;
        try {
            if (!$conn->query("ALTER TABLE orders ADD COLUMN `$column` $definition")) {
                if ($conn->errno !== 1060) throw new RuntimeException('Could not update order schema.');
            }
        } catch (mysqli_sql_exception $error) {
            // Another purchase may have added the same column concurrently.
            if ($error->getCode() !== 1060) throw $error;
        }
    }
}
