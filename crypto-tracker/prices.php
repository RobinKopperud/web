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
$quotesParam = $_GET['quotes'] ?? '';

$symbols = array_filter(array_unique(array_map(function ($symbol) {
    return strtoupper(trim($symbol));
}, explode(',', $assetsParam))));

$quotes = array_filter(array_unique(array_map(function ($quote) {
    return strtoupper(trim($quote));
}, explode(',', $quotesParam))));

$symbols = array_slice($symbols, 0, 15);
$quotes = array_slice($quotes, 0, 10);

if (empty($quotes)) {
    $quotes = ['USD'];
}

if (empty($symbols)) {
    echo json_encode(['prices' => []]);
    exit;
}

$context = stream_context_create([
    'http' => [
        'timeout' => 6,
        'ignore_errors' => true,
    ],
]);

$prices = [];

foreach ($symbols as $symbol) {
    foreach ($quotes as $quote) {
        $pair = urlencode($symbol . '-' . $quote);
        $url = 'https://api.coinbase.com/v2/prices/' . $pair . '/spot';

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            continue;
        }

        $json = json_decode($response, true);
        $amount = $json['data']['amount'] ?? null;

        if (!is_numeric($amount)) {
            continue;
        }

        if (!isset($prices[$symbol])) {
            $prices[$symbol] = [];
        }

        $prices[$symbol][$quote] = (float)$amount;
    }
}

echo json_encode([
    'prices' => $prices,
    'requested' => $symbols,
]);
