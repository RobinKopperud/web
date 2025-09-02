<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

// Sjekk admin
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
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
  <title>Adminpanel – EnkelParkering</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header class="header">
    <div>👋 Adminpanel</div>
    <div><a href="index.php">Tilbake</a></div>
  </header>

  <main class="dashboard">
    <aside class="sidebar">
      <h2>Administrasjon</h2>
      <ul>
        <li><a href="admin_anlegg.php">⚙️ Anlegg</a></li>
        <li><a href="admin_plasser.php">🅿️ Parkeringsplasser</a></li>
        <li><a href="admin_brukere.php">👥 Brukere</a></li>
      </ul>
    </aside>
    <section class="map-area" style="display:flex; align-items:center; justify-content:center;">
      <h2>Velg en modul i menyen</h2>
    </section>
  </main>
</body>
</html>
