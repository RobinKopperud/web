<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

$transaksjon_id = $_POST['transaksjon_id'];
$godkjent       = $_POST['godkjent'];
$spiller_id     = $_POST['spiller_id'] ?? null; // viktig å hente dette hvis du bruker session

if (!$transaksjon_id || $godkjent === null || !$spiller_id) {
    http_response_code(400);
    echo "Mangler påkrevde data.";
    exit;
}

// Sjekk om spilleren allerede har stemt
$stmt = $conn->prepare("SELECT 1 FROM BJGodkjenninger WHERE transaksjon_id = ? AND spiller_id = ?");
$stmt->bind_param("ii", $transaksjon_id, $spiller_id);
$stmt->execute();
$stmt->store_result();
$allerede_stemt = $stmt->num_rows > 0;
$stmt->close();

if ($allerede_stemt) {
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

// Registrer stemme
$stmt = $conn->prepare("INSERT INTO BJGodkjenninger (transaksjon_id, spiller_id, stemme) VALUES (?, ?, ?)");
$stmt->bind_param("iii", $transaksjon_id, $spiller_id, $godkjent);
$stmt->execute();
$stmt->close();

// Hent totalt antall spillere (utenom den som foreslo)
$res = $conn->query("
    SELECT t.gruppe_id, t.maal_spiller_id, t.belop
    FROM BJTransaksjoner t
    WHERE t.transaksjon_id = $transaksjon_id
");
$data = $res->fetch_assoc();
$gruppe_id = $data['gruppe_id'];
$maal_spiller_id = $data['maal_spiller_id'];
$nytt_belop = $data['belop'];

// Tell hvor mange andre spillere det er i gruppa
$res = $conn->query("
    SELECT COUNT(*) AS antall
    FROM BJSpillere
    WHERE gruppe_id = $gruppe_id AND spiller_id != $maal_spiller_id
");
$andre_spillere = $res->fetch_assoc()['antall'];

// Tell godkjenninger
$res = $conn->query("
    SELECT COUNT(*) AS total_stemmer,
           SUM(stemme) AS antall_ja
    FROM BJGodkjenninger
    WHERE transaksjon_id = $transaksjon_id
");
$stemmer = $res->fetch_assoc();

// Er det nok "ja"-stemmer?
if ($stemmer['antall_ja'] >= ceil($andre_spillere / 2)) {
    // Oppdater saldo
    $conn->query("UPDATE BJSpillere SET saldo = $nytt_belop WHERE spiller_id = $maal_spiller_id");
    $conn->query("UPDATE BJTransaksjoner SET status = 'godkjent' WHERE transaksjon_id = $transaksjon_id");
}

// Eventuelt avslå hvis det er flertall nei
if (($stemmer['total_stemmer'] - $stemmer['antall_ja']) >= ceil($andre_spillere / 2)) {
    $conn->query("UPDATE BJTransaksjoner SET status = 'avslått' WHERE transaksjon_id = $transaksjon_id");
}

$conn->close();
header("Location: " . $_SERVER['HTTP_REFERER']);
exit;
?>
