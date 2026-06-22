<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';
require_once __DIR__ . '/auth.php';

ensure_logged_in();
$userId = (int)($_SESSION['user_id'] ?? 0);

function redirect_with_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    header('Location: index.php');
    exit;
}

function clean_currency(string $currency): string
{
    $cleaned = strtoupper(trim($currency));
    $cleaned = preg_replace('/[^A-Z]/', '', $cleaned);
    return $cleaned !== '' ? substr($cleaned, 0, 10) : 'NOK';
}

function clean_category(string $category): string
{
    return in_array($category, ['property', 'crypto', 'cash', 'other'], true) ? $category : 'other';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_flash('error', 'Ugyldig forespørsel.');
}

$action = $_POST['action'] ?? '';

if ($action === 'save_asset') {
    $assetId = (int)($_POST['asset_id'] ?? 0);
    $category = clean_category($_POST['category'] ?? 'other');
    $name = trim($_POST['name'] ?? '');
    $assetType = trim($_POST['asset_type'] ?? '');
    $provider = trim($_POST['provider'] ?? '');
    $grossValue = $_POST['gross_value'] ?? '';
    $loanAmount = $_POST['loan_amount'] ?? '0';
    $ownershipPercent = $_POST['ownership_percent'] ?? '100';
    $currency = clean_currency($_POST['currency'] ?? 'NOK');
    $valuationDate = trim($_POST['valuation_date'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($name === '' || !is_numeric($grossValue) || (float)$grossValue < 0 || !is_numeric($loanAmount) || (float)$loanAmount < 0 || !is_numeric($ownershipPercent)) {
        redirect_with_flash('error', 'Fyll ut navn, verdi, lån og eierandel med gyldige tall.');
    }

    $grossValue = (float)$grossValue;
    $loanAmount = (float)$loanAmount;
    $ownershipPercent = max(0, min(100, (float)$ownershipPercent));
    $assetType = $assetType !== '' ? $assetType : null;
    $provider = $provider !== '' ? $provider : null;
    $notes = $notes !== '' ? $notes : null;
    $valuationDate = $valuationDate !== '' ? $valuationDate : null;

    if ($assetId > 0) {
        $stmt = $conn->prepare('UPDATE assets SET category = ?, asset_type = ?, name = ?, provider = ?, gross_value = ?, loan_amount = ?, ownership_percent = ?, currency = ?, valuation_date = ?, notes = ? WHERE id = ? AND user_id = ?');
        if (!$stmt) {
            redirect_with_flash('error', 'Kunne ikke forberede oppdatering.');
        }
        $stmt->bind_param('ssssdddsssii', $category, $assetType, $name, $provider, $grossValue, $loanAmount, $ownershipPercent, $currency, $valuationDate, $notes, $assetId, $userId);
        $ok = $stmt->execute();
    } else {
        $stmt = $conn->prepare('INSERT INTO assets (user_id, category, asset_type, name, provider, gross_value, loan_amount, ownership_percent, currency, valuation_date, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        if (!$stmt) {
            redirect_with_flash('error', 'Kunne ikke forberede lagring.');
        }
        $stmt->bind_param('issssdddsss', $userId, $category, $assetType, $name, $provider, $grossValue, $loanAmount, $ownershipPercent, $currency, $valuationDate, $notes);
        $ok = $stmt->execute();
    }

    redirect_with_flash($ok ? 'success' : 'error', $ok ? 'Eiendelen ble lagret.' : 'Kunne ikke lagre eiendelen.');
}

if ($action === 'delete_asset') {
    $assetId = (int)($_POST['asset_id'] ?? 0);
    if ($assetId <= 0) {
        redirect_with_flash('error', 'Ugyldig eiendel.');
    }

    $stmt = $conn->prepare('DELETE FROM assets WHERE id = ? AND user_id = ?');
    if (!$stmt) {
        redirect_with_flash('error', 'Kunne ikke forberede sletting.');
    }
    $stmt->bind_param('ii', $assetId, $userId);
    $stmt->execute();
    redirect_with_flash($stmt->affected_rows > 0 ? 'success' : 'error', $stmt->affected_rows > 0 ? 'Eiendelen ble slettet.' : 'Fant ikke eiendelen.');
}

redirect_with_flash('error', 'Ukjent handling.');
