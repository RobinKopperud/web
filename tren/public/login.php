<?php
// Include the database connection
include_once '../../../../db.php';

session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate POST variables
    if (isset($_POST['email']) && isset($_POST['password'])) {
        $email = trim($_POST['email']); // Sanitize email
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
                echo "Feil e-post eller passord."; // Password mismatch
            }
        } else {
            echo "Feil e-post eller passord."; // No matching user found
        }

        $stmt->close(); // Close the statement
    } else {
        echo "Both email and password are required."; // Missing input
    }
}
?>
