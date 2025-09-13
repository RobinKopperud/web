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

// Oppdater rolle
if (isset($_POST['update_role'])) {
    $target_id = (int)$_POST['user_id'];
    $rolle = $_POST['rolle'] === 'admin' ? 'admin' : 'user';

    $stmt = $conn->prepare("UPDATE users SET rolle = ? WHERE id = ? AND borettslag_id = ?");
    $stmt->bind_param("sii", $rolle, $target_id, $user['borettslag_id']);
    $stmt->execute();
}

// Slett bruker
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    if ($delete_id !== $user_id) { // Ikke slett deg selv
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND borettslag_id = ?");
        $stmt->bind_param("ii", $delete_id, $user['borettslag_id']);
        $stmt->execute();
    }
}

// Hent brukere
$stmt = $conn->prepare("SELECT id, navn, epost, rolle FROM users WHERE borettslag_id = ?");
$stmt->bind_param("i", $user['borettslag_id']);
$stmt->execute();
$brukere = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$title = "Brukere";
ob_start();
?>

<h1>Administrer brukere</h1>

<?php if (!$brukere): ?>
  <p>Ingen brukere funnet.</p>
<?php else: ?>
  <table border="1" cellpadding="6" cellspacing="0">
    <tr>
      <th>Navn</th>
      <th>E-post</th>
      <th>Rolle</th>
      <th>Handling</th>
    </tr>
    <?php foreach ($brukere as $b): ?>
    <tr>
      <td><?= htmlspecialchars($b['navn']) ?></td>
      <td><?= htmlspecialchars($b['epost']) ?></td>
      <td>
        <form method="post" style="display:inline-block;">
          <input type="hidden" name="user_id" value="<?= $b['id'] ?>">
          <select name="rolle">
            <option value="user" <?= $b['rolle'] === 'user' ? 'selected' : '' ?>>Bruker</option>
            <option value="admin" <?= $b['rolle'] === 'admin' ? 'selected' : '' ?>>Admin</option>
          </select>
          <button type="submit" name="update_role">Oppdater</button>
        </form>
      </td>
      <td>
        <?php if ($b['id'] !== $user_id): ?>
          <a href="?delete=<?= $b['id'] ?>" onclick="return confirm('Slette bruker?')">🗑 Slett</a>
        <?php else: ?>
          -
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<?php
$content = ob_get_clean();
include "admin_layout.php";
