<?php
session_start();
require '../../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$facility_id = isset($_GET['facility_id']) ? (int)$_GET['facility_id'] : 0;
$facilities = [];
$spots = [];
$facility = null;

$facilities_result = $conn->query("SELECT facility_id, name FROM facilities");
if ($facilities_result) {
    while ($row = $facilities_result->fetch_assoc()) {
        $facilities[] = $row;
    }
}

// Fetch waiting list counts for facility
$facility_waiting_count = 0;
if ($facility_id) {
    $facility_waiting_result = $conn->query("SELECT COUNT(*) as count FROM waiting_list WHERE facility_id = $facility_id AND spot_id IS NULL AND spot_type IS NULL");
    if ($facility_waiting_result) {
        $facility_waiting_count = $facility_waiting_result->fetch_assoc()['count'];
    }
}

// Fetch waiting list counts for spot types
$spot_type_counts = ['standard' => 0, 'ev_charger' => 0];
if ($facility_id) {
    $spot_type_result = $conn->query("SELECT spot_type, COUNT(*) as count FROM waiting_list WHERE facility_id = $facility_id AND spot_type IS NOT NULL GROUP BY spot_type");
    if ($spot_type_result) {
        while ($row = $spot_type_result->fetch_assoc()) {
            $spot_type_counts[$row['spot_type']] = $row['count'];
        }
    }
}

if ($facility_id) {
    $result = $conn->query("SELECT spot_id, spot_number, spot_type, price, is_available,
        (SELECT COUNT(*) FROM waiting_list WHERE spot_id = parking_spots.spot_id) as waiting_count
        FROM parking_spots WHERE facility_id = $facility_id");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $spots[] = $row;
        }
    }

    $facility_result = $conn->query("SELECT name FROM facilities WHERE facility_id = $facility_id");
    if ($facility_result) {
        $facility = $facility_result->fetch_assoc();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="nb">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parkeringsplasser - Borettslag Parkering</title>
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
        <h1>Parkeringsplasser</h1>
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <h2>Velg anlegg</h2>
        <form method="GET" class="mb-4">
            <select name="facility_id" class="form-select d-inline-block w-auto" onchange="this.form.submit()">
                <option value="">Velg et anlegg</option>
                <?php foreach ($facilities as $fac): ?>
                    <option value="<?php echo $fac['facility_id']; ?>" <?php echo $facility_id == $fac['facility_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($fac['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <?php if ($facility_id && $facility): ?>
            <h2>Plasser i <?php echo htmlspecialchars($facility['name']); ?></h2>
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card border-pink">
                        <div class="card-body">
                            <h5 class="card-title">Venteliste for hele anlegget</h5>
                            <p class="card-text">Bli med i ventelisten for <?php echo htmlspecialchars($facility['name']); ?> (<?php echo $facility_waiting_count; ?> på venteliste).</p>
                            <form method="POST" action="add_to_waiting_list.php">
                                <input type="hidden" name="facility_id" value="<?php echo $facility_id; ?>">
                                <button type="submit" class="btn btn-pink">Sett deg på venteliste</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-pink">
                        <div class="card-body">
                            <h5 class="card-title">Venteliste for plassertype</h5>
                            <p class="card-text">Velg en plassertype for <?php echo htmlspecialchars($facility['name']); ?>.</p>
                            <form method="POST" action="add_to_waiting_list.php">
                                <input type="hidden" name="facility_id" value="<?php echo $facility_id; ?>">
                                <select name="spot_type" class="form-select mb-2" required>
                                    <option value="standard">Standard plass (<?php echo $spot_type_counts['standard']; ?> på venteliste)</option>
                                    <option value="ev_charger">Plass med el-lader (<?php echo $spot_type_counts['ev_charger']; ?> på venteliste)</option>
                                </select>
                                <button type="submit" class="btn btn-pink">Sett deg på venteliste</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <h2>Enkelte plasser</h2>
            <div class="row">
                <?php foreach ($spots as $spot): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card border-gray">
                            <div class="card-body">
                                <h5 class="card-title">Plass <?php echo htmlspecialchars($spot['spot_number']); ?></h5>
                                <p class="card-text">
                                    <strong>Type:</strong> <?php echo $spot['spot_type'] === 'ev_charger' ? 'El-lader' : 'Standard'; ?><br>
                                    <strong>Pris:</strong> <?php echo number_format($spot['price'], 2); ?> NOK<br>
                                    <strong>Status:</strong> <?php echo $spot['is_available'] ? 'Ledig' : 'Opptatt'; ?><br>
                                    <strong>Venteliste:</strong> <?php echo $spot['waiting_count']; ?> på venteliste
                                </p>
                                <?php if ($spot['is_available']): ?>
                                    <form method="POST" action="add_to_waiting_list.php">
                                        <input type="hidden" name="spot_id" value="<?php echo $spot['spot_id']; ?>">
                                        <button type="submit" class="btn btn-pink">Sett deg på venteliste</button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($_SESSION['role'] === 'admin'): ?>
                                    <?php
                                    $contract_result = $conn->query("SELECT contract_file FROM contracts WHERE spot_id = " . (int)$spot['spot_id']);
                                    $contract = $contract_result ? $contract_result->fetch_assoc() : null;
                                    if ($contract && $contract['contract_file']): ?>
                                        <a href="Uploads/<?php echo htmlspecialchars($contract['contract_file']); ?>" download class="btn btn-outline-pink mt-2">Last ned kontrakt</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>