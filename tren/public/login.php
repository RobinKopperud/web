<?php
// Include the database connection
include_once '../../../../db.php';
session_start();

$error_message = ''; // Initialize error message

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get and sanitize user inputs
    $email = trim($_POST['email']); // Trim any unnecessary spaces
    $password = $_POST['password'];

    // Debug: Output the raw inputs for validation
    echo "Email: $email<br>";
    echo "Password: $password<br>";

    // Prepare SQL query to fetch the user by email
    if ($stmt = $conn->prepare("SELECT id, password FROM tren_users WHERE email = ?")) {
        $stmt->bind_param("s", $email); // Bind email as a string
        if ($stmt->execute()) {
            $stmt->store_result();
            // Debug: Check if any rows were found
            echo "Number of rows found: " . $stmt->num_rows . "<br>";

            if ($stmt->num_rows > 0) {
                // Bind and fetch the result
                $stmt->bind_result($id, $hashed_password);
                $stmt->fetch();

                // Debug: Output fetched data
                echo "Fetched User ID: $id<br>";
                echo "Fetched Hashed Password: $hashed_password<br>";

                // Verify the password
                if (password_verify($password, $hashed_password)) {
                    // Successful login
                    $_SESSION['user_id'] = $id;
                    echo "Password verified. Redirecting to dashboard...<br>";
                    header("Location: dashboard.php");
                    exit();
                } else {
                    // Debug: Password mismatch
                    echo "Password mismatch.<br>";
                    $error_message = "Feil e-post eller passord.";
                }
            } else {
                // Debug: No user found
                echo "No user found with this email.<br>";
                $error_message = "Feil e-post eller passord.";
            }
        } else {
            // Debug: Query execution failed
            echo "Query execution failed: (" . $stmt->errno . ") " . $stmt->error . "<br>";
        }
        $stmt->close();
    } else {
        // Debug: Statement preparation failed
        echo "Statement preparation failed: (" . $conn->errno . ") " . $conn->error . "<br>";
    }
}
?>

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
