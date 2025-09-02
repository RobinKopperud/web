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
  <title>Logg inn – EnkelParkering</title>
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: #f4f6f9;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }
    .login-container {
      background: white;
      padding: 2rem;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      width: 320px;
    }
    .login-container h1 {
      text-align: center;
      margin-bottom: 1.5rem;
      color: #2c3e50;
    }
    form {
      margin-bottom: 1.5rem;
    }
    form h2 {
      font-size: 1rem;
      margin-bottom: 0.5rem;
      color: #2c3e50;
    }
    input {
      width: 100%;
      padding: 10px;
      margin-bottom: 10px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 0.9rem;
    }
    button {
      width: 100%;
      padding: 10px;
      background: #40739e;
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 0.95rem;
      transition: background 0.2s;
    }
    button:hover {
      background: #273c75;
    }
    .message {
      text-align: center;
      margin-bottom: 1rem;
      color: red;
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
