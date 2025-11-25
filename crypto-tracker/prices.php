<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$pairsParam = $_GET['pairs'] ?? '';

$pairs = array_filter(array_map(function ($pair) {
    $parts = preg_split('/[-:]/', $pair);
    [$asset, $currency] = array_pad($parts, 2, '');
    $asset = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($asset)));
    $currency = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($currency)));
    if ($asset === '' || $currency === '') {
        return null;
    }
    return [$asset, $currency];
}, explode(',', $pairsParam)));

if (empty($pairs)) {
    echo json_encode(['prices' => []]);
    exit;
}

$pairs = array_slice($pairs, 0, 30);

$context = stream_context_create([
    'http' => [
        'timeout' => 6,
        'ignore_errors' => true,
        'user_agent' => 'CryptoTracker/1.0',
    ],
]);

$prices = [];
$binanceHosts = [
    'https://api.binance.com/api/v3/ticker/price?symbol=',
    'https://data-api.binance.vision/api/v3/ticker/price?symbol=',
];

foreach ($pairs as [$symbol, $currency]) {
    $ticker = urlencode(strtoupper($symbol . $currency));
    $amount = null;

    foreach ($binanceHosts as $host) {
        $url = $host . $ticker;
        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            continue;
        }

        $json = json_decode($response, true);

        if (!is_array($json) || isset($json['code'])) {
            continue;
        }

        $price = $json['price'] ?? null;

        if (!is_numeric($price)) {
            continue;
        }

        $amount = (float)$price;
        break;
    }

    if ($amount === null) {
        continue;
    }

    if (!isset($prices[$symbol])) {
        $prices[$symbol] = [];
    }

    $prices[$symbol][$currency] = $amount;
}

echo json_encode([
    'prices' => $prices,
    'requested' => array_unique(array_map(function ($pair) {
        return $pair[0];
    }, $pairs)),
]);
