<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$facility_id = isset($_GET['facility_id']) ? (int)$_GET['facility_id'] : 0;

$result = $conn->query("SELECT spot_id, spot_number, spot_type, price, is_available 
    FROM parking_spots WHERE facility_id = $facility_id");
if ($result === false) {
    die("Query failed: " . $conn->error);
}
$spots = [];
while ($row = $result->fetch_assoc()) {
    $spots[] = $row;
}

$facility_result = $conn->query("SELECT name FROM facilities WHERE facility_id = $facility_id");
$facility = $facility_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="nb">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plasser i <?php echo htmlspecialchars($facility['name']); ?></title>
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
        <h1>Plasser i <?php echo htmlspecialchars($facility['name']); ?></h1>
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>
        
        <h2>Meld deg på venteliste for anlegget</h2>
        <form method="POST" action="add_to_waiting_list.php" class="mb-4">
            <input type="hidden" name="facility_id" value="<?php echo $facility_id; ?>">
            <button type="submit" class="btn btn-pink">Venteliste for hele anlegget</button>
        </form>
        <form method="POST" action="add_to_waiting_list.php" class="mb-4">
            <input type="hidden" name="facility_id" value="<?php echo $facility_id; ?>">
            <select name="spot_type" class="form-select d-inline-block w-auto" required>
                <option value="standard">Standard plass</option>
                <option value="ev_charger">Plass med el-lader</option>
            </select>
            <button type="submit" class="btn btn-pink">Venteliste for plassertype</button>
        </form>

        <h2>Enkelte plasser</h2>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Plassnummer</th>
                    <th>Type</th>
                    <th>Pris</th>
                    <th>Status</th>
                    <th>Handling</th>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <th>Kontrakt</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($spots as $spot): ?>
                    <tr>
                        <td><?php echo $spot['spot_number']; ?></td>
                        <td><?php echo $spot['spot_type'] === 'ev_charger' ? 'El-lader' : 'Standard'; ?></td>
                        <td><?php echo number_format($spot['price'], 2); ?> NOK</td>
                        <td><?php echo $spot['is_available'] ? 'Ledig' : 'Opptatt'; ?></td>
                        <td>
                            <?php if ($spot['is_available']): ?>
                                <form method="POST" action="add_to_waiting_list.php">
                                    <input type="hidden" name="spot_id" value="<?php echo $spot['spot_id']; ?>">
                                    <button type="submit" class="btn btn-pink">Sett på venteliste</button>
                                </form>
                            <?php endif; ?>
                        </td>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <td>
                                <?php
                                $contract_result = $conn->query("SELECT contract_file FROM contracts WHERE spot_id = " . (int)$spot['spot_id']);
                                $contract = $contract_result->fetch_assoc();
                                if ($contract && $contract['contract_file']): ?>
                                    <a href="uploads/<?php echo htmlspecialchars($contract['contract_file']); ?>" download>Last ned</a>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
<?php $conn->close(); ?>