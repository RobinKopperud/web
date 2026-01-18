<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';
require_once __DIR__ . '/auth.php';

$error = '';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

maybe_login_from_cookie($conn);

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $remember_me = isset($_POST['remember_me']);

    if ($email === '' || $password === '') {
        $error = 'Fyll inn e-post og passord.';
    } elseif (attempt_login($conn, $email, $password, $remember_me)) {
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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Logg inn · Treningslogg</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <div class="app auth">
    <section class="auth-card">
      <p class="eyebrow">Treningslogg</p>
      <h1>Logg inn</h1>
      <p class="lead">Bruk samme innlogging som i de andre appene.</p>

      <?php if ($error): ?>
        <div class="alert error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>

      <form method="post" action="login.php" class="entry-form">
        <label>
          E-post
          <input type="email" name="email" placeholder="deg@eksempel.no" required />
        </label>
        <label>
          Passord
          <input type="password" name="password" required />
        </label>
        <label class="checkbox">
          <input type="checkbox" name="remember_me" />
          Husk meg på denne enheten
        </label>
        <button class="primary" type="submit">Logg inn</button>
      </form>
      <p class="subtle">Glemt passord? Bruk hovedappen for å tilbakestille.</p>
    </section>
  </div>
</body>
</html>
