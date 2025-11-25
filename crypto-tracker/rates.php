<?php
session_start();
require_once __DIR__ . '/auth.php';

ensure_logged_in();
header('Content-Type: application/json');

$base = strtoupper($_GET['base'] ?? 'USD');
$symbolsParam = $_GET['symbols'] ?? '';

$symbols = array_filter(array_unique(array_map(function ($symbol) {
    return strtoupper(trim($symbol));
}, explode(',', $symbolsParam))));

// Limit to a reasonable number of currencies
$symbols = array_slice($symbols, 0, 20);

if (!in_array($base, $symbols, true)) {
    $symbols[] = $base;
}

$context = stream_context_create([
    'http' => [
        'timeout' => 6,
        'ignore_errors' => true,
        'user_agent' => 'CryptoTracker/1.0',
    ],
]);

// Frankfurter offers free EUR base, so we use USD as base by converting via EUR if needed
$apiBase = 'https://api.frankfurter.app/latest';
$query = http_build_query([
    'from' => 'USD',
    'to' => implode(',', $symbols),
]);

$response = @file_get_contents("{$apiBase}?{$query}", false, $context);

if ($response === false) {
    http_response_code(502);
    echo json_encode(['error' => 'Could not load currency rates']);
    exit;
}

$json = json_decode($response, true);

if (!isset($json['rates']) || !is_array($json['rates'])) {
    http_response_code(502);
    echo json_encode(['error' => 'Invalid currency response']);
    exit;
}

$rates = $json['rates'];
$rates['USD'] = 1.0;

// Normalize to the requested base currency
if ($base !== 'USD') {
    $baseRate = $rates[$base] ?? null;
    if ($baseRate && $baseRate > 0) {
        foreach ($rates as $code => $value) {
            $rates[$code] = $value / $baseRate;
        }
        $rates[$base] = 1.0;
    }
}

echo json_encode([
    'base' => $base,
    'rates' => $rates,
]);
