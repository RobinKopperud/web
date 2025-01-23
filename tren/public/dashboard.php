<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Include database connection
include_once '../../../db.php';

// User ID from session
$user_id = $_SESSION['user_id'];
?>

<?php include_once '../includes/header.php'; ?>

<main>
    <h2>Dashboard</h2>

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
        <?php
        $stmt = $pdo->prepare("SELECT * FROM tren_measurements WHERE user_id = :user_id ORDER BY date DESC LIMIT 1");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $latest_measurement = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($latest_measurement) {
            echo "<p><strong>Vekt:</strong> {$latest_measurement['weight']} kg</p>";
            echo "<p><strong>Livvidde:</strong> {$latest_measurement['waist']} cm</p>";
            echo "<p><strong>Bredeste Vidde:</strong> {$latest_measurement['widest']} cm</p>";
        } else {
            echo "<p>Ingen målinger funnet. Legg til dine første målinger!</p>";
        }
        ?>
    </section>

    <!-- Display Latest Photo -->
    <section>
        <h3>Ditt Nyeste Bilde</h3>
        <?php
        $stmt = $pdo->prepare("SELECT * FROM tren_photos WHERE user_id = :user_id ORDER BY uploaded_at DESC LIMIT 1");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $latest_photo = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($latest_photo) {
            echo "<img src='../../uploads/{$latest_photo['file_path']}' alt='Nyeste Bilde' style='max-width: 200px;'>";
        } else {
            echo "<p>Ingen bilder funnet. Last opp ditt første bilde!</p>";
        }
        ?>
    </section>
</main>

<?php include_once '../includes/footer.php'; ?>

<?php
// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add Measurements
    if (isset($_POST['add_measurements'])) {
        $weight = $_POST['weight'];
        $waist = $_POST['waist'];
        $widest = $_POST['widest'];
        $date = $_POST['date'];

        $stmt = $pdo->prepare("INSERT INTO tren_measurements (user_id, weight, waist, widest, date) VALUES (:user_id, :weight, :waist, :widest, :date)");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':weight', $weight);
        $stmt->bindParam(':waist', $waist);
        $stmt->bindParam(':widest', $widest);
        $stmt->bindParam(':date', $date);
        
        $stmt->execute();
        header("Location: dashboard.php");
    }

    // Upload Photo
    if (isset($_POST['upload_photo']) && isset($_FILES['photo'])) {
        $upload_dir = '../uploads/';
        $file_name = uniqid() . "_" . basename($_FILES['photo']['name']);
        $target_path = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
            $stmt = $pdo->prepare("INSERT INTO tren_photos (user_id, file_path) VALUES (:user_id, :file_path)");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':file_path', $file_name);
            $stmt->execute();
            header("Location: dashboard.php");
        } else {
            echo "<p>Feil ved opplasting av bilde.</p>";
        }
    }
}
?>
