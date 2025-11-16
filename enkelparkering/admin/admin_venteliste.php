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
    SELECT v.id, v.user_id, v.anlegg_id, v.onsker_lader, v.registrert,
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

// Hjelpefunksjon for å hente kontrakt
function hent_kontrakt($conn, $venteliste_id) {
    $stmt = $conn->prepare("SELECT * FROM kontrakter WHERE venteliste_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("i", $venteliste_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

$title = "Venteliste";
ob_start();
$admin_message = $_SESSION['admin_message'] ?? null;
unset($_SESSION['admin_message']);
?>

<h1>Venteliste</h1>

<?php if ($admin_message): ?>
  <p class="message"><?= htmlspecialchars($admin_message) ?></p>
<?php endif; ?>

<?php if (!$venteliste): ?>
  <p>Ingen brukere står på venteliste.</p>
<?php else: ?>
  <table border="1" cellpadding="6" cellspacing="0" class="venteliste-tabell">
    <tr>
      <th>Navn</th>
      <th>E-post</th>
      <th>Ønsker lader</th>
      <th>Anlegg</th>
      <th>Registrert</th>
      <th>Ledig plass</th>
      <th>Kontrakt</th>
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
                ($v['onsker_lader'] ? "AND p.har_lader = 1" : "") . "
                LIMIT 1
            ");
            $stmt->bind_param("i", $v['anlegg_id']);
        } else {
            $stmt = $conn->prepare("
                SELECT p.*, a.navn AS anlegg_navn
                FROM plasser p
                JOIN anlegg a ON p.anlegg_id = a.id
                WHERE a.borettslag_id = ? AND p.status = 'ledig' " .
                ($v['onsker_lader'] ? "AND p.har_lader = 1" : "") . "
                LIMIT 1
            ");
            $stmt->bind_param("i", $user['borettslag_id']);
        }
        $stmt->execute();
        $ledig_plass = $stmt->get_result()->fetch_assoc();
        $kontrakt = hent_kontrakt($conn, $v['id']);
    ?>
    <tr>
      <td><?= htmlspecialchars($v['navn']) ?></td>
      <td><?= htmlspecialchars($v['epost']) ?></td>
      <td><?= $v['onsker_lader'] ? '⚡ Ja' : 'Nei' ?></td>
      <td><?= $v['anlegg_navn'] ?? 'Første ledige' ?></td>
      <td><?= $v['registrert'] ?></td>
      <td style="color:<?= $ledig_plass ? 'green' : 'red' ?>;">
        <?= $ledig_plass ? '✅ ' . $ledig_plass['anlegg_navn'] . ' plass ' . $ledig_plass['nummer'] : '❌ Ingen ledig' ?>
      </td>
      <td>
        <?php if ($kontrakt): ?>
          <strong><?= ucfirst($kontrakt['status']) ?></strong><br>
          <?php if ($kontrakt['tilbudt_dato']): ?>
            <small>Tilbud sendt: <?= htmlspecialchars($kontrakt['tilbudt_dato']) ?></small><br>
          <?php endif; ?>
          <?php if ($kontrakt['signert_dato']): ?>
            <small>Signert: <?= htmlspecialchars($kontrakt['signert_dato']) ?></small><br>
          <?php endif; ?>
          <?php if (!empty($kontrakt['filnavn'])): ?>
            <a href="../kontrakter/<?= rawurlencode($kontrakt['filnavn']) ?>" target="_blank">📄 Åpne kontrakt</a>
          <?php endif; ?>
        <?php else: ?>
          <em>Ingen kontrakt</em>
        <?php endif; ?>
      </td>
      <td>
        <div class="handlinger">
          <?php if (!$kontrakt): ?>
            <?php if ($ledig_plass): ?>
              <form method="post" action="send_tilbud.php" class="inline-form">
                <input type="hidden" name="venteliste_id" value="<?= $v['id'] ?>">
                <input type="hidden" name="plass_id" value="<?= $ledig_plass['id'] ?>">
                <button type="submit">✉️ Send tilbud</button>
              </form>
            <?php else: ?>
              <button disabled>Ingen ledig</button>
            <?php endif; ?>
          <?php elseif ($kontrakt['status'] === 'tilbud'): ?>
            <form method="post" action="kontrakt_last_opp.php" enctype="multipart/form-data" class="inline-form">
              <input type="hidden" name="kontrakt_id" value="<?= $kontrakt['id'] ?>">
              <label>Signert kontrakt
                <input type="file" name="signert_kontrakt" accept="application/pdf,image/*" required>
              </label>
              <button type="submit">📥 Registrer mottak</button>
            </form>
          <?php elseif ($kontrakt['status'] === 'signert'): ?>
            <form method="post" action="venteliste_tildel.php" class="inline-form">
              <input type="hidden" name="kontrakt_id" value="<?= $kontrakt['id'] ?>">
              <button type="submit" onclick="return confirm('Godkjenn og tildel plass til <?= htmlspecialchars($v['navn']) ?>?')">✅ Godkjenn tildeling</button>
            </form>
          <?php else: ?>
            <em>Ferdigstilt</em>
          <?php endif; ?>
          <form method="post" action="fjern_venteliste.php" class="inline-form" onsubmit="return confirm('Fjern fra venteliste?');">
            <input type="hidden" name="venteliste_id" value="<?= $v['id'] ?>">
            <button type="submit">🗑 Fjern</button>
          </form>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<?php
$content = ob_get_clean();
include "admin_layout.php";
