<?php
include_once '../../includes/db.php';

$destinationId = $_POST['destination_id'];
$targetDir = "../uploads/";
$targetFile = $targetDir . basename($_FILES["image"]["name"]);
$imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

// Check if image file is an actual image
$check = getimagesize($_FILES["image"]["tmp_name"]);
if($check !== false) {
    if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
        $fileName = basename($_FILES["image"]["name"]);
        $query = "INSERT INTO images (destination_id, file_name) VALUES ($destinationId, '$fileName')";
        if ($conn->query($query) === TRUE) {
            header("Location: destination.php?id=$destinationId");
        } else {
            echo "Error: " . $conn->error;
        }
    } else {
        echo "Sorry, there was an error uploading your file.";
    }
} else {
    echo "File is not an image.";
}
?>
