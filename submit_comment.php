<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>

<?php
// Aktiver feilmeldinger
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$servername = "localhost";
$username = "jmntxjwa_admin";
$password = "Mafia124lol";
$dbname = "jmntxjwa_users";

// Opprett forbindelse
$conn = new mysqli($servername, $username, $password, $dbname);

// Sjekk forbindelse
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = htmlspecialchars($_POST['username']);
    $comment = htmlspecialchars($_POST['comment']);

    // Sett inn kommentar i databasen
    $stmt = $conn->prepare("INSERT INTO comments (username, comment) VALUES (?, ?)");
    if (!$stmt) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }
    
    $stmt->bind_param("ss", $username, $comment);
    if (!$stmt->execute()) {
        die("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
    }
    
    $stmt->close();
    header("Location: ai.php#comments");
    exit();
}

$conn->close();
?>
