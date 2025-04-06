<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

$gruppe_id = $_POST['gruppe_id'] ?? null;
$password = $_POST['password'] ?? '';

if ($password !== 'admin' || !$gruppe_id) {
    http_response_code(403);
    exit('Ugyldig forespørsel');
}

// 1. Slett godkjenninger
$conn->query("
    DELETE g FROM BJGodkjenninger g
    JOIN BJTransaksjoner t ON g.transaksjon_id = t.transaksjon_id
    WHERE t.gruppe_id = $gruppe_id
");

// 2. Slett transaksjoner
$conn->query("DELETE FROM BJTransaksjoner WHERE gruppe_id = $gruppe_id");

// 3. Slett spillere
$conn->query("DELETE FROM BJSpillere WHERE gruppe_id = $gruppe_id");

// 4. Slett gruppe
$conn->query("DELETE FROM BJGrupper WHERE gruppe_id = $gruppe_id");

echo "Slettet gruppe $gruppe_id";
