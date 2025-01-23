<?php include_once '../../../../db.php'; ?>
<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Prepare the SQL query to fetch the user by email
    $stmt = $conn->prepare("SELECT id, password FROM tren_users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    // Fetch the result
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $hashed_password);
        $stmt->fetch();

        // Verify the password
        if (password_verify($password, $hashed_password)) {
            // Set session and redirect to dashboard
            $_SESSION['user_id'] = $id;
            header("Location: dashboard.php");
            exit();
        } else {
            // Password mismatch
            $error_message = "Feil e-post eller passord.";
        }
    } else {
        // No user found with the given email
        $error_message = "Feil e-post eller passord.";
    }

    $stmt->close();
}
?>

<?php include_once '../includes/header.php'; ?>

<main>
    <h2>Logg Inn</h2>
    <?php if (!empty($error_message)): ?>
        <p style="color: red;"><?= htmlspecialchars($error_message); ?></p>
    <?php endif; ?>
    <form method="POST">
        <label for="email">E-post:</label>
        <input type="email" name="email" required>

        <label for="password">Passord:</label>
        <input type="password" name="password" required>

        <button type="submit">Logg Inn</button>
    </form>
</main>

<?php include_once '../includes/footer.php'; ?>
