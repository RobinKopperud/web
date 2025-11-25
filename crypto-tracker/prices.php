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

    if ($currency === '') {
        // Allow already-concatenated symbols like BTCUSDT
        $asset = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($asset)));
        $quotes = ['USDT', 'USDC', 'BUSD', 'FDUSD', 'TUSD', 'DAI', 'EUR', 'USD', 'GBP', 'AUD', 'CAD'];
        foreach ($quotes as $quote) {
            if (str_ends_with($asset, $quote)) {
                $currency = $quote;
                $asset = substr($asset, 0, -strlen($quote));
                break;
            }
        }
    }

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

// Build the list of Binance symbols in the accepted array format
$symbols = [];
foreach ($pairs as [$asset, $currency]) {
    $symbols[] = $asset . $currency;
}

if (empty($symbols)) {
    echo json_encode(['prices' => []]);
    exit;
}

$symbols = array_values(array_unique($symbols));

$binanceHosts = [
    'https://api.binance.com/api/v3/ticker/price',
    'https://data-api.binance.vision/api/v3/ticker/price',
];

$symbolLookup = [];
foreach ($pairs as [$asset, $currency]) {
    $symbolLookup[$asset . $currency] = [$asset, $currency];
}

foreach ($binanceHosts as $host) {
    $url = $host . '?symbols=' . urlencode(json_encode($symbols));
    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        continue;
    }

    $json = json_decode($response, true);

    if (!is_array($json) || isset($json['code'])) {
        continue;
    }

    foreach ($json as $entry) {
        $symbolKey = $entry['symbol'] ?? '';
        $price = $entry['price'] ?? null;

        if (!isset($symbolLookup[$symbolKey]) || !is_numeric($price)) {
            continue;
        }

        [$asset, $currency] = $symbolLookup[$symbolKey];
        if (!isset($prices[$asset])) {
            $prices[$asset] = [];
        }

        $prices[$asset][$currency] = (float)$price;
    }

    if (!empty($prices)) {
        break;
    }
}

echo json_encode([
    'prices' => $prices,
    'requested' => array_unique(array_map(function ($pair) {
        return $pair[0];
    }, $pairs)),
]);
