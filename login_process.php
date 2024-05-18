<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$servername = "localhost";
$username = "jmntxjwa_AI";
$password = "ai-Admin";
$dbname = "jmntxjwa_users";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate that the expected POST variables are set
    if (isset($_POST['username']) && isset($_POST['password'])) {
        $user_or_email = htmlspecialchars($_POST['username']); // Use 'username' to match your form field name
        $pass = $_POST['password'];

        // Prepare and bind
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ? OR username = ?");
        if (!$stmt) {
            die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        }

        $stmt->bind_param("ss", $user_or_email, $user_or_email); // Bind the same parameter for both email and username
        if (!$stmt->execute()) {
            die("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
        }

        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $username, $hashed_password);
            $stmt->fetch();
            if (password_verify($pass, $hashed_password)) {
                $_SESSION['user_id'] = $id;
                $_SESSION['username'] = $username;
                header("Location: index.php");
                exit();
            } else {
                echo "Feil brukernavn eller passord.";
            }
        } else {
            echo "Feil brukernavn eller passord.";
        }

        $stmt->close();
    } else {
        echo "Both username/email and password are required.";
    }
}

$conn->close();
?>
