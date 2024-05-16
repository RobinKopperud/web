<?php
$servername = "localhost";
$username = "jmntxjwa_admin";
$password = "Mafia124lol";
$dbname = "jmntxjwa_users";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = htmlspecialchars($_POST['username']);
    $comment = htmlspecialchars($_POST['comment']);

    // Insert comment into database
    $stmt = $conn->prepare("INSERT INTO comments (username, comment) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $comment);
    if ($stmt->execute()) {
        header("Location: ai.php#comments");
        exit();
    } else {
        echo "Feil ved lagring av kommentar.";
    }
    $stmt->close();
}

$conn->close();
?>
