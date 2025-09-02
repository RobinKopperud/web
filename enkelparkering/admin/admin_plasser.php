<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

// Sjekk admin
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT rolle, borettslag_id FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user['rolle'] !== 'admin') {
    die("Ingen tilgang.");
}

// Håndter ny plass
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'create') {
    $anlegg_id = $_POST['anlegg_id'];
    $nummer = $_POST['nummer'];
    $har_lader = isset($_POST['har_lader']) ? 1 : 0;

    $stmt = $conn->prepare("INSERT INTO plasser (anlegg_id, nummer, har_lader) VALUES (?, ?, ?)");
    $stmt->bind_param("isi", $anlegg_id, $nummer, $har_lader);
    $stmt->execute();
}

// Hent alle anlegg for dropdown
$stmt = $conn->prepare("SELECT * FROM anlegg WHERE borettslag_id = ?");
$stmt->bind_param("i", $user['borettslag_id']);
$stmt->execute();
$anlegg_liste = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Hvis admin har valgt et anlegg
$valgt_anlegg_id = $_GET['anlegg_id'] ?? null;
$plasser = [];
if ($valgt_anlegg_id) {
    $stmt = $conn->prepare("SELECT * FROM plasser WHERE anlegg_id = ?");
    $stmt->bind_param("i", $valgt_anlegg_id);
    $stmt->execute();
    $plasser = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$title = "Plasser";
ob_start();
?>

<h1>Administrer plasser</h1>

<!-- Velg anlegg -->
<form method="get" action="">
  <label>Velg anlegg:</label>
  <select name="anlegg_id" onchange="this.form.submit()">
    <option value="">-- Velg --</option>
    <?php foreach ($anlegg_liste as $a): ?>
      <option value="<?= $a['id'] ?>" <?= ($valgt_anlegg_id == $a['id']) ? 'selected' : '' ?>>
        <?= htmlspecialchars($a['navn']) ?>
      </option>
    <?php endforeach; ?>
  </select>
</form>

<?php if ($valgt_anlegg_id): ?>
  <h2>Plasser i valgt anlegg</h2>

  <!-- Liste over plasser -->
  <table border="1" cellpadding="6" cellspacing="0">
    <tr>
      <th>Nummer</th>
      <th>Status</th>
      <th>Lader</th>
      <th>Beboer</th>
    </tr>
    <?php foreach ($plasser as $p): ?>
      <tr>
        <td><?= htmlspecialchars($p['nummer']) ?></td>
        <td><?= $p['status'] ?></td>
        <td><?= $p['har_lader'] ? '⚡' : '❌' ?></td>
        <td><?= $p['beboer_id'] ?? '-' ?></td>
      </tr>
    <?php endforeach; ?>
  </table>

  <h3>Legg til ny plass</h3>
  <form method="post">
    <input type="hidden" name="action" value="create">
    <input type="hidden" name="anlegg_id" value="<?= $valgt_anlegg_id ?>">

    <label>Nummer:</label>
    <input type="text" name="nummer" required>

    <label><input type="checkbox" name="har_lader"> Har lader</label><br><br>

    <button type="submit">➕ Opprett plass</button>
  </form>
<?php endif; ?>

<?php
$content = ob_get_clean();
include "admin_layout.php";
