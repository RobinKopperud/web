<?php
session_start();
include_once '../../db.php'; // Adjust the path as needed

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$facility_id = isset($_POST['facility_id']) ? (int)$_POST['facility_id'] : null;
$spot_id = isset($_POST['spot_id']) ? (int)$_POST['spot_id'] : null;
$spot_type = isset($_POST['spot_type']) ? $_POST['spot_type'] : null;

try {
    // Sjekk om brukeren allerede er på ventelisten for denne kombinasjonen
    $stmt = $conn->prepare("SELECT waiting_id FROM waiting_list 
        WHERE user_id = ? AND (facility_id = ? OR spot_id = ? OR spot_type = ?)");
    $stmt->execute([$user_id, $facility_id, $spot_id, $spot_type]);
    if ($stmt->fetch()) {
        $error = "Du er allerede på ventelisten for denne plassen eller typen.";
    } else {
        // Legg til i ventelisten
        $stmt = $conn->prepare("INSERT INTO waiting_list (user_id, facility_id, spot_id, spot_type) 
            VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $facility_id, $spot_id, $spot_type]);
        $success = "Du er lagt til på ventelisten.";
    }
} catch(PDOException $e) {
    $error = "Feil: " . $e->getMessage();
}

header("Location: parking.php" . ($facility_id ? "?facility_id=$facility_id" : "") . 
    (isset($success) ? "&success=" . urlencode($success) : "") . 
    (isset($error) ? "&error=" . urlencode($error) : ""));
exit;
?>