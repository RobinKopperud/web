<?php
// Integration test: run against a disposable database named crypto_order_test.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = new mysqli(getenv('MYSQL_HOST') ?: '127.0.0.1', 'root', 'crypto-test');
$conn->query('CREATE DATABASE IF NOT EXISTS crypto_order_test');
$conn->select_db('crypto_order_test');
$conn->query('DROP TABLE IF EXISTS orders');
$conn->query("CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, asset VARCHAR(20) NOT NULL,
    side VARCHAR(4), quantity DECIMAL(18,8), entry_price DECIMAL(18,8), fee DECIMAL(18,8),
    currency VARCHAR(10), status VARCHAR(10), remaining_quantity DECIMAL(18,8),
    created_at DATETIME, realized_profit DECIMAL(18,8) NULL
)");
$conn->query("INSERT INTO orders (user_id, asset, quantity, created_at) VALUES (1, 'ETH', 2, '2020-01-01 00:00:00')");

$conn->query("CREATE USER IF NOT EXISTS 'crypto_no_alter'@'%' IDENTIFIED BY 'crypto-test'");
$conn->query('GRANT SELECT, INSERT ON crypto_order_test.* TO crypto_no_alter');

$fixture = sys_get_temp_dir() . '/crypto-order-' . bin2hex(random_bytes(8));
mkdir($fixture);
file_put_contents($fixture . '/db.php', '<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = new mysqli(getenv("MYSQL_HOST") ?: "127.0.0.1", "crypto_no_alter", "crypto-test", "crypto_order_test");
$_SESSION["user_id"] = 1;
');

function submit_purchase(string $fixture, string $asset): array
{
    $sessionId = 'crypto-test-' . bin2hex(random_bytes(8));
    $post = ['action' => 'create_order', 'asset' => $asset, 'quantity' => '0.25',
        'entry_price' => '40000', 'fee' => '5', 'currency' => 'USD',
        'purchased_at' => '2024-01-02T12:30', 'strategy' => 'Sparing', 'notes' => 'Testnotat'];
    $code = 'session_id(' . var_export($sessionId, true) . ');'
        . '$_SERVER["DOCUMENT_ROOT"] = ' . var_export($fixture, true) . ';'
        . '$_SERVER["REQUEST_METHOD"] = "POST";'
        . '$_POST = ' . var_export($post, true) . ';'
        . 'require ' . var_export(dirname(__DIR__) . '/actions.php', true) . ';';
    exec(escapeshellarg(PHP_BINARY) . ' -d display_errors=stderr -r ' . escapeshellarg($code) . ' 2>&1', $output, $status);
    session_id($sessionId);
    session_start();
    $flash = $_SESSION['flash'] ?? [];
    session_destroy();
    return [$status, implode("\n", $output), $flash];
}

function check(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

try {
    $columns = $conn->query('SHOW COLUMNS FROM orders')->fetch_all(MYSQLI_ASSOC);
    $legacy = $conn->query('SELECT * FROM orders WHERE id = 1')->fetch_assoc();
    [$status, $output, $flash] = submit_purchase($fixture, 'BTC');
    check($status === 0 && ($flash['type'] ?? '') === 'success', 'Legacy purchase failed without ALTER permission: ' . $output);
    check($conn->query('SHOW COLUMNS FROM orders')->fetch_all(MYSQLI_ASSOC) === $columns, 'Purchase changed schema.');
    check($conn->query('SELECT * FROM orders WHERE id = 1')->fetch_assoc() === $legacy, 'Existing order changed.');
    $order = $conn->query("SELECT * FROM orders WHERE asset = 'BTC'")->fetch_assoc();
    check($order !== null && (float)$order['quantity'] === 0.25 && (float)$order['entry_price'] === 40000.0, 'Purchase values lost.');
    check($order['created_at'] === '2024-01-02 12:30:00', 'Purchase date lost.');
    check(str_contains($flash['message'], 'strategi') && str_contains($flash['message'], 'notat'), 'Unsupported fields silently discarded.');

    // Schema changes below are restricted to this disposable test fixture.
    $conn->query('ALTER TABLE orders ADD purchased_at DATETIME NULL, ADD strategy VARCHAR(60) NULL, ADD notes TEXT NULL');
    $columns = $conn->query('SHOW COLUMNS FROM orders')->fetch_all(MYSQLI_ASSOC);
    [$status, $output, $flash] = submit_purchase($fixture, 'SOL');
    check($status === 0 && ($flash['type'] ?? '') === 'success', 'Current schema purchase failed: ' . $output);
    $order = $conn->query("SELECT * FROM orders WHERE asset = 'SOL'")->fetch_assoc();
    check($order['purchased_at'] === '2024-01-02 12:30:00' && $order['strategy'] === 'Sparing' && $order['notes'] === 'Testnotat', 'Journal values lost.');
    check($flash['message'] === 'Kjøpet ble lagret.', 'Incorrect warning.');
    check($conn->query('SHOW COLUMNS FROM orders')->fetch_all(MYSQLI_ASSOC) === $columns, 'Current schema changed.');

    [$status, $output, $flash] = submit_purchase($fixture, str_repeat('X', 21));
    check($status === 0 && ($flash['type'] ?? '') === 'error', 'Database error caused blank page: ' . $output);
    check((int)$conn->query('SELECT COUNT(*) FROM orders')->fetch_row()[0] === 3, 'Failed purchase changed orders.');

    $conn->query('ALTER TABLE orders DROP COLUMN notes');
    $columns = $conn->query('SHOW COLUMNS FROM orders')->fetch_all(MYSQLI_ASSOC);
    [$status, $output, $flash] = submit_purchase($fixture, 'ADA');
    check($status === 0 && ($flash['type'] ?? '') === 'success', 'Partial schema purchase failed: ' . $output);
    $order = $conn->query("SELECT * FROM orders WHERE asset = 'ADA'")->fetch_assoc();
    check($order['strategy'] === 'Sparing' && $order['purchased_at'] === '2024-01-02 12:30:00', 'Supported journal values lost.');
    check(str_contains($flash['message'], 'notat') && !str_contains($flash['message'], 'strategi'), 'Wrong unsupported fields reported.');
    check($conn->query('SHOW COLUMNS FROM orders')->fetch_all(MYSQLI_ASSOC) === $columns, 'Partial schema changed.');
    check((int)$conn->query('SELECT COUNT(*) FROM orders')->fetch_row()[0] === 4, 'Purchase duplicated or lost.');
    echo "Order storage integration tests passed.\n";
} finally {
    unlink($fixture . '/db.php');
    rmdir($fixture);
}
