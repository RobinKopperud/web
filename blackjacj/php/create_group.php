<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $creator_name = trim($_POST['creator_name']);

    // Her kan du generere en unik gruppekode, for eksempel ved hjelp av uniqid() eller en annen metode
    $gruppekode = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));

    // Sett inn i tabellen BJGrupper
    $stmt = $conn->prepare("INSERT INTO BJGrupper (gruppekode, opprettet_av) VALUES (?, ?)");
    $stmt->bind_param("ss", $gruppekode, $creator_name);
    if ($stmt->execute()) {
        echo "Gruppe opprettet! Gruppkode: " . $gruppekode;
    } else {
        echo "Feil ved oppretting av gruppe: " . $stmt->error;
    }
    $stmt->close();
}
$conn->close();
?>
