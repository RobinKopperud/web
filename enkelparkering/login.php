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
            $subject = "Velkommen til EnkelParkering";
            $body = "Hei $navn,\n\nTakk for at du registrerte deg hos EnkelParkering. Du kan nå logge inn og administrere parkeringsplassene dine.\n\nVennlig hilsen\nEnkelParkering";
            $headers = "From: noreply@robinkopperud.no\r\n" .
                       "Reply-To: noreply@robinkopperud.no\r\n" .
                       "X-Mailer: PHP/" . phpversion();

            mail($email, $subject, $body, $headers);

            $message = "✅ Bruker opprettet. Logg inn nå.";
        } else {
            $message = "Feil: " . $conn->error;
        }
    } else {
        $message = "❌ Ugyldig kode fra borettslaget.";
    }
}

// Hent alle borettslag
$sql = "SELECT navn FROM borettslag ORDER BY navn ASC";
$result = $conn->query($sql);
$borettslag = [];
if ($result) {
    while($row = $result->fetch_assoc()) {
        $borettslag[] = $row['navn'];
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
    *,
    *::before,
    *::after {
      box-sizing: border-box;
    }

    :root {
      --surface: #e0e0e0;
      --shadow-dark: rgba(163, 177, 198, 0.65);
      --shadow-light: rgba(255, 255, 255, 0.85);
      --text-muted: #4f4f4f;
      --accent: #4c86e4;
    }

    body {
      margin: 0;
      font-family: "Segoe UI", Tahoma, sans-serif;
      background: radial-gradient(circle at top left, rgba(76, 134, 228, 0.18), transparent 55%),
        radial-gradient(circle at bottom right, rgba(255, 197, 125, 0.18), transparent 50%),
        var(--surface);
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      padding: 1.5rem;
      color: #3a3a3a;
    }

    .login-container {
      background: var(--surface);
      padding: 2.5rem 2.25rem;
      border-radius: 26px;
      box-shadow: 20px 20px 40px var(--shadow-dark),
        -20px -20px 40px var(--shadow-light);
      width: 100%;
      max-width: 380px;
      position: relative;
      overflow: hidden;
    }

    .login-container::before,
    .login-container::after {
      content: "";
      position: absolute;
      border-radius: 50%;
      pointer-events: none;
    }

    .login-container::before {
      width: 260px;
      height: 260px;
      background: linear-gradient(135deg, rgba(76, 134, 228, 0.35), rgba(255, 255, 255, 0));
      top: -140px;
      right: -140px;
    }

    .login-container::after {
      width: 180px;
      height: 180px;
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.12), rgba(76, 134, 228, 0));
      bottom: -90px;
      left: -90px;
    }

    .brand {
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.75rem;
      margin-bottom: 1.75rem;
      z-index: 1;
    }

    .brand-icon {
      width: 54px;
      height: 54px;
      border-radius: 18px;
      display: grid;
      place-items: center;
      background: linear-gradient(135deg, rgba(76, 134, 228, 0.28), rgba(76, 134, 228, 0.05));
      box-shadow: inset 6px 6px 12px rgba(163, 177, 198, 0.35),
        inset -6px -6px 12px rgba(255, 255, 255, 0.65);
      color: #2d4f87;
      font-weight: 700;
      font-size: 1.4rem;
      letter-spacing: 0.04em;
    }

    .brand h1 {
      text-align: center;
      margin: 0;
      color: #2c2c2c;
      letter-spacing: 0.04em;
    }

    .brand p {
      margin: 0;
      color: #5c6a82;
      font-size: 0.9rem;
      letter-spacing: 0.04em;
    }

    form {
      margin-bottom: 1.75rem;
      position: relative;
      z-index: 1;
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
      position: relative;
      z-index: 1;
    }

    @media (min-width: 768px) {
      body {
        padding: 2rem 3rem;
      }

      .login-container {
        max-width: 420px;
        padding: 3rem 2.75rem;
      }
    }

    @media (min-width: 1100px) {
      .login-container {
        max-width: 460px;
      }
    }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="brand">
      <div class="brand-icon">P</div>
      <div>
        <h1>EnkelParkering</h1>
        <p>Parkering gjort enkelt – hver dag.</p>
      </div>
    </div>
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

    <div style="margin-top: 2rem; padding: 1rem;">
        <h2 style="font-size: 0.95rem; color: #5a5a5a; text-transform: uppercase; letter-spacing: 0.08em;">
            Tilknyttede borettslag
        </h2>
        <?php if (empty($borettslag)): ?>
            <p style="color: var(--text-muted); text-align: center;">
                Ingen borettslag registrert ennå.
            </p>
        <?php else: ?>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <?php foreach($borettslag as $navn): ?>
                    <li style="padding: 8px 12px; margin-bottom: 8px; 
                               background: var(--surface);
                               border-radius: 12px;
                               box-shadow: inset 3px 3px 6px var(--shadow-dark),
                                         inset -3px -3px 6px var(--shadow-light);">
                        <?= htmlspecialchars($navn) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
  </div>
</body>
</html>
