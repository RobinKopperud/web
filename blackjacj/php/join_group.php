<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Hent og trim data fra POST
    $group_code = trim($_POST['group_code']);
    $name = trim($_POST['name']);

    // Input validation
    if (empty($group_code) || empty($name)) {
        die("Vennligst fyll ut alle feltene.");
    }

    if (strlen($name) > 50) {  // Assuming max length is 50
        die("Navnet er for langt. Maksimum 50 tegn.");
    }

    // Sjekk om gruppen finnes ved å hente gruppe_id fra BJGrupper
    $stmt = $conn->prepare("SELECT gruppe_id FROM BJGrupper WHERE gruppekode = ?");
    $stmt->bind_param("s", $group_code);
    $stmt->execute();
    $stmt->bind_result($gruppe_id);

    if ($stmt->fetch()) {
        $stmt->close();

        // Check if player name already exists in this group
        $check_stmt = $conn->prepare("SELECT spiller_id FROM BJSpillere WHERE gruppe_id = ? AND navn = ?");
        $check_stmt->bind_param("is", $gruppe_id, $name);
        $check_stmt->execute();
        
        if ($check_stmt->fetch()) {
            $check_stmt->close();
            die("En spiller med dette navnet eksisterer allerede i gruppen.");
        }
        $check_stmt->close();

        // Legg til spilleren i BJSpillere
        $stmt2 = $conn->prepare("INSERT INTO BJSpillere (gruppe_id, navn) VALUES (?, ?)");
        $stmt2->bind_param("is", $gruppe_id, $name);
        if ($stmt2->execute()) {
            echo "Du har nå blitt med i gruppen med kode: " . htmlspecialchars($group_code);
            // Etter at spilleren er lagt til
            header("Location: /view/table.php?gruppekode=$group_code&spiller_id=$conn->insert_id");
            exit;

        } else {
            echo "Feil ved innmelding: " . $stmt2->error;
        }
        $stmt2->close();
    } else {
        http_response_code(404);
        echo "Ingen gruppe funnet med koden: " . htmlspecialchars($group_code);
        $stmt->close();
    }
    $conn->close();
} else {
    http_response_code(405);
    echo "Ugyldig forespørsel metode.";
}
?>
