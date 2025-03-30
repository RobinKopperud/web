<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $creator_name = trim($_POST['creator_name']);

    if (empty($creator_name)) {
        die("Vennligst skriv inn navnet ditt.");
    }

    if (strlen($creator_name) > 50) {
        die("Navnet er for langt. Maks 50 tegn.");
    }

    // Generer gruppekode
    $gruppekode = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));

    // Opprett gruppe
    $stmt = $conn->prepare("INSERT INTO BJGrupper (gruppekode, opprettet_av) VALUES (?, ?)");
    $stmt->bind_param("ss", $gruppekode, $creator_name);
    $stmt->execute();
    $gruppe_id = $conn->insert_id;
    $stmt->close();

    // Legg til spiller
    $stmt2 = $conn->prepare("INSERT INTO BJSpillere (gruppe_id, navn) VALUES (?, ?)");
    $stmt2->bind_param("is", $gruppe_id, $creator_name);
    $stmt2->execute();
    $spiller_id = $conn->insert_id;
    $stmt2->close();

    $conn->close();

    // Redirect med full path inkludert gruppekode
    header("Location: /web/blackjacj/view/table.php?gruppekode=$gruppekode&spiller_id=$spiller_id");
    exit;
} else {
    http_response_code(405);
    echo "Ugyldig metode.";
}
?>
