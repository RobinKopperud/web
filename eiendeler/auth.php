<?php
// Shared authentication helpers for the crypto tracker.
session_start();

function ensure_logged_in(): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function fetch_current_user(mysqli $conn): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    $stmt = $conn->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result ? $result->fetch_assoc() : null;
}

function attempt_login(mysqli $conn, string $email, string $password): bool
{
    $sql = 'SELECT * FROM users WHERE epost = ?';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result ? $result->fetch_assoc() : null;

    if ($user && password_verify($password, $user['passord'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['rolle'] = $user['rolle'] ?? null;
        $_SESSION['borettslag_id'] = $user['borettslag_id'] ?? null;
        return true;
    }

    return false;
}

function logout_and_redirect(): void
{
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
