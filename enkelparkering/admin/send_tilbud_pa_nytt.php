<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';
require_once __DIR__ . '/../lib/PdfGenerator.php';

function kodetEmne(string $subject): string
{
    return '=?UTF-8?B?' . base64_encode($subject) . '?=';
}

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

$kontrakt_id = (int)($_POST['kontrakt_id'] ?? 0);
if (!$kontrakt_id) {
    $_SESSION['admin_message'] = 'Mangler kontrakt-ID.';
    header('Location: admin_venteliste.php');
    exit;
}

$stmt = $conn->prepare('SELECT k.*, v.user_id, u.navn, u.epost, p.nummer AS plass_nummer, a.navn AS anlegg_navn, a.type AS anlegg_type
                        FROM kontrakter k
                        JOIN venteliste v ON v.id = k.venteliste_id
                        JOIN users u ON u.id = v.user_id
                        JOIN plasser p ON p.id = k.plass_id
                        JOIN anlegg a ON a.id = k.anlegg_id
                        WHERE k.id = ? AND v.borettslag_id = ?
                        LIMIT 1');
$stmt->bind_param('ii', $kontrakt_id, $admin['borettslag_id']);
$stmt->execute();
$kontrakt = $stmt->get_result()->fetch_assoc();

if (!$kontrakt) {
    $_SESSION['admin_message'] = 'Fant ikke kontrakten.';
    header('Location: admin_venteliste.php');
    exit;
}

if ($kontrakt['status'] !== 'tilbud') {
    $_SESSION['admin_message'] = 'Tilbud kan kun sendes på nytt når status er "tilbud".';
    header('Location: admin_venteliste.php');
    exit;
}

$depositum = 0;
$maanedspris = 0;
if ($prisStmt = $conn->prepare('SELECT depositum, leiepris FROM plass_priser WHERE type = ? LIMIT 1')) {
    $prisStmt->bind_param('s', $kontrakt['anlegg_type']);
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
        'name' => $kontrakt['navn'],
        'facility' => $kontrakt['anlegg_navn'],
        'place' => $kontrakt['plass_nummer'],
        'type' => $kontrakt['anlegg_type'],
        'deposit' => $depositum,
        'monthly' => $maanedspris,
        'date' => date('d.m.Y')
    ]);
    $pdfAttachment = chunk_split(base64_encode(file_get_contents($pdfPath)));
} catch (Throwable $e) {
    $pdfAttachment = null;
}

$subject = 'Tilbud om parkeringsplass – ' . $kontrakt['anlegg_navn'];
$body = "Hei {$kontrakt['navn']},\r\n\r\n" .
    "Vi sender tilbudet ditt på nytt for parkeringsplass {$kontrakt['plass_nummer']} på {$kontrakt['anlegg_navn']}. " .
    "Vedlagt finner du standardkontrakten med generell informasjon om bruk av plassen, hærverk og betalingsbetingelser.\r\n\r\n" .
    "Signer kontrakten og svar på denne e-posten med signert kopi for å bekrefte plassen.\r\n\r\n" .
    "Med vennlig hilsen\r\n" .
    "Parkeringsansvarlig";

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

    $mail_ok = @mail($kontrakt['epost'], kodetEmne($subject), $message, $headers);
} else {
    $headers = "From: noreply@enkelparkering.test\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "Content-Transfer-Encoding: 8bit";
    $mail_ok = @mail($kontrakt['epost'], kodetEmne($subject), $body, $headers);
}

if ($mail_ok) {
    $stmt = $conn->prepare('UPDATE kontrakter SET tilbudt_dato = NOW() WHERE id = ?');
    $stmt->bind_param('i', $kontrakt_id);
    $stmt->execute();
    $_SESSION['admin_message'] = 'Tilbud sendt på nytt til ' . $kontrakt['navn'] . '.';
} else {
    $_SESSION['admin_message'] = 'Klarte ikke å sende tilbud på nytt automatisk.';
}

header('Location: admin_venteliste.php');
exit;
