<?php include_once '../../../../db.php'; ?>

<?php

session_start();

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!$conn) {
        die("Database connection failed: " . mysqli_connect_error());
    }

    $stmt = $conn->prepare("SELECT id, password FROM tren_users WHERE email = ?");
    if (!$stmt) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    if (!$stmt->execute()) {
        die("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
    }

    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $id;
            header("Location: dashboard.php");
            exit();
        } else {
            echo "Password mismatch.";
        }
    } else {
        echo "No user found.";
    }

    $stmt->close();
}
?>
<main>
    <h2>Logg Inn</h2>
    <form method="POST">
        <label for="email">E-post:</label>
        <input type="email" name="email" required>
        <label for="password">Passord:</label>
        <input type="password" name="password" required>
        <button type="submit">Logg Inn</button>
    </form>
</main>
