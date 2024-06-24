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
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../mobile.css" media="screen and (max-width: 768px)">
    <style>
        .admin-form { max-width: 300px; margin: 20px auto; padding: 20px; background: #333; border-radius: 5px; }
        .admin-form input, .admin-form textarea { width: 100%; padding: 10px; margin-bottom: 10px; }
        .admin-form button { width: 100%; padding: 10px; background: #e8491d; color: white; border: none; cursor: pointer; }
        .error { color: red; }
        .success { color: green; }
        .back-link { display: block; margin-top: 20px; text-align: center; color: #e8491d; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
    </style>
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
