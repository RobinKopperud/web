<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

$password = $_POST['password'] ?? '';
if ($password !== 'admin') {
    http_response_code(403);
    exit('Feil passord');
}

$result = $conn->query("
    SELECT gruppe_id, gruppekode, opprettet_av, opprettet_tid
    FROM BJGrupper
    ORDER BY opprettet_tid DESC
");

$grupper = $result->fetch_all(MYSQLI_ASSOC);
echo json_encode($grupper);
