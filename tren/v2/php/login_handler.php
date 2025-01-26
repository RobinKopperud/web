<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';
session_start();

$response = ['success' => false, 'message' => 'Invalid credentials.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT id, password FROM tren_users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($id, $hashed_password);

    if ($stmt->fetch() && password_verify($password, $hashed_password)) {
        $_SESSION['user_id'] = $id;
        $response = ['success' => true];
    }

    $stmt->close();
}

header('Content-Type: application/json');
echo json_encode($response);
