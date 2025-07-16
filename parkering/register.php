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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Validate input
    if (empty($username) || empty($email) || empty($password)) {
        $error = "Alle felt må fylles ut.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Ugyldig e-postadresse.";
    } else {
        // Check if username or email already exists
        $username = $conn->real_escape_string($username);
        $email = $conn->real_escape_string($email);
        $result = $conn->query("SELECT user_id FROM users WHERE username = '$username' OR email = '$email'");
        if ($result->num_rows > 0) {
            $error = "Brukernavn eller e-post er allerede i bruk.";
        } else {
            // Hash password and insert user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query = "INSERT INTO users (username, email, password, role) VALUES ('$username', '$email', '$hashed_password', 'user')";
            if ($conn->query($query) === true) {
                header("Location: login.php?success=Registrering vellykket. Logg inn.");
                exit;
            } else {
                $error = "Registrering feilet: " . $conn->error;
            }
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
    <title>Registrer - Borettslag Parkering</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-gray">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Borettslag Parkering</a>
            <div class="navbar-nav">
                <a class="nav-link" href="index.php">Hjem</a>
                <a class="nav-link" href="parking.php">Parkeringsplasser</a>
                <a class="nav-link" href="login.php">Logg inn</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>Registrer deg</h1>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" class="needs-validation" novalidate>
            <div class="mb-3">
                <label for="username" class="form-label">Brukernavn</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">E-post</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Passord</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-pink">Registrer</button>
        </form>
        <p class="mt-3">Har du allerede en konto? <a href="login.php">Logg inn her</a>.</p>
    </div>
</body>
</html>