<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

$gruppekode = $_GET['gruppekode'] ?? '';
$spiller_id = $_GET['spiller_id'] ?? '';

// Hent gruppe og spillere
$stmt = $conn->prepare("
    SELECT g.gruppe_id, s.spiller_id, s.navn, s.saldo
    FROM BJGrupper g
    JOIN BJSpillere s ON s.gruppe_id = g.gruppe_id
    WHERE g.gruppekode = ?
");
$stmt->bind_param("s", $gruppekode);
$stmt->execute();
$result = $stmt->get_result();

$spillere = [];
$gruppe_id = null;

while ($row = $result->fetch_assoc()) {
    $spillere[] = $row;
    $gruppe_id = $row['gruppe_id'];
}

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <title>Blackjackbord – Gruppe <?php echo htmlspecialchars($gruppekode); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: url('img/bg.png') no-repeat center center fixed;
            background-size: contain;
            height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .bord {
            position: relative;
            width: 800px;
            height: 600px;
        }

        .spiller {
            position: absolute;
            background: rgba(0, 0, 0, 0.7);
            color: #fff;
            padding: 6px 12px;
            border-radius: 10px;
            font-weight: bold;
        }

        /* 4 faste posisjoner, kan utvides */
        .pos1 { top: 80%; left: 20%; transform: translate(-50%, -50%); }
        .pos2 { top: 80%; left: 50%; transform: translate(-50%, -50%); }
        .pos3 { top: 80%; left: 80%; transform: translate(-50%, -50%); }
        .pos4 { top: 10%; left: 50%; transform: translate(-50%, -50%); }
    </style>
</head>
<body>
    <div class="bord">
    <?php
        $antall_spillere = count($spillere); // Legg dette før loopen
        // Sjekk om denne spilleren har et ventende forslag
        $har_ventende = false;
        $stmt = $conn->prepare("SELECT 1 FROM BJTransaksjoner WHERE maal_spiller_id = ? AND status = 'ventende' LIMIT 1");
        $stmt->bind_param("i", $spiller_id);
        $stmt->execute();
        $stmt->store_result();
        $har_ventende = $stmt->num_rows > 0;
        $stmt->close();


        foreach ($spillere as $index => $spiller) {
            $posClass = "pos" . ($index + 1);
            $er_meg = $spiller['spiller_id'] == $spiller_id;

            echo "<div class='spiller $posClass'>";
            echo htmlspecialchars($spiller['navn']) . "<br>";
            echo "<small>Saldo: " . number_format($spiller['saldo'], 2, ',', ' ') . " kr</small>";

            if ($er_meg) {
                if ($antall_spillere < 2) {
                    echo "<p style='margin-top:10px; color: #ff0;'>⚠️ Minst 2 spillere kreves for å foreslå saldo.</p>";
                } elseif ($har_ventende) {
                    echo "<p style='margin-top:10px; color: orange;'>⏳ Du har allerede et forslag som venter på godkjenning.</p>";
                    echo "<form action='/php/cancel_proposal.php' method='post' style='margin-top: 5px;'>
                            <input type='hidden' name='spiller_id' value='{$spiller_id}'>
                            <input type='hidden' name='gruppekode' value='" . htmlspecialchars($gruppekode) . "'>
                            <button type='submit' style='margin-top:5px;'>❌ Avbryt forslag</button>
                        </form>";

                } else {
                    echo "<form action='Web/blackjacj/php/propose_sum.php' method='post' style='margin-top: 10px;'>
                            <input type='hidden' name='gruppe_id' value='{$gruppe_id}'>
                            <input type='hidden' name='spiller_id' value='{$spiller['spiller_id']}'>
                            <input type='hidden' name='gruppekode' value='" . htmlspecialchars($gruppekode) . "'>
                            <input type='number' name='belop' step='0.01' required placeholder='Ny saldo'>
                            <input type='submit' value='Foreslå'>
                          </form>";
                }
            }

            echo "</div>";
        }
        ?>

    </div>
    <!-- Popup for forslag -->
    <div id="proposal-popup" style="display:none; position:fixed; top:30%; left:50%; transform:translate(-50%,-50%);
    background:white; border:2px solid black; padding:20px; z-index:999;">
        <p id="proposal-text"></p>
        <form id="accept-form" method="post" action="Web/blackjacj/php/respond_to_proposal.php">
            <input type="hidden" name="transaksjon_id" id="transaksjon_id_accept">
            <input type="hidden" name="godkjent" value="1">
            <button type="submit">✅ Godta</button>
        </form>
        <form id="reject-form" method="post" action="Web/blackjacj/php/respond_to_proposal.php">
            <input type="hidden" name="transaksjon_id" id="transaksjon_id_reject">
            <input type="hidden" name="godkjent" value="0">
            <button type="submit">❌ Avslå</button>
        </form>
    </div>
    <script src="Web/blackjacjjs/popup.js" defer></script>

</body>
</html>
