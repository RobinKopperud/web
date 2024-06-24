<?php
include_once '../../../db.php'; // Adjust the path as needed

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['timeline_title']) && $_SESSION['authenticated']) {
    $title = $_POST['timeline_title'];
    $event_date = $_POST['timeline_date'];
    $comment = $_POST['timeline_comment'];
    $image = $_FILES['timeline_image']['name'];

    // Set a default value for image
    $target = null;
    if (!empty($image)) {
        $target = "../uploads/" . basename($image);
    }

    // Attempt to move the uploaded image if one was provided
    if (empty($image) || move_uploaded_file($_FILES['timeline_image']['tmp_name'], $target)) {
        $image = empty($image) ? null : basename($image); // Use null if no image was uploaded
        $sql = "INSERT INTO timeline_events (title, event_date, comment, image) VALUES ('$title', '$event_date', '$comment', " . ($image ? "'$image'" : "NULL") . ")";
        if ($conn->query($sql) === TRUE) {
            $success = 'Timeline event uploaded successfully!';
        } else {
            $error = 'Database error: ' . $conn->error;
        }
    } else {
        $error = 'Failed to upload image';
    }
}
?>

<form class="admin-form" method="POST" enctype="multipart/form-data">
    <label for="timeline_title">Tittel:</label>
    <input type="text" id="timeline_title" name="timeline_title" required>

    <label for="timeline_date">Dato:</label>
    <input type="date" id="timeline_date" name="timeline_date" required>

    <label for="timeline_comment">Kommentar:</label>
    <textarea id="timeline_comment" name="timeline_comment" optional></textarea>

    <label for="timeline_image">Bilde:</label>
    <input type="file" id="timeline_image" name="timeline_image" accept="image/*" optional>

    <button type="submit">Last opp hendelse</button>
    <?php if (isset($success)): ?>
        <p class="success"><?php echo $success; ?></p>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>
</form>
