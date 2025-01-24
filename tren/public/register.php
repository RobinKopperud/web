<?php
// Include the database connection
include_once '../../../../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']); // Sanitize email input
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT); // Hash the password

    // Ensure database connection is initialized
    if (!$conn) {
        die("Database connection failed: " . mysqli_connect_error());
    }

    // Prepare SQL query
    $stmt = $conn->prepare("INSERT INTO tren_users (email, password) VALUES (?, ?)");
    if (!$stmt) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }

    // Bind parameters and execute query
    $stmt->bind_param("ss", $email, $password);

    if ($stmt->execute()) {
        // Redirect to login.php on successful registration
        header("Location: login.php");
        exit();
    } else {
        // Display an error message if registration fails
        die("Insert failed: (" . $stmt->errno . ") " . $stmt->error);
    }

    $stmt->close();
}
?>

<?php include_once '../includes/header.php'; ?>

<main>
    <h2>Registrer Bruker</h2>
    <form method="POST">
        <label for="email">E-post:</label>
        <input type="email" name="email" required>

        <label for="password">Passord:</label>
        <input type="password" name="password" required>

        <button type="submit">Registrer</button>
    </form>
</main>

<?php include_once '../includes/footer.php'; ?>
