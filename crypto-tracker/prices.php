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

$assetsParam = $_GET['assets'] ?? '';
$currenciesParam = $_GET['currencies'] ?? ($_GET['quotes'] ?? '');

$symbols = array_filter(array_unique(array_map(function ($symbol) {
    return strtoupper(trim($symbol));
}, explode(',', $assetsParam))));

$currencies = array_filter(array_unique(array_map(function ($currency) {
    return strtoupper(trim($currency));
}, explode(',', $currenciesParam))));

// Restrict currencies to those Binance commonly supports to avoid repeated feed errors
$allowedCurrencies = ['USD', 'USDT', 'BUSD', 'EUR', 'GBP'];
$currencies = array_values(array_intersect($currencies, $allowedCurrencies));

// Always include at least USD as a base so we have a reliable feed to work with
if (!in_array('USD', $currencies, true)) {
    array_unshift($currencies, 'USD');
}

$symbols = array_slice($symbols, 0, 15);
$currencies = array_slice($currencies, 0, 10);

if (empty($symbols)) {
    echo json_encode(['prices' => []]);
    exit;
}

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

foreach ($symbols as $symbol) {
    foreach ($currencies as $currency) {
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
}

echo json_encode([
    'prices' => $prices,
    'requested' => $symbols,
]);
