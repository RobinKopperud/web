<?php
// Start session
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once '../../db.php'; // Correct path

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php?error=Du må være admin for å få tilgang.");
    exit;
}

$error = null;
$success = null;

// Handle contract upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['contract_file'])) {
    $user_id = $conn->real_escape_string($_POST['user_id']);
    $spot_id = $conn->real_escape_string($_POST['spot_id']);
    $start_date = $conn->real_escape_string($_POST['start_date']);
    $end_date = $conn->real_escape_string($_POST['end_date']);
    $contract_file = $_FILES['contract_file'];

    if ($contract_file['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        $file_name = time() . '_' . basename($contract_file['name']);
        $upload_path = $upload_dir . $file_name;

        if (move_uploaded_file($contract_file['tmp_name'], $upload_path)) {
            $query = "INSERT INTO contracts (user_id, spot_id, contract_file, start_date, end_date) 
                      VALUES ('$user_id', '$spot_id', '$file_name', '$start_date', '$end_date')";
            if ($conn->query($query)) {
                $success = "Kontrakt lastet opp.";
                // Update spot availability
                $conn->query("UPDATE parking_spots SET is_available = FALSE WHERE spot_id = '$spot_id'");
            } else {
                $error = "Feil ved lagring av kontrakt: " . $conn->error;
            }
        } else {
            $error = "Feil ved opplasting av fil.";
        }
    } else {
        $error = "Filopplasting feilet: " . $contract_file['error'];
    }
}

// Fetch users
$users_result = $conn->query("SELECT user_id, username, email, role FROM users");
if ($users_result === false) {
    $error = "Feil ved henting av brukere: " . $conn->error;
} else {
    $users = [];
    while ($row = $users_result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Fetch contracts
$contracts_result = $conn->query("SELECT c.contract_id, c.contract_file, c.start_date, c.end_date, p.spot_number, f.name, u.username 
    FROM contracts c
    JOIN parking_spots p ON c.spot_id = p.spot_id
    JOIN facilities f ON p.facility_id = f.facility_id
    JOIN users u ON c.user_id = u.user_id");
if ($contracts_result === false) {
    $error = "Feil ved henting av kontrakter: " . $conn->error;
} else {
    $contracts = [];
    while ($row = $contracts_result->fetch_assoc()) {
        $contracts[] = $row;
    }
}

// Fetch waiting list
$waiting_result = $conn->query("SELECT w.waiting_id, u.username, f.name AS facility_name, p.spot_number, w.spot_type 
    FROM waiting_list w
    LEFT JOIN users u ON w.user_id = u.user_id
    LEFT JOIN facilities f ON w.facility_id = f.facility_id
    LEFT JOIN parking_spots p ON w.spot_id = p.spot_id");
if ($waiting_result === false) {
    $error = "Feil ved henting av venteliste: " . $conn->error;
} else {
    $waiting_list = [];
    while ($row = $waiting_result->fetch_assoc()) {
        $waiting_list[] = $row;
    }
}

// Fetch available spots for contract form
$spots_result = $conn->query("SELECT spot_id, spot_number, facility_id FROM parking_spots WHERE is_available = TRUE");
if ($spots_result === false) {
    $error = "Feil ved henting av plasser: " . $conn->error;
} else {
    $spots = [];
    while ($row = $spots_result->fetch_assoc()) {
        $spots[] = $row;
    }
}

$conn->close();
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
    <?php include 'nav.php'; ?>

    <div class="container mt-4">
        <h1>Adminpanel</h1>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <h2>Last opp kontrakt</h2>
        <form method="POST" enctype="multipart/form-data" class="mb-4">
            <div class="mb-3">
                <label for="user_id" class="form-label">Bruker</label>
                <select name="user_id" class="form-select" required>
                    <option value="">Velg bruker</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['user_id']; ?>"><?php echo htmlspecialchars($user['username']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="spot_id" class="form-label">Plass</label>
                <select name="spot_id" class="form-select" required>
                    <option value="">Velg plass</option>
                    <?php foreach ($spots as $spot): ?>
                        <option value="<?php echo $spot['spot_id']; ?>">Plass <?php echo $spot['spot_number']; ?> (Anlegg ID: <?php echo $spot['facility_id']; ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="start_date" class="form-label">Startdato</label>
                <input type="date" class="form-control" name="start_date" required>
            </div>
            <div class="mb-3">
                <label for="end_date" class="form-label">Sluttdato</label>
                <input type="date" class="form-control" name="end_date" required>
            </div>
            <div class="mb-3">
                <label for="contract_file" class="form-label">Kontraktfil</label>
                <input type="file" class="form-control" name="contract_file" required>
            </div>
            <button type="submit" class="btn btn-pink">Last opp</button>
        </form>

        <h2>Brukere</h2>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Brukernavn</th>
                    <th>E-post</th>
                    <th>Rolle</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['role']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2>Kontrakter</h2>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Bruker</th>
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
                        <td><?php echo htmlspecialchars($contract['username']); ?></td>
                        <td><?php echo htmlspecialchars($contract['spot_number']); ?></td>
                        <td><?php echo htmlspecialchars($contract['name']); ?></td>
                        <td><a href="Uploads/<?php echo htmlspecialchars($contract['contract_file']); ?>" download>Last ned</a></td>
                        <td><?php echo htmlspecialchars($contract['start_date']); ?></td>
                        <td><?php echo htmlspecialchars($contract['end_date']); ?></td>
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
                        <td><?php echo htmlspecialchars($waiting['facility_name'] ?: 'Ikke spesifisert'); ?></td>
                        <td><?php echo htmlspecialchars($waiting['spot_number'] ?: 'Ikke spesifisert'); ?></td>
                        <td><?php echo htmlspecialchars($waiting['spot_type'] ?: 'Ikke spesifisert'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>