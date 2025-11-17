<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: min_venteliste.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$kontrakt_id = (int)($_POST['kontrakt_id'] ?? 0);
$harBekreftet = isset($_POST['bekreft']);

if (!$kontrakt_id) {
    $_SESSION['message'] = 'Kunne ikke finne kontrakten.';
    header('Location: min_venteliste.php');
    exit;
}

if (!$harBekreftet) {
    $_SESSION['message'] = 'Du må bekrefte at du godkjenner tilbudet.';
    header('Location: min_venteliste.php');
    exit;
}

$stmt = $conn->prepare('SELECT * FROM kontrakter WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $kontrakt_id, $user_id);
$stmt->execute();
$kontrakt = $stmt->get_result()->fetch_assoc();

if (!$kontrakt) {
    $_SESSION['message'] = 'Kontrakten finnes ikke.';
    header('Location: min_venteliste.php');
    exit;
}

if ($kontrakt['status'] !== 'tilbud') {
    $_SESSION['message'] = 'Kontrakten kan ikke oppdateres lenger.';
    header('Location: min_venteliste.php');
    exit;
}

if (!isset($_FILES['signert_kontrakt']) || $_FILES['signert_kontrakt']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['message'] = 'Kunne ikke lese opplastet fil.';
    header('Location: min_venteliste.php');
    exit;
}

$fil = $_FILES['signert_kontrakt'];
$ext = strtolower(pathinfo($fil['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
    $_SESSION['message'] = 'Bare PDF eller bilde (JPG/PNG) er tillatt.';
    header('Location: min_venteliste.php');
    exit;
}

$targetDir = __DIR__ . '/kontrakter';
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

if (!empty($kontrakt['filnavn'])) {
    $existing = $targetDir . '/' . $kontrakt['filnavn'];
    if (is_file($existing)) {
        @unlink($existing);
    }
}

$filnavn = 'kontrakt_' . $kontrakt_id . '_bruker_' . time() . '.' . $ext;
$fullPath = $targetDir . '/' . $filnavn;

if (!move_uploaded_file($fil['tmp_name'], $fullPath)) {
    $_SESSION['message'] = 'Kunne ikke lagre filen.';
    header('Location: min_venteliste.php');
    exit;
}

$stmt = $conn->prepare('UPDATE kontrakter SET status = "signert", signert_dato = NOW(), filnavn = ? WHERE id = ?');
$stmt->bind_param('si', $filnavn, $kontrakt_id);
$stmt->execute();

$_SESSION['message'] = 'Takk! Vi har mottatt signert kontrakt.';
header('Location: min_venteliste.php');
exit;
