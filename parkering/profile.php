<?php
session_start();
require '../../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

try {
    $stmt = $conn->prepare("SELECT c.contract_id, c.contract_file, c.start_date, c.end_date, p.spot_number, f.name 
        FROM contracts c
        JOIN parking_spots p ON c.spot_id = p.spot_id
        JOIN facilities f ON p.facility_id = f.facility_id
        WHERE c.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Feil: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="nb">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Min side - Borettslag Parkering</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-gray">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Borettslag Parkering</a>
            <div class="navbar-nav">
                <a class="nav-link" href="index.php">Hjem</a>
                <a class="nav-link" href="parking.php">Parkeringsplasser</a>
                <a class="nav-link" href="profile.php">Min side</a>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a class="nav-link" href="admin.php">Admin</a>
                <?php endif; ?>
                <a class="nav-link" href="logout.php">Logg ut</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>Min side</h1>
        <h2>Mine kontrakter</h2>
        <?php if (empty($contracts)): ?>
            <p>Du har ingen kontrakter.</p>
        <?php else: ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Plass</th>
                        <th>Anlegg</th>
                        <th>Kontraktfil</th>
                        <th>Startdato</th>
                        <th>Sluttdato</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contracts as $contract): ?>
                        <tr>
                            <td><?php echo $contract['spot_number']; ?></td>
                            <td><?php echo htmlspecialchars($contract['name']); ?></td>
                            <td><a href="uploads/<?php echo htmlspecialchars($contract['contract_file']); ?>" download>Last ned</a></td>
                            <td><?php echo $contract['start_date']; ?></td>
                            <td><?php echo $contract['end_date']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>