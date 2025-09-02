<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['rolle'] !== 'admin') {
    die("Ingen tilgang.");
}

$venteliste_id = (int)$_POST['venteliste_id'];
$plass_id = (int)$_POST['plass_id'];

// Hent ventelisteoppføring
$stmt = $conn->prepare("SELECT * FROM venteliste WHERE id = ?");
$stmt->bind_param("i", $venteliste_id);
$stmt->execute();
$v = $stmt->get_result()->fetch_assoc();

if (!$v) die("Ventelisteoppføring ikke funnet.");

// Oppdater plass → sett til opptatt og knytt til bruker
$stmt = $conn->prepare("UPDATE plasser SET status = 'opptatt', beboer_id = ? WHERE id = ?");
$stmt->bind_param("ii", $v['user_id'], $plass_id);
$stmt->execute();

// Fjern fra venteliste
$stmt = $conn->prepare("DELETE FROM venteliste WHERE id = ?");
$stmt->bind_param("i", $venteliste_id);
$stmt->execute();

header("Location: admin_venteliste.php?msg=tildelt");
exit;
