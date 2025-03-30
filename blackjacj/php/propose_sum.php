<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

$gruppe_id  = $_POST['gruppe_id'];
$spiller_id = $_POST['spiller_id'];
$belop      = $_POST['belop'];

// Slett evt gamle ventende forslag for deg selv
$conn->query("DELETE FROM BJTransaksjoner WHERE maal_spiller_id = $spiller_id AND status = 'ventende'");

$stmt = $conn->prepare("INSERT INTO BJTransaksjoner (gruppe_id, maal_spiller_id, belop) VALUES (?, ?, ?)");
$stmt->bind_param("iid", $gruppe_id, $spiller_id, $belop);
$stmt->execute();
$stmt->close();

header("Location: Web/blackjacj/view/table.php?gruppekode=" . $_GET['gruppekode'] . "&spiller_id=" . $spiller_id);
exit;
?>
