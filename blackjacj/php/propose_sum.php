<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

$gruppe_id   = $_POST['gruppe_id'];
$spiller_id  = $_POST['spiller_id'];
$belop       = $_POST['belop'];
$gruppekode  = $_POST['gruppekode'];

// Fjern gamle ventende forslag for samme spiller
$conn->query("DELETE FROM BJTransaksjoner WHERE maal_spiller_id = $spiller_id AND status = 'ventende'");

// Opprett nytt forslag
$stmt = $conn->prepare("INSERT INTO BJTransaksjoner (gruppe_id, maal_spiller_id, belop) VALUES (?, ?, ?)");
$stmt->bind_param("iid", $gruppe_id, $spiller_id, $belop);
$stmt->execute();
$stmt->close();

$conn->close();

// ✅ Send spilleren tilbake til riktig bord med gruppekode
header("Location: /web/blackjacj/view/table.php?gruppekode=$gruppekode&spiller_id=$spiller_id");
exit;
?>
