<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare('SELECT rolle, borettslag_id FROM users WHERE id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

if (!$admin || $admin['rolle'] !== 'admin') {
    die('Ingen tilgang.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_venteliste.php');
    exit;
}

$venteliste_id = (int)($_POST['venteliste_id'] ?? 0);
$plass_id = (int)($_POST['plass_id'] ?? 0);

if (!$venteliste_id || !$plass_id) {
    $_SESSION['admin_message'] = 'Mangler venteliste- eller plass-ID.';
    header('Location: admin_venteliste.php');
    exit;
}

$stmt = $conn->prepare('SELECT v.*, u.navn, u.epost FROM venteliste v JOIN users u ON v.user_id = u.id WHERE v.id = ?');
$stmt->bind_param('i', $venteliste_id);
$stmt->execute();
$venteliste_entry = $stmt->get_result()->fetch_assoc();

if (!$venteliste_entry) {
    $_SESSION['admin_message'] = 'Ventelisteoppføring ble ikke funnet.';
    header('Location: admin_venteliste.php');
    exit;
}

// Finn plassinformasjon
$stmt = $conn->prepare('SELECT p.*, a.navn AS anlegg_navn FROM plasser p JOIN anlegg a ON p.anlegg_id = a.id WHERE p.id = ?');
$stmt->bind_param('i', $plass_id);
$stmt->execute();
$plass = $stmt->get_result()->fetch_assoc();

if (!$plass || $plass['status'] !== 'ledig') {
    $_SESSION['admin_message'] = 'Plassen er ikke tilgjengelig lenger.';
    header('Location: admin_venteliste.php');
    exit;
}

// Sjekk om det finnes en aktiv kontrakt allerede
$stmt = $conn->prepare('SELECT id, status FROM kontrakter WHERE venteliste_id = ? ORDER BY id DESC LIMIT 1');
$stmt->bind_param('i', $venteliste_id);
$stmt->execute();
$eksisterende = $stmt->get_result()->fetch_assoc();

if ($eksisterende && $eksisterende['status'] !== 'fullfort') {
    $_SESSION['admin_message'] = 'Det finnes allerede et aktivt tilbud for denne personen.';
    header('Location: admin_venteliste.php');
    exit;
}

$stmt = $conn->prepare('INSERT INTO kontrakter (user_id, venteliste_id, plass_id, anlegg_id, status, tilbudt_dato) VALUES (?, ?, ?, ?, "tilbud", NOW())');
$stmt->bind_param('iiii', $venteliste_entry['user_id'], $venteliste_id, $plass_id, $plass['anlegg_id']);
$stmt->execute();
$kontrakt_id = $stmt->insert_id;

$subject = 'Tilbud om parkeringsplass – ' . $plass['anlegg_navn'];
$body = "Hei {$venteliste_entry['navn']},\n\n" .
    "Du har nå fått et tilbud om parkeringsplass {$plass['nummer']} på {$plass['anlegg_navn']}. " .
    "Vennligst signer kontrakten du har mottatt fra borettslaget og send den tilbake ved å svare på denne e-posten.\n\n" .
    "Så snart vi registrerer signert kontrakt vil du få endelig bekreftelse på tildeling.\n\n" .
    "Med vennlig hilsen\n" .
    "{$admin['borettslag_id']} – Parkeringsansvarlig";
$headers = "From: noreply@enkelparkering.test";
$mail_ok = @mail($venteliste_entry['epost'], $subject, $body, $headers);

if ($mail_ok) {
    $_SESSION['admin_message'] = 'Tilbud er sendt til ' . $venteliste_entry['navn'] . '.';
} else {
    $_SESSION['admin_message'] = 'Tilbud ble registrert, men e-post kunne ikke sendes automatisk.';
}

header('Location: admin_venteliste.php');
exit;
