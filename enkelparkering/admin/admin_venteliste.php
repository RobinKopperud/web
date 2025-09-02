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

// Hent venteliste med bruker og anlegg
$stmt = $conn->prepare("
    SELECT v.id, v.user_id, v.anlegg_id, v.ønsker_lader, v.registrert,
           u.navn, u.epost, a.navn AS anlegg_navn
    FROM venteliste v
    JOIN users u ON v.user_id = u.id
    LEFT JOIN anlegg a ON v.anlegg_id = a.id
    WHERE v.borettslag_id = ?
    ORDER BY v.registrert ASC
");
$stmt->bind_param("i", $user['borettslag_id']);
$stmt->execute();
$venteliste = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Funksjon: sjekk om ønsket er ledig
function sjekk_ledig($conn, $v, $borettslag_id) {
    if ($v['anlegg_id']) {
        // Brukeren ønsker et spesifikt anlegg
        $sql = "SELECT COUNT(*) AS antall 
                FROM plasser 
                WHERE anlegg_id = ? AND status = 'ledig'";
        if ($v['ønsker_lader']) {
            $sql .= " AND har_lader = 1";
        }
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $v['anlegg_id']);
    } else {
        // Første ledige plass i borettslaget
        $sql = "SELECT COUNT(*) AS antall 
                FROM plasser p
                JOIN anlegg a ON p.anlegg_id = a.id
                WHERE a.borettslag_id = ? AND p.status = 'ledig'";
        if ($v['ønsker_lader']) {
            $sql .= " AND p.har_lader = 1";
        }
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $borettslag_id);
    }

    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['antall'] > 0;
}

$title = "Venteliste";
ob_start();
?>

<h1>Venteliste</h1>

<?php if (!$venteliste): ?>
  <p>Ingen brukere står på venteliste.</p>
<?php else: ?>
  <table border="1" cellpadding="6" cellspacing="0">
    <tr>
      <th>Navn</th>
      <th>E-post</th>
      <th>Ønsker lader</th>
      <th>Anlegg</th>
      <th>Registrert</th>
      <th>Status</th>
      <th>Handling</th>
    </tr>
    <?php foreach ($venteliste as $v): 
        // Hent ledige plasser
        if ($v['anlegg_id']) {
            $stmt = $conn->prepare("
                SELECT p.*, a.navn AS anlegg_navn
                FROM plasser p
                JOIN anlegg a ON p.anlegg_id = a.id
                WHERE p.anlegg_id = ? AND p.status = 'ledig' " . 
                ($v['ønsker_lader'] ? "AND p.har_lader = 1" : "") . "
                LIMIT 1
            ");
            $stmt->bind_param("i", $v['anlegg_id']);
        } else {
            $stmt = $conn->prepare("
                SELECT p.*, a.navn AS anlegg_navn
                FROM plasser p
                JOIN anlegg a ON p.anlegg_id = a.id
                WHERE a.borettslag_id = ? AND p.status = 'ledig' " . 
                ($v['ønsker_lader'] ? "AND p.har_lader = 1" : "") . "
                LIMIT 1
            ");
            $stmt->bind_param("i", $user['borettslag_id']);
        }
        $stmt->execute();
        $ledig_plass = $stmt->get_result()->fetch_assoc();
    ?>
    <tr>
    <td><?= htmlspecialchars($v['navn']) ?></td>
    <td><?= htmlspecialchars($v['epost']) ?></td>
    <td><?= $v['ønsker_lader'] ? '⚡ Ja' : 'Nei' ?></td>
    <td><?= $v['anlegg_navn'] ?? 'Første ledige' ?></td>
    <td><?= $v['registrert'] ?></td>
    <td style="color:<?= $ledig_plass ? 'green' : 'red' ?>;">
        <?= $ledig_plass ? '✅ ' . $ledig_plass['anlegg_navn'] . ' plass ' . $ledig_plass['nummer'] : '❌ Ingen ledig' ?>
    </td>
    <td>
        <?php if ($ledig_plass): ?>
        <form method="post" action="venteliste_tildel.php" style="display:inline;">
            <input type="hidden" name="venteliste_id" value="<?= $v['id'] ?>">
            <input type="hidden" name="plass_id" value="<?= $ledig_plass['id'] ?>">
            <button type="submit" onclick="return confirm('Tildel <?= $ledig_plass['anlegg_navn'] ?> plass <?= $ledig_plass['nummer'] ?> til <?= htmlspecialchars($v['navn']) ?>?')">Bekreft</button>
        </form>
        <?php else: ?>
        <button disabled>Ingen ledig</button>
        <?php endif; ?>
        <form method="post" action="fjern_venteliste.php" style="display:inline;">
        <input type="hidden" name="venteliste_id" value="<?= $v['id'] ?>">
        <button type="submit" onclick="return confirm('Fjern fra venteliste?')">🗑 Fjern</button>
        </form>
    </td>
    </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<?php
$content = ob_get_clean();
include "admin_layout.php";
