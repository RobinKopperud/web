<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../loginout.html");
    exit();
}
?>

<div class="sidebar">
    <nav>
        <ul>
            <li><a href="#" id="toggleSidebar"><i class="fas fa-bars"></i></a></li>
            <li><a href="gamingui.php">Gaming Hub</a></li>
            <li><a href="../index.php">Home</a></li>
            <li><a href="../logout.php">Logout</a></li>
        </ul>
    </nav>
</div>