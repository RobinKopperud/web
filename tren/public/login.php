<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include_once '../../../../db.php';

    $email = $_POST['email'];
    $password = $_POST['password'];

    // Ensure $conn is initialized
    if (!$conn) {
        die("Database connection failed: " . mysqli_connect_error());
    }

    // Prepare SQL query
    $stmt = $conn->prepare("SELECT id, password FROM tren_users WHERE email = ?");
    if (!$stmt) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }

    $stmt->bind_param("s", $email);

    if ($stmt->execute()) {
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
    } else {
        die("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
    }

    $stmt->close();
}
?>

<?php include_once '../includes/header.php'; ?>

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

<?php include_once '../includes/footer.php'; ?>
