<?php include_once '../../../../db.php'; ?>
<?php session_start(); ?>

<?php
$error_message = ''; // Initialize error message

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Prepare SQL statement to fetch user by email
    $stmt = $conn->prepare("SELECT * FROM tren_users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        header('Location: dashboard.php');
        exit();
    } else {
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
