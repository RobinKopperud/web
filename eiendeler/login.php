<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';
require_once __DIR__ . '/auth.php';

$error = '';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Skriv inn e-post og passord.';
    } elseif (attempt_login($conn, $email, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Ugyldig innlogging. Prøv igjen.';
    }
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logg inn · Mine verdier</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="shell auth-shell">
    <section class="card auth-card">
        <p class="eyebrow">Mine verdier</p>
        <h1>Logg inn</h1>
        <p class="muted">Bruk samme bruker som resten av løsningene.</p>
        <?php if ($error): ?>
            <div class="alert error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <form method="POST" action="login.php" class="stacked-form">
            <label>E-post
                <input type="email" name="email" autocomplete="email" required>
            </label>
            <label>Passord
                <input type="password" name="password" autocomplete="current-password" required>
            </label>
            <button type="submit" class="btn primary">Logg inn</button>
        </form>
    </section>
</main>
</body>
</html>
