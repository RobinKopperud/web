<?php include_once '../../../../db.php'; ?>
<?php session_start(); ?>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include_once '../../../../db.php';
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM tren_users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        session_start();
        $_SESSION['user_id'] = $user['id'];
        header('Location: dashboard.php');
        exit();
    } else {
        echo "Feil e-post eller passord.";
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
