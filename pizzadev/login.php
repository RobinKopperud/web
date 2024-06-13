<?php
session_start();
include_once '../../db.php'; // Correct path to include db.php

// Function to log errors
function log_error($message) {
    $log_file = dirname(__FILE__) . '/error_log.txt';
    error_log($message . "\n", 3, $log_file);
}

// Handle login submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = htmlspecialchars($_POST['login_username']);
    $password = $_POST['login_password'];

    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
    if ($stmt === false) {
        log_error('Login Prepare failed: ' . htmlspecialchars($conn->error));
        die('Login Prepare failed: ' . htmlspecialchars($conn->error));
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            // Password is correct, start a session
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $username;
            header("Location: index.php");
            exit();
        } else {
            $login_error = "Invalid username or password";
        }
    } else {
        $login_error = "Invalid username or password";
    }

    $stmt->close();
}

// Handle sign-up submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['signup'])) {
    $username = htmlspecialchars($_POST['signup_username']);
    $email = htmlspecialchars($_POST['signup_email']);
    $password = $_POST['signup_password'];

    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    if ($stmt === false) {
        log_error('Signup Check Prepare failed: ' . htmlspecialchars($conn->error));
        die('Signup Check Prepare failed: ' . htmlspecialchars($conn->error));
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $signup_error = "Username already exists";
    } else {
        // Username is available, create new user
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        if (!$stmt) {
            log_error("Signup Insert Prepare failed: (" . $conn->errno . ") " . $conn->error);
            die("Signup Insert Prepare failed: (" . $conn->errno . ") " . $conn->error);
        }

        $stmt->bind_param("sss", $username, $email, $hashed_password);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                // User created successfully, log in the user
                $_SESSION['user_id'] = $stmt->insert_id;
                $_SESSION['username'] = $username;
                header("Location: index.php");
                exit();
            } else {
                $signup_error = "Failed to create account";
                log_error('Signup Insert Execute: No rows affected');
            }
        } else {
            $signup_error = "Failed to create account";
            log_error('Signup Insert Execute failed: ' . htmlspecialchars($stmt->error));
        }
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login or Sign Up</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2>Login</h2>
            <?php if (isset($login_error)): ?>
                <p><?php echo htmlspecialchars($login_error); ?></p>
            <?php endif; ?>
            <form method="POST" action="login.php">
                <label for="login_username">Username:</label>
                <input type="text" id="login_username" name="login_username" required><br>
                <label for="login_password">Password:</label>
                <input type="password" id="login_password" name="login_password" required><br>
                <button type="submit" name="login">Login</button>
            </form>
        </div>

        <div class="form-container">
            <h2>Sign Up</h2>
            <?php if (isset($signup_error)): ?>
                <p><?php echo htmlspecialchars($signup_error); ?></p>
            <?php endif; ?>
            <form method="POST" action="login.php">
                <label for="signup_username">Username:</label>
                <input type="text" id="signup_username" name="signup_username" required><br>
                <label for="signup_email">Email:</label>
                <input type="email" id="signup_email" name="signup_email" required><br>
                <label for="signup_password">Password:</label>
                <input type="password" id="signup_password" name="signup_password" required><br>
                <button type="submit" name="signup">Sign Up</button>
            </form>
        </div>
    </div>
</body>
</html>
