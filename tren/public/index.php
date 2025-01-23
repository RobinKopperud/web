<?php
session_start(); // Start session to check if the user is logged in

// Redirect to dashboard if the user is already logged in
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
