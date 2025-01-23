<?php
$error_message = ''; // Initialize error message

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include_once '../../../../db.php'; // Ensure this points to the correct file

    $email = $_POST['email'];
    $password = $_POST['password'];

    // Debug: Check inputs
    var_dump($email, $password);

    // Prepare SQL statement to fetch user by email
    if (!$stmt = $conn->prepare("SELECT * FROM tren_users WHERE email = ?")) {
        die("Prepare failed: " . $conn->error); // Debug: Check query preparation
    }
    $stmt->bind_param("s", $email);

    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error); // Debug: Check query execution
    }

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Debug: Check fetched user data
    var_dump($user);

    if ($user && password_verify($password, $user['password'])) {
        session_start();
        $_SESSION['user_id'] = $user['id'];

        // Debug: Check session before redirect
        var_dump($_SESSION);

        header('Location: dashboard.php');
        exit();
    } else {
        $error_message = "Feil e-post eller passord.";
    }

    $stmt->close();
}
?>
