<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';
require_once __DIR__ . '/../lib/PdfGenerator.php';

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
$stmt = $conn->prepare('SELECT p.*, a.navn AS anlegg_navn, a.type AS anlegg_type FROM plasser p JOIN anlegg a ON p.anlegg_id = a.id WHERE p.id = ?');
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

$depositum = 0;
$maanedspris = 0;
if ($prisStmt = $conn->prepare('SELECT depositum, leiepris FROM plass_priser WHERE type = ? LIMIT 1')) {
    $prisStmt->bind_param('s', $plass['anlegg_type']);
    $prisStmt->execute();
    $pris = $prisStmt->get_result()->fetch_assoc();
    if ($pris) {
        $depositum = (int)$pris['depositum'];
        $maanedspris = (int)$pris['leiepris'];
    }
}

$kontraktDir = __DIR__ . '/../kontrakter';
if (!is_dir($kontraktDir)) {
    mkdir($kontraktDir, 0777, true);
}

$pdfFilnavn = sprintf('tilbud_%d_%s.pdf', $kontrakt_id, date('Ymd_His'));
$pdfPath = $kontraktDir . '/' . $pdfFilnavn;

try {
    PdfGenerator::createContractOffer($pdfPath, [
        'name' => $venteliste_entry['navn'],
        'facility' => $plass['anlegg_navn'],
        'place' => $plass['nummer'],
        'type' => $plass['anlegg_type'],
        'deposit' => $depositum,
        'monthly' => $maanedspris,
        'date' => date('d.m.Y')
    ]);
    $pdfAttachment = chunk_split(base64_encode(file_get_contents($pdfPath)));
} catch (Throwable $e) {
    $pdfAttachment = null;
}

$subject = 'Tilbud om parkeringsplass – ' . $plass['anlegg_navn'];
$body = "Hei {$venteliste_entry['navn']},\r\n\r\n" .
    "Du har nå fått et tilbud om parkeringsplass {$plass['nummer']} på {$plass['anlegg_navn']}. " .
    "Vedlagt finner du standardkontrakten med generell informasjon om bruk av plassen, hærverk og betalingsbetingelser.\r\n\r\n" .
    "Signer kontrakten og svar på denne e-posten med signert kopi for å bekrefte plassen.\r\n\r\n" .
    "Med vennlig hilsen\r\n" .
    "{$admin['borettslag_id']} – Parkeringsansvarlig";

if ($pdfAttachment) {
    $boundary = '=_Part_' . md5((string)microtime(true));
    $headers = "From: noreply@enkelparkering.test\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"";

    $message = "--{$boundary}\r\n";
    $message .= "Content-Type: text/plain; charset=\"utf-8\"\r\n";
    $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $message .= $body . "\r\n\r\n";
    $message .= "--{$boundary}\r\n";
    $message .= "Content-Type: application/pdf; name=\"kontraktstilbud.pdf\"\r\n";
    $message .= "Content-Transfer-Encoding: base64\r\n";
    $message .= "Content-Disposition: attachment; filename=\"kontraktstilbud.pdf\"\r\n\r\n";
    $message .= $pdfAttachment . "\r\n";
    $message .= "--{$boundary}--";

    $mail_ok = @mail($venteliste_entry['epost'], $subject, $message, $headers);
} else {
    $headers = "From: noreply@enkelparkering.test";
    $mail_ok = @mail($venteliste_entry['epost'], $subject, $body, $headers);
}

if ($mail_ok) {
    $_SESSION['admin_message'] = 'Tilbud er sendt til ' . $venteliste_entry['navn'] . '.';
} else {
    $_SESSION['admin_message'] = 'Tilbud ble registrert, men e-post kunne ikke sendes automatisk.';
}

header('Location: admin_venteliste.php');
exit;
