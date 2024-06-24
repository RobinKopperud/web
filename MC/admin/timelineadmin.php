<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['timeline_title']) && $_SESSION['authenticated']) {
    $title = $_POST['timeline_title'];
    $event_date = $_POST['timeline_date'];
    $comment = $_POST['timeline_comment'];
    $image = $_FILES['timeline_image']['name'];
    $target = "../uploads/" . basename($image);

    if (move_uploaded_file($_FILES['timeline_image']['tmp_name'], $target)) {
        $sql = "INSERT INTO timeline_events (title, event_date, comment, image) VALUES ('$title', '$event_date', '$comment', '$image')";
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
    <textarea id="timeline_comment" name="timeline_comment" required></textarea>

    <label for="timeline_image">Bilde:</label>
    <input type="file" id="timeline_image" name="timeline_image" accept="image/*" required>

    <button type="submit">Last opp hendelse</button>
    <?php if (isset($success)): ?>
        <p class="success"><?php echo $success; ?></p>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>
</form>
