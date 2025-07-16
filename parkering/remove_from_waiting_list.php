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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['waiting_id'])) {
    $waiting_id = $conn->real_escape_string($_POST['waiting_id']);
    $user_id = $conn->real_escape_string($_SESSION['user_id']);

    // Verify the waiting list entry belongs to the user
    $result = $conn->query("SELECT waiting_id FROM waiting_list WHERE waiting_id = '$waiting_id' AND user_id = '$user_id'");
    if ($result->num_rows === 0) {
        header("Location: profile.php?error=Ugydlig ventelisteoppføring.");
        exit;
    }

    // Delete the waiting list entry
    $query = "DELETE FROM waiting_list WHERE waiting_id = '$waiting_id' AND user_id = '$user_id'";
    if ($conn->query($query)) {
        header("Location: profile.php?success=Du er fjernet fra ventelisten.");
    } else {
        header("Location: profile.php?error=Feil ved fjerning fra venteliste: " . $conn->error);
    }
    exit;
}

$conn->close();
header("Location: profile.php?error=Ugyldig forespørsel.");
?>