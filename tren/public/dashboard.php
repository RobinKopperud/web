<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';
include_once '../includes/functions.php'; // Include the functions file

// User ID from session
$user_id = $_SESSION['user_id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_measurements'])) {
        $weight = $_POST['weight'];
        $waist = $_POST['waist'];
        $widest = $_POST['widest'];
        $date = $_POST['date'];

        addMeasurement($conn, $user_id, $weight, $waist, $widest, $date);
        $_SESSION['success_message'] = "Målingene ble lagt til!";
    }

    if (isset($_POST['upload_photo']) && isset($_FILES['photo'])) {
        $upload_dir = '../uploads/';
        $file_name = uniqid() . "_" . basename($_FILES['photo']['name']);
        $target_path = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
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

    <!-- Display Latest Measurements -->
    <section>
        <h3>Dine Nyeste Målinger</h3>
        <?php if ($latest_measurement): ?>
            <p><strong>Vekt:</strong> <?= htmlspecialchars($latest_measurement['weight']); ?> kg</p>
            <p><strong>Livvidde:</strong> <?= htmlspecialchars($latest_measurement['waist']); ?> cm</p>
            <p><strong>Bredeste Vidde:</strong> <?= htmlspecialchars($latest_measurement['widest']); ?> cm</p>
        <?php else: ?>
            <p>Ingen målinger funnet. Legg til dine første målinger!</p>
        <?php endif; ?>
    </section>

    <!-- Display Latest Photo -->
    <section>
        <h3>Ditt Nyeste Bilde</h3>
        <?php if ($latest_photo): ?>
            <img src="../uploads/<?= htmlspecialchars($latest_photo['file_path']); ?>" alt="Nyeste Bilde" style="max-width: 200px;">
        <?php else: ?>
            <p>Ingen bilder funnet. Last opp ditt første bilde!</p>
        <?php endif; ?>
    </section>
</main>

<?php include_once '../includes/footer.php'; ?>
