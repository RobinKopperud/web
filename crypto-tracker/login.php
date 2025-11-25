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
        $error = 'Please enter your email and password.';
    } elseif (attempt_login($conn, $email, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid credentials. Try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in · Crypto Tracker</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .auth-card { max-width: 420px; margin: 60px auto; }
        .auth-card h1 { margin-bottom: 8px; }
        .auth-card form { display: flex; flex-direction: column; gap: 12px; }
        .auth-card input { width: 100%; }
        .auth-hint { color: var(--muted); margin-top: 4px; }
    </style>
</head>
<body>
<div class="container">
    <section class="card auth-card">
        <h1>Sign in</h1>
        <p class="subtitle">Use the same credentials as your main account.</p>

        <?php if ($error): ?>
            <div class="alert error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-control">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" placeholder="you@example.com" required>
            </div>
            <div class="form-control">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn primary">Login</button>
            </div>
            <p class="auth-hint">Forgot your password? Reset it from the main app.</p>
        </form>
    </section>
</div>
</body>
</html>
