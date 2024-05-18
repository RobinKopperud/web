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

$sql = "SELECT username, comment, created_at FROM comments ORDER BY created_at DESC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<div class='comment'>";
        echo "<p><strong>" . htmlspecialchars($row['username']) . "</strong> (" . $row['created_at'] . ")</p>";
        echo "<p>" . htmlspecialchars($row['comment']) . "</p>";
        echo "</div>";
    }
} else {
    echo "<p>Ingen kommentarer ennå. Vær den første til å legge inn en kommentar!</p>";
}

$conn->close();
?>
