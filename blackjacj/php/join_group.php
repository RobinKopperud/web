<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php'; // Inkluderer databasekoblingen

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Hent og trim data fra POST
    $group_code = trim($_POST['group_code']);
    $name       = trim($_POST['name']);

    // Sjekk om gruppen finnes ved å hente gruppe_id fra BJGrupper
    $stmt = $conn->prepare("SELECT gruppe_id FROM BJGrupper WHERE gruppekode = ?");
    $stmt->bind_param("s", $group_code);
    $stmt->execute();
    $stmt->bind_result($gruppe_id);

    if ($stmt->fetch()) {
        $stmt->close();

        // Legg til spilleren i BJSpillere
        $stmt2 = $conn->prepare("INSERT INTO BJSpillere (gruppe_id, navn) VALUES (?, ?)");
        $stmt2->bind_param("is", $gruppe_id, $name);
        if ($stmt2->execute()) {
            echo "Du har nå blitt med i gruppen med kode: " . htmlspecialchars($group_code);
        } else {
            echo "Feil ved innmelding: " . $stmt2->error;
        }
        $stmt2->close();
    } else {
        echo "Ingen gruppe funnet med koden: " . htmlspecialchars($group_code);
        $stmt->close();
    }
    $conn->close();
} else {
    echo "Ugyldig forespørsel.";
}
?>
