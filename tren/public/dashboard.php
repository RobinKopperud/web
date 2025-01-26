<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';
include_once '../includes/functions.php';
include_once '../features/predictions.php'; // Include predictions logic

// User ID from session
$user_id = $_SESSION['user_id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_measurements'])) {
        $weight = $_POST['weight'];
        $waist = $_POST['waist'];
        $widest = $_POST['widest'];
        $date = $_POST['date'];

        // Add measurement using a function from functions.php
        addMeasurement($conn, $user_id, $weight, $waist, $widest, $date);
        $_SESSION['success_message'] = "Målingene ble lagt til!";
    }

    if (isset($_POST['upload_photo']) && isset($_FILES['photo'])) {
        $upload_dir = '../uploads/';
        $file_name = uniqid() . "_" . basename($_FILES['photo']['name']);
        $target_path = $upload_dir . $file_name;
        $measurement_id = isset($_POST['measurement_id']) ? intval($_POST['measurement_id']) : null;
    
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
            // Upload photo with associated measurement_id
            $stmt = $conn->prepare("INSERT INTO tren_photos (user_id, file_path, measurement_id) VALUES (?, ?, ?)");
            $stmt->bind_param("isi", $user_id, $file_name, $measurement_id);
            $stmt->execute();
    
            $_SESSION['success_message'] = "Bilde ble lastet opp!";
        } else {
            $_SESSION['error_message'] = "Feil ved opplasting av bilde.";
        }
    }
      
}

// Fetch data for display
$latest_measurement = getLatestMeasurement($conn, $user_id);
$latest_photo = getLatestPhoto($conn, $user_id);
$first_photo = getFirstPhoto($conn, $user_id);
$last_photo = getLastPhoto($conn, $user_id);


// Fetch the number of days from the user's input or default to 30
$days = isset($_GET['days']) && is_numeric($_GET['days']) ? intval($_GET['days']) : 10;

// Fetch predicted measurements
$predicted_measurements = getPredictedMeasurements($conn, $user_id, $days);
?>

<?php include_once '../includes/header.php'; ?>

<main>
    <h2>Dashboard</h2>

    <!-- Section: Success/Error Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <p style="color: green;"><?= htmlspecialchars($_SESSION['success_message']); ?></p>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <p style="color: red;"><?= htmlspecialchars($_SESSION['error_message']); ?></p>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- Section: Add Measurements -->
    <section>
        <h3>Legg til Målinger</h3>
        <form method="POST" action="dashboard.php">
            <label for="weight">Vekt (kg):</label>
            <input type="number" step="1" name="weight" required>

            <label for="waist">Livvidde (cm):</label>
            <input type="number" step="0.1" name="waist">

            <label for="widest">Bredeste Vidde (cm):</label>
            <input type="number" step="0.1" name="widest" required>

            <label for="date">Dato:</label>
            <input type="date" name="date" required>

            <button type="submit" name="add_measurements">Lagre Målinger</button>
        </form>
    </section>

    <!-- Section: Photos -->
    <section>
        <h3>Last opp Bilde</h3>
        <form method="POST" enctype="multipart/form-data" action="dashboard.php">
            <label for="photo">Velg bilde:</label>
            <input type="file" name="photo" required>

            <label for="measurement_id">Knytt til Måling:</label>
            <select name="measurement_id">
                <option value="">Velg Måling</option>
                <?php
                // Fetch all measurements for the user
                $stmt = $conn->prepare("SELECT id, date FROM tren_measurements WHERE user_id = ? ORDER BY date DESC");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($measurement = $result->fetch_assoc()):
                ?>
                    <option value="<?= htmlspecialchars($measurement['id']); ?>">
                        <?= htmlspecialchars($measurement['date']); ?>
                    </option>
                <?php endwhile; ?>
                <?php $stmt->close(); ?>
            </select>

            <button type="submit" name="upload_photo">Last Opp</button>
        </form>
    </section>


    <!-- Section: Predictions -->
    <section>
        <h3>Prediksjoner for Fremtidige Målinger (<?= htmlspecialchars($days); ?> dager)</h3>
        <form method="GET" action="dashboard.php">
            <label for="days">Antall dager for prediksjon:</label>
            <input type="number" name="days" id="days" min="1" value="<?= htmlspecialchars($days); ?>" required>
            <button type="submit">Oppdater</button>
        </form>

        <?php if (!empty($predicted_measurements)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Dato</th>
                        <th>Forventet Vekt (kg)</th>
                        <th>Forventet Livvidde (cm)</th>
                        <th>Forventet Bredeste Vidde (cm)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($predicted_measurements as $prediction): ?>
                        <tr>
                            <td><?= htmlspecialchars($prediction['date']); ?></td>
                            <td><?= htmlspecialchars($prediction['weight']); ?></td>
                            <td><?= htmlspecialchars($prediction['waist']); ?></td>
                            <td><?= htmlspecialchars($prediction['widest']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Ikke nok data til å lage prediksjoner.</p>
        <?php endif; ?>
    </section>
    <!-- Section: Side-by-Side Photos -->
    <section>
        <h3>Sammenlign Første og Siste Bilde</h3>
        <div style="display: flex; justify-content: space-around; align-items: center; gap: 20px;">
            <div>
                <h4>Første Bilde</h4>
                <?php if ($first_photo): ?>
                    <img src="../uploads/<?= htmlspecialchars($first_photo); ?>" alt="Første Bilde" style="max-width: 200px;">
                <?php else: ?>
                    <p>Ingen første bilde funnet.</p>
                <?php endif; ?>
            </div>
            <div>
                <h4>Siste Bilde</h4>
                <?php if ($last_photo): ?>
                    <img src="../uploads/<?= htmlspecialchars($last_photo); ?>" alt="Siste Bilde" style="max-width: 200px;">
                <?php else: ?>
                    <p>Ingen siste bilde funnet.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

</main>

<?php include_once '../includes/footer.php'; ?>
