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

// Make sure the connection is available to all included files
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
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
        <header>
            <h1>Admin - Mental Racing Team</h1>
            <a href="../index.php" class="back-link">Tilbake til hovedsiden</a>
        </header>
        <?php if (!isset($_SESSION['authenticated'])): ?>
            <form class="admin-form" method="POST">
                <label for="password">Passord:</label>
                <input type="password" id="password" name="password" placeholder="Passord" required>
                <button type="submit">Logg inn</button>
                <?php if (isset($error)): ?>
                    <p class="error"><?php echo $error; ?></p>
                <?php endif; ?>
            </form>
        <?php else: ?>
            <div class="admin-sections">
                <div class="admin-section">
                    <h2>Oppdater neste arrangementsdato</h2>
                    <?php include('nextrace.php'); ?>
                </div>
                <div class="admin-section">
                    <h2>Last opp tidslinjehendelse</h2>
                    <?php include('timelineadmin.php'); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <script src="nextrace.js"></script>
</body>
</html>
