<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beskyttet Side</title>
</head>
<body>
    <h1>Velkommen til den beskyttede siden!</h1>
    <p>Dette innholdet er kun tilgjengelig for innloggede brukere.</p>
    <a href="../logout.php">Logg ut</a>
</body>
</html>
