<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Include database connection
include_once '../../db.php';

// User ID from session
$user_id = $_SESSION['user_id'];

// Check if the measurement ID is provided
if (!isset($_GET['id'])) {
    header('Location: view_all_measurements.php');
    exit();
}

$measurement_id = $_GET['id'];

// Fetch the specific measurement
$stmt = $pdo->prepare("SELECT * FROM tren_measurements WHERE id = :id AND user_id = :user_id");
$stmt->bindParam(':id', $measurement_id);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$measurement = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$measurement) {
    header('Location: view_all_measurements.php');
    exit();
}

// Handle form submission for editing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $weight = $_POST['weight'];
    $waist = $_POST['waist'];
    $widest = $_POST['widest'];
    $date = $_POST['date'];

    $stmt = $pdo->prepare("UPDATE tren_measurements SET weight = :weight, waist = :waist, widest = :widest, date = :date WHERE id = :id AND user_id = :user_id");
    $stmt->bindParam(':weight', $weight);
    $stmt->bindParam(':waist', $waist);
    $stmt->bindParam(':widest', $widest);
    $stmt->bindParam(':date', $date);
    $stmt->bindParam(':id', $measurement_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();

    header('Location: view_all_measurements.php');
}
?>

<?php include_once '../includes/header.php'; ?>

<main>
    <h2>Rediger Måling</h2>

    <form method="POST">
        <label for="weight">Vekt (kg):</label>
        <input type="number" step="0.1" name="weight" value="<?= htmlspecialchars($measurement['weight']); ?>" required>

        <label for="waist">Livvidde (cm):</label>
        <input type="number" step="0.1" name="waist" value="<?= htmlspecialchars($measurement['waist']); ?>" required>

        <label for="widest">Bredeste Vidde (cm):</label>
        <input type="number" step="0.1" name="widest" value="<?= htmlspecialchars($measurement['widest']); ?>" required>

        <label for="date">Dato:</label>
        <input type="date" name="date" value="<?= htmlspecialchars($measurement['date']); ?>" required>

        <button type="submit">Lagre Endringer</button>
    </form>
</main>

<?php include_once '../includes/footer.php'; ?>
