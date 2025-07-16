<?php

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once '../../db.php'; // Correct path

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$error = null;

// Fetch contracts
$user_id = $conn->real_escape_string($_SESSION['user_id']);
$contracts_result = $conn->query("SELECT c.contract_id, c.contract_file, c.start_date, c.end_date, p.spot_number, f.name 
    FROM contracts c
    JOIN parking_spots p ON c.spot_id = p.spot_id
    JOIN facilities f ON p.facility_id = f.facility_id
    WHERE c.user_id = '$user_id'");
if ($contracts_result === false) {
    $error = "Database error (kontrakter): " . $conn->error;
} else {
    $contracts = [];
    while ($row = $contracts_result->fetch_assoc()) {
        $contracts[] = $row;
    }
}

// Fetch waiting list entries with position
$waiting_result = $conn->query("SELECT w.waiting_id, w.created_at, f.name AS facility_name, p.spot_number, w.spot_type,
    (SELECT COUNT(*) + 1 FROM waiting_list w2 WHERE w2.facility_id = w.facility_id 
        AND (w2.spot_id = w.spot_id OR (w2.spot_id IS NULL AND w.spot_id IS NULL)) 
        AND (w2.spot_type = w.spot_type OR (w2.spot_type IS NULL AND w.spot_type IS NULL)) 
        AND w2.created_at < w.created_at) AS position
    FROM waiting_list w
    LEFT JOIN facilities f ON w.facility_id = f.facility_id
    LEFT JOIN parking_spots p ON w.spot_id = p.spot_id
    WHERE w.user_id = '$user_id'
    ORDER BY w.created_at");
if ($waiting_result === false) {
    $error = "Database error (venteliste): " . $conn->error;
} else {
    $waiting_list = [];
    while ($row = $waiting_result->fetch_assoc()) {
        $waiting_list[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="nb">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Min side - Borettslag Parkering</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .table-responsive {
            overflow-x: hidden;
        }
        .table th, .table td {
            font-size: 0.9rem;
            padding: 0.5rem;
            white-space: nowrap;
        }
        @media (max-width: 576px) {
            .table th, .table td {
                font-size: 0.75rem;
                padding: 0.3rem;
            }
            .table .hide-on-mobile {
                display: none;
            }
            .btn {
                font-size: 0.75rem;
                padding: 0.2rem 0.4rem;
            }
            .table td, .table th {
                white-space: normal;
                word-wrap: break-word;
                max-width: 100px;
            }
            .table a, .table button {
                display: inline-block;
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>

    <div class="container mt-4">
        <h1>Min side</h1>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <h2>Mine kontrakter</h2>
        <?php if (empty($contracts)): ?>
            <p>Du har ingen kontrakter.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Plass</th>
                            <th>Anlegg</th>
                            <th>Kontraktfil</th>
                            <th class="hide-on-mobile">Startdato</th>
                            <th class="hide-on-mobile">Sluttdato</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contracts as $contract): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($contract['spot_number']); ?></td>
                                <td><?php echo htmlspecialchars($contract['name']); ?></td>
                                <td><a href="Uploads/<?php echo htmlspecialchars($contract['contract_file']); ?>" download>Last ned</a></td>
                                <td class="hide-on-mobile"><?php echo htmlspecialchars($contract['start_date']); ?></td>
                                <td class="hide-on-mobile"><?php echo htmlspecialchars($contract['end_date']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <h2>Min venteliste</h2>
        <?php if (empty($waiting_list)): ?>
            <p>Du er ikke på noen venteliste.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Posisjon</th>
                            <th>Anlegg</th>
                            <th>Plass</th>
                            <th>Plassertype</th>
                            <th class="hide-on-mobile">Påmeldt</th>
                            <th>Handling</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($waiting_list as $waiting): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($waiting['position']); ?></td>
                                <td><?php echo htmlspecialchars($waiting['facility_name'] ?: 'Ikke spesifisert'); ?></td>
                                <td><?php echo htmlspecialchars($waiting['spot_number'] ?: 'Ikke spesifisert'); ?></td>
                                <td><?php echo htmlspecialchars($waiting['spot_type'] ?: 'Ikke spesifisert'); ?></td>
                                <td class="hide-on-mobile"><?php echo htmlspecialchars($waiting['created_at']); ?></td>
                                <td>
                                    <form method="POST" action="remove_from_waiting_list.php">
                                        <input type="hidden" name="waiting_id" value="<?php echo $waiting['waiting_id']; ?>">
                                        <button type="submit" class="btn btn-pink">Gå av ventelisten</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>