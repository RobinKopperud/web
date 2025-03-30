<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

$spiller_id  = $_POST['spiller_id'];
$gruppekode  = $_POST['gruppekode'];

// Slett alle ventende forslag for denne spilleren
$stmt = $conn->prepare("DELETE FROM BJTransaksjoner WHERE maal_spiller_id = ? AND status = 'ventende'");
$stmt->bind_param("i", $spiller_id);
$stmt->execute();
$stmt->close();

$conn->close();

// Send tilbake til bordet
header("Location: /Web/blackjacj/view/table.php?gruppekode=$gruppekode&spiller_id=$spiller_id");
exit;
?>
