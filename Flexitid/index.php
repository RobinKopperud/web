<?php
session_start();
include_once '../../db.php'; // Adjust the path as needed

if (isset($_SESSION['user_id'])) {
    header("Location: employees.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['login'])) {
        // Handle login
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
                $login_error = "Feil passord.";
            }
        } else {
            $login_error = "Ingen bruker funnet.";
        }
    } elseif (isset($_POST['register'])) {
        // Handle registration
        $username = $_POST['reg_username'];
        $password = password_hash($_POST['reg_password'], PASSWORD_BCRYPT);

        $sql = "INSERT INTO brukere (username, password) VALUES ('$username', '$password')";

        if ($conn->query($sql) === TRUE) {
            $register_success = "Ny bruker registrert.";
        } else {
            $register_error = "Feil: " . $sql . "<br>" . $conn->error;
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login og Registrering</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Logg inn</h1>
        <?php if (isset($login_error)): ?>
            <p><?php echo $login_error; ?></p>
        <?php endif; ?>
        <form method="post" action="">
            <input type="hidden" name="login" value="1">
            <label for="username">Brukernavn:</label>
            <input type="text" id="username" name="username" required>
            <label for="password">Passord:</label>
            <input type="password" id="password" name="password" required>
            <button type="submit">Logg inn</button>
        </form>

        <h1>Registrer</h1>
        <?php if (isset($register_error)): ?>
            <p><?php echo $register_error; ?></p>
        <?php endif; ?>
        <?php if (isset($register_success)): ?>
            <p><?php echo $register_success; ?></p>
        <?php endif; ?>
        <form method="post" action="">
            <input type="hidden" name="register" value="1">
            <label for="reg_username">Brukernavn:</label>
            <input type="text" id="reg_username" name="reg_username" required>
            <label for="reg_password">Passord:</label>
            <input type="password" id="reg_password" name="reg_password" required>
            <button type="submit">Registrer</button>
        </form>
    </div>
</body>
</html>
