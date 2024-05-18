<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../loginout.html");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game 1</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <div class="sidebar">
        <nav>
            <ul>
                <li><a href="../gamingui.php">Gaming Hub</a></li>
                <li><a href="../../index.html">Home</a></li>
                <li><a href="../../logout.php">Logout</a></li>
            </ul>
        </nav>
    </div>
    <div class="main-content">
        <h1>Game 1</h1>
        <!-- Game content goes here -->
    </div>
</body>
</html>
