<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

// Meldinger
$message = "";

// Håndter innlogging
if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $passord = trim($_POST['passord']);

    $sql = "SELECT * FROM users WHERE epost = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($passord, $user['passord'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['rolle'] = $user['rolle'];
        $_SESSION['borettslag_id'] = $user['borettslag_id'];

        header("Location: index.php");
        exit;
    } else {
        $message = "Feil e-post eller passord.";
    }
}

// Håndter registrering
if (isset($_POST['register'])) {
    $navn = trim($_POST['navn']);
    $email = trim($_POST['email']);
    $passord = password_hash(trim($_POST['passord']), PASSWORD_DEFAULT);
    $kode = trim($_POST['kode']); // borettslagskode

    $sql = "SELECT id FROM borettslag WHERE kode = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $kode);
    $stmt->execute();
    $result = $stmt->get_result();
    $borettslag = $result->fetch_assoc();

    if ($borettslag) {
        $sql = "INSERT INTO users (borettslag_id, navn, epost, passord, rolle) VALUES (?, ?, ?, ?, 'user')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isss", $borettslag['id'], $navn, $email, $passord);
        if ($stmt->execute()) {
            $message = "✅ Bruker opprettet. Logg inn nå.";
        } else {
            $message = "Feil: " . $conn->error;
        }
    } else {
        $message = "❌ Ugyldig kode fra borettslaget.";
    }
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Logg inn – EnkelParkering</title>
  <style>
    :root {
      --surface: #e0e0e0;
      --shadow-dark: rgba(163, 177, 198, 0.65);
      --shadow-light: rgba(255, 255, 255, 0.85);
      --text-muted: #4f4f4f;
    }

    body {
      margin: 0;
      font-family: "Segoe UI", Tahoma, sans-serif;
      background: var(--surface);
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      padding: 1.5rem;
      color: #3a3a3a;
    }

    .login-container {
      background: var(--surface);
      padding: 2.25rem 2rem;
      border-radius: 26px;
      box-shadow: 20px 20px 40px var(--shadow-dark),
        -20px -20px 40px var(--shadow-light);
      width: 100%;
      max-width: 360px;
    }

    .login-container h1 {
      text-align: center;
      margin-bottom: 1.75rem;
      color: #2c2c2c;
      letter-spacing: 0.04em;
    }

    form {
      margin-bottom: 1.75rem;
    }

    form h2 {
      font-size: 0.95rem;
      margin-bottom: 0.75rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: #5a5a5a;
    }

    input {
      width: 100%;
      padding: 12px 18px;
      margin-bottom: 12px;
      border: none;
      border-radius: 20px;
      font-size: 0.95rem;
      background: var(--surface);
      color: var(--text-muted);
      box-shadow: inset 6px 6px 12px var(--shadow-dark),
        inset -6px -6px 12px var(--shadow-light);
      transition: box-shadow 0.2s ease;
    }

    input:focus {
      outline: none;
      box-shadow: inset 4px 4px 8px var(--shadow-dark),
        inset -4px -4px 8px var(--shadow-light);
    }

    button {
      width: 100%;
      padding: 12px 18px;
      background: var(--surface);
      color: var(--text-muted);
      border: none;
      border-radius: 20px;
      cursor: pointer;
      font-size: 0.95rem;
      font-weight: 600;
      box-shadow: 8px 8px 16px var(--shadow-dark),
        -8px -8px 16px var(--shadow-light);
      transition: transform 0.2s ease, box-shadow 0.2s ease, color 0.2s ease;
    }

    button:hover {
      transform: translateY(-2px);
      color: #2d2d2d;
      box-shadow: 10px 10px 20px var(--shadow-dark),
        -10px -10px 20px var(--shadow-light);
    }

    button:active {
      box-shadow: inset 5px 5px 10px var(--shadow-dark),
        inset -5px -5px 10px var(--shadow-light);
      color: #3a3a3a;
    }

    .message {
      text-align: center;
      margin-bottom: 1.25rem;
      color: #b63b3b;
      background: var(--surface);
      padding: 0.75rem 1rem;
      border-radius: 18px;
      box-shadow: inset 5px 5px 10px var(--shadow-dark),
        inset -5px -5px 10px var(--shadow-light);
    }
  </style>
</head>
<body>
  <div class="login-container">
    <h1>EnkelParkering</h1>
    <?php if ($message): ?>
      <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <!-- Innlogging -->
    <form method="POST">
      <h2>Logg inn</h2>
      <input type="email" name="email" placeholder="E-post" required>
      <input type="password" name="passord" placeholder="Passord" required>
      <button type="submit" name="login">Logg inn</button>
    </form>

    <!-- Registrering -->
    <form method="POST">
      <h2>Registrer deg</h2>
      <input type="text" name="navn" placeholder="Navn" required>
      <input type="email" name="email" placeholder="E-post" required>
      <input type="password" name="passord" placeholder="Passord" required>
      <input type="text" name="kode" placeholder="Kode fra borettslaget" required>
      <button type="submit" name="register">Registrer bruker</button>
    </form>
  </div>
</body>
</html>
