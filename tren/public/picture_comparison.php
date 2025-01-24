<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';
include_once '../includes/functions.php';

// User ID from session
$user_id = $_SESSION['user_id'];

// Fetch all photos with associated measurements
$stmt = $conn->prepare("
    SELECT p.file_path, p.uploaded_at, m.weight, m.waist, m.widest, m.date
    FROM tren_photos p
    LEFT JOIN tren_measurements m ON p.measurement_id = m.id
    WHERE p.user_id = ?
    ORDER BY m.date ASC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$photos = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get the first photo for the left side
$first_photo = $photos[0] ?? null;
?>

<?php include_once '../includes/header.php'; ?>

<link rel="stylesheet" href="picture_comparison.css">


<main>
    <h2>Sammenlign Bilder</h2>
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 20px;">

        <!-- Left Side: First Photo -->
        <div style="flex: 1; text-align: center;">
            <h3>Første Bilde</h3>
            <?php if ($first_photo): ?>
                <img src="../uploads/<?= htmlspecialchars($first_photo['file_path']); ?>" alt="Første Bilde" style="max-width: 100%;">
                <p><strong>Dato:</strong> <?= htmlspecialchars($first_photo['date']); ?></p>
                <p><strong>Vekt:</strong> <?= htmlspecialchars($first_photo['weight']); ?> kg</p>
                <p><strong>Livvidde:</strong> <?= htmlspecialchars($first_photo['waist']); ?> cm</p>
                <p><strong>Bredeste Vidde:</strong> <?= htmlspecialchars($first_photo['widest']); ?> cm</p>
            <?php else: ?>
                <p>Ingen bilder funnet.</p>
            <?php endif; ?>
        </div>

        <!-- Right Side: Slider for Other Photos -->
        <div style="flex: 1; text-align: center;">
            <h3>Velg Bilde</h3>
            <?php if (!empty($photos)): ?>
                <input type="range" id="photo-slider" min="0" max="<?= count($photos) - 1; ?>" value="0" style="width: 100%;" onchange="updatePhoto(this.value)">
                <div id="selected-photo">
                    <img src="../uploads/<?= htmlspecialchars($photos[0]['file_path']); ?>" alt="Valgt Bilde" style="max-width: 100%;">
                    <p><strong>Dato:</strong> <span id="photo-date"><?= htmlspecialchars($photos[0]['date']); ?></span></p>
                    <p><strong>Vekt:</strong> <span id="photo-weight"><?= htmlspecialchars($photos[0]['weight']); ?></span> kg</p>
                    <p><strong>Livvidde:</strong> <span id="photo-waist"><?= htmlspecialchars($photos[0]['waist']); ?></span> cm</p>
                    <p><strong>Bredeste Vidde:</strong> <span id="photo-widest"><?= htmlspecialchars($photos[0]['widest']); ?></span> cm</p>
                </div>
            <?php else: ?>
                <p>Ingen bilder funnet.</p>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
    const photos = <?= json_encode($photos); ?>;
    const selectedPhoto = document.getElementById('selected-photo');
    const slider = document.getElementById('photo-slider');

    function updatePhoto(index) {
        const photo = photos[index];
        selectedPhoto.querySelector('img').src = `../uploads/${photo.file_path}`;
        selectedPhoto.querySelector('#photo-date').textContent = photo.date || 'Ingen Dato';
        selectedPhoto.querySelector('#photo-weight').textContent = photo.weight || 'Ingen Vekt';
        selectedPhoto.querySelector('#photo-waist').textContent = photo.waist || 'Ingen Livvidde';
        selectedPhoto.querySelector('#photo-widest').textContent = photo.widest || 'Ingen Vidde';
    }
</script>

<?php include_once '../includes/footer.php'; ?>
