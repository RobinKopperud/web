<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$borettslag_id = $_SESSION['borettslag_id']; // hvis du lagrer dette i session
$anlegg_id = $_POST['anlegg_id'];
$ønsker_lader = isset($_POST['ønsker_lader']) ? 1 : 0;

// Sjekk om bruker allerede står på ventelisten for dette anlegget
$stmt = $conn->prepare("SELECT id FROM venteliste WHERE user_id = ? AND anlegg_id = ?");
$stmt->bind_param("ii", $user_id, $anlegg_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $stmt = $conn->prepare("INSERT INTO venteliste (borettslag_id, user_id, anlegg_id, ønsker_lader) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiii", $borettslag_id, $user_id, $anlegg_id, $ønsker_lader);
    $stmt->execute();
}

header("Location: index.php");
exit;
?>
