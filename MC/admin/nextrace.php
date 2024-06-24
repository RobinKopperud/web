<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_date']) && $_SESSION['authenticated']) {
    $event_date = $_POST['event_date'];
    file_put_contents('../next_event.txt', $event_date);
    $success = 'Dato oppdatert suksessfullt!';
}
?>

<form class="admin-form" method="POST">
    <label for="event_date">Neste arrangementsdato:</label>
    <input type="datetime-local" id="event_date" name="event_date" required>
    <button type="submit">Oppdater</button>
    <?php if (isset($success)): ?>
        <p class="success"><?php echo $success; ?></p>
    <?php endif; ?>
</form>
