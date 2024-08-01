<?php
include_once '../../db.php'; // Adjust the path as needed


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT name, status FROM names";
$result = $conn->query($sql);

$names = array();
while ($row = $result->fetch_assoc()) {
    $names[] = $row;
}

echo json_encode($names);

$conn->close();
?>
