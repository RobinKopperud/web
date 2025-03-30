<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

$transaksjon_id = $_POST['transaksjon_id'];
$godkjent       = $_POST['godkjent']; // 1 = ja, 0 = nei
session_start();
$spiller_id = $_GET['spiller_id'] ?? $_POST['spiller_id'] ?? $_SESSION['spiller_id'] ?? null;

if ($spiller_id && $transaksjon_id !== null) {
    $stmt = $conn->prepare("INSERT INTO BJGodkjenninger (transaksjon_id, spiller_id, stemme) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $transaksjon_id, $spiller_id, $godkjent);
    $stmt->execute();
    $stmt->close();

    // Sjekk om vi har nok stemmer
    $res = $conn->query("
        SELECT t.maal_spiller_id, COUNT(*) AS antall, 
               SUM(g.stemme) AS ja
        FROM BJGodkjenninger g
        JOIN BJTransaksjoner t ON t.transaksjon_id = g.transaksjon_id
        WHERE g.transaksjon_id = $transaksjon_id
        GROUP BY t.transaksjon_id
    ");
    $data = $res->fetch_assoc();

    if ($data) {
        $maal_spiller_id = $data['maal_spiller_id'];
        $antall          = $data['antall'];
        $ja              = $data['ja'];

        // Hent antall andre spillere
        $res2 = $conn->query("
            SELECT COUNT(*) AS andre
            FROM BJSpillere
            WHERE gruppe_id = (SELECT gruppe_id FROM BJTransaksjoner WHERE transaksjon_id = $transaksjon_id)
              AND spiller_id != $maal_spiller_id
        ");
        $andre = $res2->fetch_assoc()['andre'];

        if ($ja >= ceil($andre / 2)) {
            // Oppdater saldo
            $res3 = $conn->query("
                SELECT belop FROM BJTransaksjoner WHERE transaksjon_id = $transaksjon_id
            ");
            $belop = $res3->fetch_assoc()['belop'];

            $conn->query("UPDATE BJSpillere SET saldo = $belop WHERE spiller_id = $maal_spiller_id");
            $conn->query("UPDATE BJTransaksjoner SET status = 'godkjent' WHERE transaksjon_id = $transaksjon_id");
        }
    }
}

$conn->close();
header("Location: " . $_SERVER['HTTP_REFERER']);
exit;
