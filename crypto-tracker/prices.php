<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';
require_once __DIR__ . '/auth.php';

ini_set('allow_url_fopen', '1');

ensure_logged_in();
header('Content-Type: application/json');

function sanitize_symbol_part(string $value): string
{
    return strtoupper(preg_replace('/[^A-Z0-9]/', '', $value));
}

function fetch_with_curl(string $url, array $context): array
{
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

    $httpOptions = $context['http'] ?? [];
    if (isset($httpOptions['timeout'])) {
        curl_setopt($curl, CURLOPT_TIMEOUT, (int)$httpOptions['timeout']);
    }

    if (isset($httpOptions['user_agent'])) {
        curl_setopt($curl, CURLOPT_USERAGENT, $httpOptions['user_agent']);
    }

    $body = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $error = $body === false ? curl_error($curl) : null;
    curl_close($curl);

    return [
        'body' => $body === false ? null : $body,
        'status' => $status ?: null,
        'error' => $error,
    ];
}

function fetch_price_from_hosts(string $symbol, array $hosts, array $context, array &$requestLog, array &$responseLog): ?float
{
    foreach ($hosts as $host) {
        $url = $host . '?symbol=' . urlencode($symbol);
        $requestLog[] = $url;
        $response = fetch_with_curl($url, $context);

        $responseLog[] = [
            'url' => $url,
            'status' => $response['status'],
            'body' => $response['body'],
            'error' => $response['error'],
        ];

        if ($response['body'] === null) {
            continue;
        }

        $json = json_decode($response['body'], true);
        if (!is_array($json) || isset($json['code'])) {
            continue;
        }

        $price = $json['price'] ?? null;
        if (is_numeric($price)) {
            return (float)$price;
        }
    }

    return null;
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$assetFilter = sanitize_symbol_part($_GET['asset'] ?? '');
$statusFilter = ($_GET['status'] ?? 'open') === 'all' ? null : 'OPEN';

$query = "SELECT DISTINCT UPPER(asset) AS asset, UPPER(currency) AS currency FROM orders WHERE user_id = ?";
$types = 'i';

if ($assetFilter !== '') {
    $query .= " AND UPPER(asset) = ?";
    $types .= 's';
}

if ($statusFilter === 'OPEN') {
    $query .= " AND status = 'OPEN'";
}

$stmt = $conn->prepare($query);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not load orders']);
    exit;
}

if ($assetFilter !== '') {
    $stmt->bind_param($types, $userId, $assetFilter);
} else {
    $stmt->bind_param($types, $userId);
}

$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    echo json_encode(['prices' => []]);
    exit;
}

$pairs = [];
while ($row = $result->fetch_assoc()) {
    $asset = sanitize_symbol_part($row['asset'] ?? '');
    $currency = sanitize_symbol_part($row['currency'] ?? '');
    if ($asset === '' || $currency === '') {
        continue;
    }
    $pairs[$asset . $currency] = [$asset, $currency];
}

if (empty($pairs)) {
    echo json_encode(['prices' => []]);
    exit;
}

$binanceHosts = [
    'https://api.binance.com/api/v3/ticker/price',
];

$binanceRequests = [];
$binanceResponses = [];

$symbolPrices = [];

$httpOptions = [
    'http' => [
        'timeout' => 6,
        'ignore_errors' => true,
        'user_agent' => 'CryptoTracker/1.0',
    ],
];

$prices = [];

foreach ($pairs as $symbol => [$asset, $currency]) {
    $price = fetch_price_from_hosts($symbol, $binanceHosts, $httpOptions, $binanceRequests, $binanceResponses);

    if ($price === null) {
        continue;
    }

    if (!isset($prices[$asset])) {
        $prices[$asset] = [];
    }

    $prices[$asset][$currency] = $price;
    $symbolPrices[$symbol] = $price;
}

echo json_encode([
    'prices' => $prices,
    'symbol_prices' => $symbolPrices,
    'binance_requests' => array_values(array_unique($binanceRequests)),
    'binance_responses' => $binanceResponses,
    'requested' => array_values(array_unique(array_map(function ($pair) {
        return $pair[0];
    }, array_values($pairs)))),
]);
