<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

$response = ['success' => false, 'message' => 'Registration failed.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO tren_users (email, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $email, $password);

    if ($stmt->execute()) {
        $response = ['success' => true, 'message' => 'Registration successful.'];
    } else {
        $response['message'] = 'E-post already exists.';
    }

    $stmt->close();
}

header('Content-Type: application/json');
echo json_encode($response);
