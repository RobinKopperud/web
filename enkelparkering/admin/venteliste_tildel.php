<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

if (!isset($_SESSION['user_id'])) {
    die('Ingen tilgang.');
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare('SELECT rolle FROM users WHERE id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

if (!$admin || $admin['rolle'] !== 'admin') {
    die('Ingen tilgang.');
}

$kontrakt_id = (int)($_POST['kontrakt_id'] ?? 0);
if (!$kontrakt_id) {
    $_SESSION['admin_message'] = 'Fant ikke kontrakten som skulle godkjennes.';
    header('Location: admin_venteliste.php');
    exit;
}

$stmt = $conn->prepare('SELECT k.*, v.user_id, v.id AS venteliste_id FROM kontrakter k JOIN venteliste v ON k.venteliste_id = v.id WHERE k.id = ?');
$stmt->bind_param('i', $kontrakt_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    $_SESSION['admin_message'] = 'Kontrakten eksisterer ikke.';
    header('Location: admin_venteliste.php');
    exit;
}

if ($data['status'] !== 'signert') {
    $_SESSION['admin_message'] = 'Kontrakten må være signert før tildeling.';
    header('Location: admin_venteliste.php');
    exit;
}

$plass_id = $data['plass_id'];

$stmt = $conn->prepare('SELECT status FROM plasser WHERE id = ?');
$stmt->bind_param('i', $plass_id);
$stmt->execute();
$plass = $stmt->get_result()->fetch_assoc();

if (!$plass) {
    $_SESSION['admin_message'] = 'Fant ikke plassen som skulle tildeles.';
    header('Location: admin_venteliste.php');
    exit;
}

// Sett plass til opptatt og knytt til bruker
$stmt = $conn->prepare("UPDATE plasser SET status = 'opptatt', beboer_id = ? WHERE id = ?");
$stmt->bind_param('ii', $data['user_id'], $plass_id);
$stmt->execute();

// Fjern ventelisteoppføring
$stmt = $conn->prepare('DELETE FROM venteliste WHERE id = ?');
$stmt->bind_param('i', $data['venteliste_id']);
$stmt->execute();

// Oppdater kontraktstatus
$stmt = $conn->prepare("UPDATE kontrakter SET status = 'fullfort' WHERE id = ?");
$stmt->bind_param('i', $kontrakt_id);
$stmt->execute();

$_SESSION['admin_message'] = 'Plass er nå endelig tildelt.';
header('Location: admin_venteliste.php');
exit;
