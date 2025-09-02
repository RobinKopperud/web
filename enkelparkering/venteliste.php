<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$borettslag_id = $_SESSION['borettslag_id']; 
$anlegg_id = !empty($_POST['anlegg_id']) ? (int)$_POST['anlegg_id'] : null;
$onsker_lader = isset($_POST['onsker_lader']) ? 1 : 0;

// Sjekk om bruker allerede står på ventelisten i dette borettslaget
$stmt = $conn->prepare("SELECT id FROM venteliste WHERE user_id = ? AND borettslag_id = ?");
$stmt->bind_param("ii", $user_id, $borettslag_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Allerede på venteliste
    $_SESSION['message'] = "❌ Du står allerede på ventelisten.";
} else {
    // Sett inn ny oppføring
    $stmt = $conn->prepare("
        INSERT INTO venteliste (borettslag_id, user_id, anlegg_id, onsker_lader, registrert)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("iiii", $borettslag_id, $user_id, $anlegg_id, $onsker_lader);
    if ($stmt->execute()) {
        $_SESSION['message'] = "✅ Du er satt på ventelisten.";
    } else {
        $_SESSION['message'] = "❌ Kunne ikke sette deg på ventelisten.";
    }
}

header("Location: index.php");
exit;
