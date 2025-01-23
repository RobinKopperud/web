<?php include_once '../../../db.php'; ?>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("INSERT INTO tren_users (email, password) VALUES (:email, :password)");
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $password);
    $stmt->execute();

    echo "Bruker registrert! <a href='login.php'>Logg inn her</a>";
}
?>

<?php include_once '../includes/header.php'; ?>

<main>
    <h2>Registrer Bruker</h2>
    <form method="POST">
        <label for="email">E-post:</label>
        <input type="email" name="email" required>

        <label for="password">Passord:</label>
        <input type="password" name="password" required>

        <button type="submit">Registrer</button>
    </form>
</main>

<?php include_once '../includes/footer.php'; ?>
