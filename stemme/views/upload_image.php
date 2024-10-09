<?php
define('APP_INIT', true);
include_once '../../../includes/db.php';  // Database connection

$destinationId = $_POST['destination_id'];

// Directory to save uploaded images
$targetDir = "../uploads/";
$targetFile = $targetDir . basename($_FILES["image"]["name"]);
$imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

// Move the uploaded file
if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
    // Insert the image record into the database
    $fileName = basename($_FILES["image"]["name"]);
    $query = "INSERT INTO images (destination_id, file_name) VALUES (?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $destinationId, $fileName);
    $stmt->execute();
    $stmt->close();
}

// Redirect back to the destination page
header("Location: destination.php?id=$destinationId");
exit;
?>
