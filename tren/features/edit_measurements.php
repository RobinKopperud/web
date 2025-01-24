<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';


// User ID from session
$user_id = $_SESSION['user_id'];

// Check if the measurement ID is provided
if (!isset($_GET['id'])) {
    header('Location: view_all_measurements.php');
    exit();
}

$measurement_id = $_GET['id'];

// Fetch the specific measurement
$stmt = $conn->prepare("SELECT * FROM tren_measurements WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $measurement_id, $user_id); // Bind id and user_id as integers
$stmt->execute();
$result = $stmt->get_result();
$measurement = $result->fetch_assoc(); // Fetch the result as an associative array
$stmt->close();


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

    $stmt = $conn->prepare("UPDATE tren_measurements SET weight = ?, waist = ?, widest = ?, date = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ddssii", $weight, $waist, $widest, $date, $measurement_id, $user_id); // Bind parameters
    $stmt->execute();
    $stmt->close();


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
