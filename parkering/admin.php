<?php
session_start();
include_once '../../db.php'; // Adjust the path as needed

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['contract_file'])) {
    $spot_id = (int)$_POST['spot_id'];
    $user_id = (int)$_POST['user_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    $target_dir = "uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    $target_file = $target_dir . basename($_FILES["contract_file"]["name"]);
    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    if (in_array($file_type, ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']) && move_uploaded_file($_FILES["contract_file"]["tmp_name"], $target_file)) {
        try {
            $stmt = $conn->prepare("INSERT INTO contracts (user_id, spot_id, contract_file, start_date, end_date) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $spot_id, basename($_FILES["contract_file"]["name"]), $start_date, $end_date]);
            
            $update_stmt = $conn->prepare("UPDATE parking_spots SET is_available = FALSE WHERE spot_id = ?");
            $update_stmt->execute([$spot_id]);
            
            $success = "Kontrakt lastet opp og plass tildelt.";
        } catch(PDOException $e) {
            $error = "Feil: " . $e->getMessage();
        }
    } else {
        $error = "Ugyldig filtype eller feil ved opplasting.";
    }
}

try {
    $stmt = $conn->query("SELECT c.contract_id, c.contract_file, c.start_date, c.end_date, u.username, p.spot_number, f.name 
        FROM contracts c
        JOIN users u ON c.user_id = u.user_id
        JOIN parking_spots p ON c.spot_id = p.spot_id
        JOIN facilities f ON p.facility_id = f.facility_id");
    $contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $users = $conn->query("SELECT user_id, username FROM users")->fetchAll(PDO::FETCH_ASSOC);
    $spots = $conn->query("SELECT spot_id, spot_number, facility_id FROM parking_spots WHERE is_available = TRUE")->fetchAll(PDO::FETCH_ASSOC);

    $waiting_stmt = $conn->query("SELECT w.waiting_id, w.spot_type, u.username, f.name AS facility_name, p.spot_number 
        FROM waiting_list w
        JOIN users u ON w.user_id = u.user_id
        LEFT JOIN facilities f ON w.facility_id = f.facility_id
        LEFT JOIN parking_spots p ON w.spot_id = p.spot_id");
    $waiting_list = $waiting_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Feil: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="nb">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Borettslag Parkering</title>
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
                <a class="nav-link" href="logout.php">Logg ut</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>Administrasjon</h1>
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <h2>Legg til kontrakt</h2>
        <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
            <div class="mb-3">
                <label for="user_id" class="form-label">Bruker</label>
                <select class="form-control" id="user_id" name="user_id" required>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['user_id']; ?>"><?php echo htmlspecialchars($user['username']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="spot_id" class="form-label">Plass</label>
                <select class="form-control" id="spot_id" name="spot_id" required>
                    <?php foreach ($spots as $spot): ?>
                        <option value="<?php echo $spot['spot_id']; ?>">Plass <?php echo $spot['spot_number']; ?> (Anlegg <?php echo $spot['facility_id']; ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="contract_file" class="form-label">Kontraktfil (PDF, Word, bilde)</label>
                <input type="file" class="form-control" id="contract_file" name="contract_file" required>
            </div>
            <div class="mb-3">
                <label for="start_date" class="form-label">Startdato</label>
                <input type="date" class="form-control" id="start_date" name="start_date" required>
            </div>
            <div class="mb-3">
                <label for="end_date" class="form-label">Sluttdato</label>
                <input type="date" class="form-control" id="end_date" name="end_date" required>
            </div>
            <button type="submit" class="btn btn-pink">Last opp kontrakt</button>
        </form>

        <h2>Eksisterende kontrakter</h2>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Bruker</th>
                    <th>Plass</th>
                    <th>Anlegg</th>
                    <th>Fil</th>
                    <th>Startdato</th>
                    <th>Sluttdato</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contracts as $contract): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($contract['username']); ?></td>
                        <td><?php echo $contract['spot_number']; ?></td>
                        <td><?php echo htmlspecialchars($contract['name']); ?></td>
                        <td><a href="uploads/<?php echo htmlspecialchars($contract['contract_file']); ?>" download>Last ned</a></td>
                        <td><?php echo $contract['start_date']; ?></td>
                        <td><?php echo $contract['end_date']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2>Venteliste</h2>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Bruker</th>
                    <th>Anlegg</th>
                    <th>Plass</th>
                    <th>Plassertype</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($waiting_list as $waiting): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($waiting['username']); ?></td>
                        <td><?php echo $waiting['facility_name'] ? htmlspecialchars($waiting['facility_name']) : '-'; ?></td>
                        <td><?php echo $waiting['spot_number'] ? $waiting['spot_number'] : '-'; ?></td>
                        <td><?php echo $waiting['spot_type'] ? ($waiting['spot_type'] === 'ev_charger' ? 'El-lader' : 'Standard') : '-'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>