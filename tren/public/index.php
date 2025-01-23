<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}
?>

<?php include_once '../includes/header.php'; ?>

<main>
    <h2>Velkommen til Treningsplattformen</h2>
    <p>Logg inn for å begynne å spore fremgangen din.</p>
    <a href="login.php">Logg Inn</a> | <a href="register.php">Registrer</a>
</main>

<?php include_once '../includes/footer.php'; ?>
