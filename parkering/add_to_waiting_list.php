<?php
// Start session
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once '../../db.php'; // Correct path

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=Du må være logget inn.");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $conn->real_escape_string($_SESSION['user_id']);
    $facility_id = isset($_POST['facility_id']) ? $conn->real_escape_string($_POST['facility_id']) : null;
    $spot_id = isset($_POST['spot_id']) ? $conn->real_escape_string($_POST['spot_id']) : null;
    $spot_type = isset($_POST['spot_type']) ? $conn->real_escape_string($_POST['spot_type']) : null;

    // If spot_id is provided, fetch the corresponding facility_id
    if ($spot_id && !$facility_id) {
        $spot_result = $conn->query("SELECT facility_id FROM parking_spots WHERE spot_id = '$spot_id'");
        if ($spot_result && $spot_result->num_rows > 0) {
            $spot = $spot_result->fetch_assoc();
            $facility_id = $spot['facility_id'];
        } else {
            header("Location: parking.php?error=Ugyldig plass-ID.");
            exit;
        }
    }

    // Validate input
    if (!$facility_id && !$spot_id && !$spot_type) {
        header("Location: parking.php" . ($facility_id ? "?facility_id=$facility_id" : "") . "&error=Velg minst ett kriterium for ventelisten.");
        exit;
    }

    // Check if user is already on the waiting list for this combination
    $query = "SELECT waiting_id FROM waiting_list WHERE user_id = '$user_id'";
    if ($facility_id) $query .= " AND facility_id = '$facility_id'";
    if ($spot_id) $query .= " AND spot_id = '$spot_id'";
    if ($spot_type) $query .= " AND spot_type = '$spot_type'";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        header("Location: parking.php" . ($facility_id ? "?facility_id=$facility_id" : "") . "&error=Du er allerede på ventelisten for denne kombinasjonen.");
        exit;
    }

    // Insert into waiting list
    $query = "INSERT INTO waiting_list (user_id, facility_id, spot_id, spot_type) 
              VALUES ('$user_id', " . ($facility_id ? "'$facility_id'" : "NULL") . ", " . 
              ($spot_id ? "'$spot_id'" : "NULL") . ", " . ($spot_type ? "'$spot_type'" : "NULL") . ")";
    if ($conn->query($query)) {
        header("Location: parking.php" . ($facility_id ? "?facility_id=$facility_id" : "") . "&success=Du er lagt til i ventelisten.");
    } else {
        header("Location: parking.php" . ($facility_id ? "?facility_id=$facility_id" : "") . "&error=Feil ved registrering i venteliste: " . $conn->error);
    }
    exit;
}

$conn->close();
header("Location: parking.php?error=Ugyldig forespørsel.");
?>