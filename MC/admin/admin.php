<?php
session_start();
include_once '../../../db.php'; // Adjust the path as needed


// Simple authentication (replace with a more secure method in production)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === 'admintest') { // Replace with secure password handling
        $_SESSION['authenticated'] = true;
    } else {
        $error = 'Feil passord';
    }
}
?>

<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Mental Racing Team</title>
    <link rel="stylesheet" href="admin_style.css">
</head>
<body>
    <div class="container">
        <h1>Admin - Mental Racing Team</h1>
        <a href="../index.php" class="back-link">Tilbake til hovedsiden</a>
        <?php if (!isset($_SESSION['authenticated'])): ?>
            <form class="admin-form" method="POST">
                <input type="password" name="password" placeholder="Passord" required>
                <button type="submit">Logg inn</button>
                <?php if ($error): ?>
                    <p class="error"><?php echo $error; ?></p>
                <?php endif; ?>
            </form>
        <?php else: ?>
            <h2>Oppdater neste arrangementsdato</h2>
            <?php include('nextrace.php'); ?>

            <h2>Last opp tidslinjehendelse</h2>
            <?php include('timelineadmin.php'); ?>
        <?php endif; ?>
    </div>
    <script src="nextrace.js"></script>
</body>
</html>
