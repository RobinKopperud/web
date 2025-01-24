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

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
            // Upload photo using a function from functions.php
            uploadPhoto($conn, $user_id, $file_name);
            $_SESSION['success_message'] = "Bilde ble lastet opp!";
        } else {
            $_SESSION['error_message'] = "Feil ved opplasting av bilde.";
        }
    }
}

// Fetch data for display
$latest_measurement = getLatestMeasurement($conn, $user_id);
$latest_photo = getLatestPhoto($conn, $user_id);

// Fetch the number of days from the user's input or default to 30
$days = isset($_GET['days']) && is_numeric($_GET['days']) ? intval($_GET['days']) : 30;

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
            <input type="number" step="0.1" name="weight" required>

            <label for="waist">Livvidde (cm):</label>
            <input type="number" step="0.1" name="waist" required>

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
</main>

<?php include_once '../includes/footer.php'; ?>
