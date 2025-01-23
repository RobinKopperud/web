<?php
// Include the database connection
include_once '../../../../db.php';
session_start();

$error_message = ''; // Initialize error message

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate POST variables
    if (isset($_POST['email']) && isset($_POST['password'])) {
        $email = trim($_POST['email']); // Sanitize email input
        $password = $_POST['password'];

        // Prepare the SQL query
        $stmt = $conn->prepare("SELECT id, password FROM tren_users WHERE email = ?");
        if (!$stmt) {
            die("Prepare failed: (" . $conn->errno . ") " . $conn->error); // Debugging info for preparation failure
        }

        $stmt->bind_param("s", $email); // Bind email as a string
        if (!$stmt->execute()) {
            die("Execute failed: (" . $stmt->errno . ") " . $stmt->error); // Debugging info for execution failure
        }

        $stmt->store_result(); // Store the result for counting rows
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $hashed_password); // Bind result variables
            $stmt->fetch(); // Fetch the row

            // Verify the password
            if (password_verify($password, $hashed_password)) {
                // Successful login
                $_SESSION['user_id'] = $id; // Store user ID in session
                header("Location: dashboard.php"); // Redirect to dashboard
                exit();
            } else {
                $error_message = "Feil e-post eller passord."; // Password mismatch
            }
        } else {
            $error_message = "Feil e-post eller passord."; // No matching user found
        }

        $stmt->close(); // Close the statement
    } else {
        $error_message = "Both email and password are required."; // Missing input
    }
}
?>

<?php include_once '../includes/header.php'; ?>

<main>
    <h2>Logg Inn</h2>
    <?php if (!empty($error_message)): ?>
        <p style="color: red;"><?= htmlspecialchars($error_message); ?></p>
    <?php endif; ?>
    <form method="POST" action="">
        <label for="email">E-post:</label>
        <input type="email" id="email" name="email" required>

        <label for="password">Passord:</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Logg Inn</button>
    </form>
</main>

<?php include_once '../includes/footer.php'; ?>
