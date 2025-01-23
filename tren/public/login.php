<?php
// Include the database connection
include_once '../../../../db.php';
session_start();

$error_message = ''; // Initialize error message

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email']; // Get email input
    $password = $_POST['password']; // Get password input

    // Prepare SQL query to fetch the user by email
    $stmt = $conn->prepare("SELECT id, password FROM tren_users WHERE email = ?");
    $stmt->bind_param("s", $email); // Bind email as a string
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // User found, bind and fetch the result
        $stmt->bind_result($id, $hashed_password);
        $stmt->fetch();

        // Verify the password
        if (password_verify($password, $hashed_password)) {
            // Password matches, set session and redirect to dashboard
            $_SESSION['user_id'] = $id;
            header("Location: dashboard.php");
            exit();
        } else {
            // Incorrect password
            $error_message = "Feil e-post eller passord.";
        }
    } else {
        // No user found with the provided email
        $error_message = "Feil e-post eller passord.";
    }

    $stmt->close(); // Close the statement
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
        <input type="email" name="email" id="email" required>

        <label for="password">Passord:</label>
        <input type="password" name="password" id="password" required>

        <button type="submit">Logg Inn</button>
    </form>
</main>

<?php include_once '../includes/footer.php'; ?>
