<?php
session_start();
include_once '../../db.php'; // Juster stien etter behov

if (isset($_SESSION['user_id'])) {
    header("Location: employees.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT id, password FROM brukere WHERE username='$username'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $username;
            header("Location: employees.php");
            exit();
        } else {
            $error = "Feil passord.";
        }
    } else {
        $error = "Ingen bruker funnet.";
    }
}
?>

<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Logg inn</h1>
        <?php if (isset($error)): ?>
            <p><?php echo $error; ?></p>
        <?php endif; ?>
        <form method="post" action="">
            <label for="username">Brukernavn:</label>
            <input type="text" id="username" name="username" required>
            <label for="password">Passord:</label>
            <input type="password" id="password" name="password" required>
            <button type="submit">Logg inn</button>
        </form>
    </div>
</body>
</html>
