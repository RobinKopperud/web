<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Include database connection
include_once '../../../../db.php';

// User ID from session
$user_id = $_SESSION['user_id'];

// Fetch all measurements
$stmt = $pdo->prepare("SELECT * FROM tren_measurements WHERE user_id = :user_id ORDER BY date DESC");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$measurements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include_once '../includes/header.php'; ?>

<main>
    <h2>Alle Målinger</h2>

    <table border="1">
        <thead>
            <tr>
                <th>Dato</th>
                <th>Vekt (kg)</th>
                <th>Livvidde (cm)</th>
                <th>Bredeste Vidde (cm)</th>
                <th>Handlinger</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($measurements as $measurement): ?>
                <tr>
                    <td><?= htmlspecialchars($measurement['date']); ?></td>
                    <td><?= htmlspecialchars($measurement['weight']); ?></td>
                    <td><?= htmlspecialchars($measurement['waist']); ?></td>
                    <td><?= htmlspecialchars($measurement['widest']); ?></td>
                    <td>
                        <a href="edit_measurement.php?id=<?= $measurement['id']; ?>">Rediger</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</main>

<?php include_once '../includes/footer.php'; ?>
