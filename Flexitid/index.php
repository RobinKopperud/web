<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: employees.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Login</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Fleksitid System</h1>

        <div id="auth-section">
            <h2>Register</h2>
            <form id="register-form">
                <input type="text" id="reg-username" placeholder="Username" required>
                <input type="password" id="reg-password" placeholder="Password" required>
                <button type="submit">Register</button>
            </form>

            <h2>Login</h2>
            <form id="login-form">
                <input type="text" id="login-username" placeholder="Username" required>
                <input type="password" id="login-password" placeholder="Password" required>
                <button type="submit">Login</button>
            </form>
        </div>
    </div>
    <script src="js/auth.js"></script>
</body>
</html>
