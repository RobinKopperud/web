<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $creator_name = trim($_POST['creator_name']);

    // Lag unik gruppekode (6 store bokstaver/tall)
    $gruppekode = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));

    // Opprett gruppe
    $stmt = $conn->prepare("INSERT INTO BJGrupper (gruppekode, opprettet_av) VALUES (?, ?)");
    $stmt->bind_param("ss", $gruppekode, $creator_name);
    $stmt->execute();
    $gruppe_id = $conn->insert_id;
    $stmt->close();

    // Legg til oppretter som første spiller
    $stmt2 = $conn->prepare("INSERT INTO BJSpillere (gruppe_id, navn) VALUES (?, ?)");
    $stmt2->bind_param("is", $gruppe_id, $creator_name);
    $stmt2->execute();
    $spiller_id = $conn->insert_id;
    $stmt2->close();

    $conn->close();

    // Send videre til bordet
    header("Location: /web/blackjacj/view/table.php?gruppekode=$group_code&spiller_id=$spiller_id");
    exit;
}
?>
