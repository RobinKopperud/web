<?php
session_start();
include_once '../../db.php'; // Correct path to include db.php

// Function to log errors
function log_error($message) {
    $log_file = dirname(__FILE__) . '/error_log.txt';
    error_log($message . "\n", 3, $log_file);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $action = $_POST['action'];

    if ($action == 'login') {
        // Attempt to log in
        $stmt = $conn->prepare("SELECT id, password FROM user WHERE username = ?");
        if ($stmt === false) {
            log_error('Prepare failed: ' . htmlspecialchars($conn->error));
            die('Prepare failed: ' . htmlspecialchars($conn->error));
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
                $error = "Invalid username or password";
            }
        } else {
            $error = "Invalid username or password";
        }

        $stmt->close();
    } elseif ($action == 'signup') {
        // Attempt to sign up
        $stmt = $conn->prepare("SELECT id FROM user WHERE username = ?");
        if ($stmt === false) {
            log_error('Prepare failed: ' . htmlspecialchars($conn->error));
            die('Prepare failed: ' . htmlspecialchars($conn->error));
        }

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Username already exists";
        } else {
            // Username is available, create new user
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO user (username, password) VALUES (?, ?)");
            if ($stmt === false) {
                log_error('Prepare failed: ' . htmlspecialchars($conn->error));
                die('Prepare failed: ' . htmlspecialchars($conn->error));
            }

            $stmt->bind_param("ss", $username, $hashed_password);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                // User created successfully, log in the user
                $_SESSION['user_id'] = $stmt->insert_id;
                $_SESSION['username'] = $username;
                header("Location: index.php");
                exit();
            } else {
                $error = "Failed to create account";
            }
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login or Sign Up</title>
</head>
<body>
    <h2>Login or Sign Up</h2>
    <?php if (isset($error)): ?>
        <p><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <form method="POST" action="login.php">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required><br>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required><br>
        <input type="radio" id="login" name="action" value="login" checked>
        <label for="login">Login</label><br>
        <input type="radio" id="signup" name="action" value="signup">
        <label for="signup">Sign Up</label><br>
        <button type="submit">Submit</button>
    </form>
</body>
</html>
