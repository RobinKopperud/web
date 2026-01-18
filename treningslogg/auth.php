<?php
session_start();

const REMEMBER_COOKIE_NAME = 'treningslogg_remember';
const REMEMBER_COOKIE_DURATION = 2592000;

function ensure_logged_in(mysqli $conn): void
{
    if (!isset($_SESSION['user_id'])) {
        maybe_login_from_cookie($conn);
    }

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

function attempt_login(mysqli $conn, string $email, string $password, bool $remember_me = false): bool
{
    $stmt = $conn->prepare('SELECT * FROM users WHERE epost = ?');
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result ? $result->fetch_assoc() : null;

    if ($user && password_verify($password, $user['passord'])) {
        set_user_session($user);
        if ($remember_me) {
            create_remember_token($conn, (int) $user['id']);
        }
        return true;
    }

    return false;
}

function logout_and_redirect(mysqli $conn): void
{
    if (isset($_COOKIE[REMEMBER_COOKIE_NAME])) {
        delete_remember_token($conn, $_COOKIE[REMEMBER_COOKIE_NAME]);
        clear_remember_me_cookie();
    }
    session_destroy();
    header('Location: login.php');
    exit;
}

function maybe_login_from_cookie(mysqli $conn): void
{
    if (isset($_SESSION['user_id'])) {
        return;
    }

    $token = $_COOKIE[REMEMBER_COOKIE_NAME] ?? '';
    if ($token === '') {
        return;
    }

    $cleanup = $conn->prepare('DELETE FROM treningslogg_remember_tokens WHERE expires_at < NOW()');
    if ($cleanup) {
        $cleanup->execute();
    }

    $token_hash = hash('sha256', $token);
    $stmt = $conn->prepare(
        'SELECT id, user_id FROM treningslogg_remember_tokens WHERE token_hash = ? AND expires_at > NOW() LIMIT 1'
    );
    if (!$stmt) {
        return;
    }

    $stmt->bind_param('s', $token_hash);
    $stmt->execute();
    $result = $stmt->get_result();
    $record = $result ? $result->fetch_assoc() : null;

    if (!$record) {
        clear_remember_me_cookie();
        return;
    }

    $user = fetch_user_by_id($conn, (int) $record['user_id']);
    if (!$user) {
        clear_remember_me_cookie();
        return;
    }

    $delete = $conn->prepare('DELETE FROM treningslogg_remember_tokens WHERE id = ?');
    if ($delete) {
        $delete->bind_param('i', $record['id']);
        $delete->execute();
    }

    set_user_session($user);
    create_remember_token($conn, (int) $user['id']);
}

function set_user_session(array $user): void
{
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['rolle'] = $user['rolle'] ?? null;
    $_SESSION['borettslag_id'] = $user['borettslag_id'] ?? null;
}

function fetch_user_by_id(mysqli $conn, int $user_id): ?array
{
    $stmt = $conn->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result ? $result->fetch_assoc() : null;
}

function create_remember_token(mysqli $conn, int $user_id): void
{
    $token = bin2hex(random_bytes(32));
    $token_hash = hash('sha256', $token);
    $expires_at = date('Y-m-d H:i:s', time() + REMEMBER_COOKIE_DURATION);

    $stmt = $conn->prepare(
        'INSERT INTO treningslogg_remember_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)'
    );
    if ($stmt) {
        $stmt->bind_param('iss', $user_id, $token_hash, $expires_at);
        if ($stmt->execute()) {
            set_remember_me_cookie($token, strtotime($expires_at));
        }
    }
}

function delete_remember_token(mysqli $conn, string $token): void
{
    if ($token === '') {
        return;
    }

    $token_hash = hash('sha256', $token);
    $stmt = $conn->prepare('DELETE FROM treningslogg_remember_tokens WHERE token_hash = ?');
    if ($stmt) {
        $stmt->bind_param('s', $token_hash);
        $stmt->execute();
    }
}

function set_remember_me_cookie(string $token, int $expires): void
{
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    setcookie(REMEMBER_COOKIE_NAME, $token, [
        'expires' => $expires,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function clear_remember_me_cookie(): void
{
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    setcookie(REMEMBER_COOKIE_NAME, '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}
?>
