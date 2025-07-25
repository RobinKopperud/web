<?php
// Start session
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once '../../db.php'; // Adjust the path as needed

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = null;
$success = isset($_GET['success']) ? $_GET['success'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prevent SQL injection by escaping input
    $username = $conn->real_escape_string($username);

    // Query the users table
    $result = $conn->query("SELECT user_id, username, password, role FROM users WHERE username = '$username'");
    if ($result === false) {
        $error = "Database error: " . $conn->error;
    } else {
        $user = $result->fetch_assoc();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
            header("Location: index.php");
            exit;
        } else {
            $error = "Feil brukernavn eller passord.";
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="nb">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logg inn - Borettslag Parkering</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'nav.php'; ?>

    <div class="container mt-4">
        <h1>Logg inn</h1>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" class="needs-validation" novalidate>
            <div class="mb-3">
                <label for="username" class="form-label">Brukernavn</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Passord</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-pink">Logg inn</button>
        </form>
        <p class="mt-3">Har du ikke konto? <a href="register.php">Registrer deg her</a>.</p>
    </div>
</body>
</html>