<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';


// User ID from session
$user_id = $_SESSION['user_id'];

// Fetch all measurements
$stmt = $conn->prepare("SELECT * FROM tren_measurements WHERE user_id = ? ORDER BY date DESC");
$stmt->bind_param("i", $user_id); // Bind user_id as an integer
$stmt->execute();
$result = $stmt->get_result();
$measurements = $result->fetch_all(MYSQLI_ASSOC); // Fetch all results as an associative array
$stmt->close();

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
                        <a href="edit_measurements.php?id=<?= $measurement['id']; ?>">Rediger</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</main>

<?php include_once '../includes/footer.php'; ?>
