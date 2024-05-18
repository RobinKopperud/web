<?php
$servername = "localhost";
$username = "jmntxjwa_AI";
$password = "ai-Admin";
$dbname = "jmntxjwa_users";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT username, score, created_at FROM highscores ORDER BY score DESC, created_at ASC LIMIT 5";
$result = $conn->query($sql);

$highscores = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $highscores[] = $row;
    }
}

$conn->close();

header('Content-Type: application/json');
echo json_encode($highscores);
?>
