<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

// Sjekk admin
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT rolle FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user['rolle'] !== 'admin') {
    die("Ingen tilgang.");
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $title ?? 'Adminpanel' ?> – EnkelParkering</title>
  <link rel="stylesheet" href="admin.css">
</head>
<body>
  <header class="header">
    <div>⚙️ Adminpanel</div>
    <div>
      <a href="../index.php">Til forsiden</a>
      <a href="../logout.php">Logg ut</a>
    </div>
  </header>

  <main class="dashboard">
    <aside class="sidebar">
      <h2>Meny</h2>
      <ul>
        <li><a href="admin.php">🏠 Dashboard</a></li>
        <li><a href="admin_anlegg.php">🅿️ Anlegg</a></li>
        <li><a href="admin_plasser.php">📋 Plasser</a></li>
        <li><a href="admin_brukere.php">👥 Brukere</a></li>
        <li><a href="admin_venteliste.php">⏳ Venteliste</a></li>
      </ul>
    </aside>

    <section class="content">
      <?php
      // Her vil undersidene putte sitt innhold
      if (isset($content)) {
          echo $content;
      }
      ?>
    </section>
  </main>
</body>
</html>
