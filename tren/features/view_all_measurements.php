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

        <table>
        <thead>
            <tr>
                <th>Dato</th>
                <th>Vekt (kg)</th>
                <th>Livvidde (cm)</th>
                <th>Bredeste Vidde (cm)</th>
                <th>Bilde</th>
                <th>Handlinger</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $stmt = $conn->prepare("
                SELECT m.id, m.date, m.weight, m.waist, m.widest, p.file_path 
                FROM tren_measurements m
                LEFT JOIN tren_photos p ON m.id = p.measurement_id
                WHERE m.user_id = ?
                ORDER BY m.date DESC
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($measurement = $result->fetch_assoc()):
            ?>
                <tr>
                    <td><?= htmlspecialchars($measurement['date']); ?></td>
                    <td><?= htmlspecialchars($measurement['weight']); ?></td>
                    <td><?= htmlspecialchars($measurement['waist']); ?></td>
                    <td><?= htmlspecialchars($measurement['widest']); ?></td>
                    <td>
                        <?php if ($measurement['file_path']): ?>
                            <img src="../uploads/<?= htmlspecialchars($measurement['file_path']); ?>" alt="Bilde" style="max-width: 100px;">
                        <?php else: ?>
                            Ingen bilde
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="edit_measurement.php?id=<?= $measurement['id']; ?>">Rediger</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            <?php $stmt->close(); ?>
        </tbody>
    </table>

</main>

<?php include_once '../includes/footer.php'; ?>
