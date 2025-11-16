<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare('SELECT rolle FROM users WHERE id = ?');
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
    $_SESSION['admin_message'] = 'Ingen kontrakt valgt.';
    header('Location: admin_venteliste.php');
    exit;
}

$stmt = $conn->prepare('SELECT * FROM kontrakter WHERE id = ?');
$stmt->bind_param('i', $kontrakt_id);
$stmt->execute();
$kontrakt = $stmt->get_result()->fetch_assoc();

if (!$kontrakt || $kontrakt['status'] !== 'tilbud') {
    $_SESSION['admin_message'] = 'Kontrakten kan ikke oppdateres.';
    header('Location: admin_venteliste.php');
    exit;
}

if (!isset($_FILES['signert_kontrakt']) || $_FILES['signert_kontrakt']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['admin_message'] = 'Kunne ikke lese opplastet fil.';
    header('Location: admin_venteliste.php');
    exit;
}

$fil = $_FILES['signert_kontrakt'];
$ext = strtolower(pathinfo($fil['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
    $_SESSION['admin_message'] = 'Bare PDF/JPG/PNG er tillatt.';
    header('Location: admin_venteliste.php');
    exit;
}

$targetDir = __DIR__ . '/../kontrakter';
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

$filnavn = 'kontrakt_' . $kontrakt_id . '_' . time() . '.' . $ext;
$fullPath = $targetDir . '/' . $filnavn;

if (!move_uploaded_file($fil['tmp_name'], $fullPath)) {
    $_SESSION['admin_message'] = 'Kunne ikke lagre filen.';
    header('Location: admin_venteliste.php');
    exit;
}

$stmt = $conn->prepare('UPDATE kontrakter SET status = "signert", signert_dato = NOW(), filnavn = ? WHERE id = ?');
$stmt->bind_param('si', $filnavn, $kontrakt_id);
$stmt->execute();

$_SESSION['admin_message'] = 'Signert kontrakt registrert.';
header('Location: admin_venteliste.php');
exit;
