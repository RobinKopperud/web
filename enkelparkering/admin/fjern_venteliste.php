<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
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
if (!$venteliste_id) {
    $_SESSION['admin_message'] = 'Ingen ventelisteoppføring valgt.';
    header('Location: admin_venteliste.php');
    exit;
}

$stmt = $conn->prepare('SELECT v.*, u.navn FROM venteliste v JOIN users u ON v.user_id = u.id WHERE v.id = ? AND v.borettslag_id = ?');
$stmt->bind_param('ii', $venteliste_id, $admin['borettslag_id']);
$stmt->execute();
$venteliste = $stmt->get_result()->fetch_assoc();

if (!$venteliste) {
    $_SESSION['admin_message'] = 'Fant ikke ventelisteoppføringen.';
    header('Location: admin_venteliste.php');
    exit;
}

function slettKontraktFiler(mysqli $conn, int $venteliste_id): void
{
    $stmt = $conn->prepare('SELECT filnavn FROM kontrakter WHERE venteliste_id = ? AND filnavn IS NOT NULL');
    $stmt->bind_param('i', $venteliste_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $dir = __DIR__ . '/../kontrakter/';

    while ($rad = $result->fetch_assoc()) {
        $fil = $dir . $rad['filnavn'];
        if ($rad['filnavn'] && is_file($fil)) {
            @unlink($fil);
        }
    }
}

slettKontraktFiler($conn, $venteliste_id);

$stmt = $conn->prepare('DELETE FROM venteliste WHERE id = ?');
$stmt->bind_param('i', $venteliste_id);
$stmt->execute();

$_SESSION['admin_message'] = 'Ventelisteoppføring for ' . $venteliste['navn'] . ' er slettet.';
header('Location: admin_venteliste.php');
exit;
