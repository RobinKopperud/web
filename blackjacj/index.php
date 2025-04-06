<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php'; // Inkluderer databasekoblingen
?>
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <title>Blackjack Kontroll</title>
    <link rel="stylesheet" href="css/style.css"> <!-- Ekstern CSS -->
</head>
<body>
    <header>
        <div class="banner">
            <h1>Blackjack Kontroll</h1>
            <p>Velkommen til den ultimate blackjack-opplevelsen med digital verdikontroll!</p>
        </div>
    </header>
    
    <main>
        <section id="create-group">
            <h2>Opprett ny gruppe</h2>
            <form action="php/create_group.php" method="post">
                <label for="creator_name">Ditt navn:</label>
                <input type="text" id="creator_name" name="creator_name" required>
                <input type="submit" value="Opprett Gruppe">
            </form>
        </section>
        
        <section id="join-group">
            <h2>Bli med i en eksisterende gruppe</h2>
            <form action="php/join_group.php" method="post">
                <label for="group_code">Gruppekode:</label>
                <input type="text" id="group_code" name="group_code" required>
                <label for="name">Ditt navn:</label>
                <input type="text" id="name" name="name" required>
                <input type="submit" value="Bli med">
            </form>
        </section>
        <section id="admin-access">
        <h2>Admin</h2>
            <form id="admin-login">
                <label for="admin_password">Admin-passord:</label>
                <input type="password" id="admin_password" required>
                <input type="submit" value="Vis grupper">
            </form>

            <div id="admin-output" style="margin-top: 20px;"></div>
        </section>

    </main>
    
    <script src="js/script.js"></script> <!-- Ekstern JavaScript -->
</body>
</html>
