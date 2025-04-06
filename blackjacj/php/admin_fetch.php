<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

$password = $_POST['password'] ?? '';
if ($password !== 'admin') {
    http_response_code(403);
    exit('Feil passord');
}

$result = $conn->query("
    SELECT g.gruppe_id, g.gruppekode, g.opprettet_tidspunkt, s.navn AS oppretter
    FROM BJGrupper g
    LEFT JOIN BJSpillere s ON s.gruppe_id = g.gruppe_id
    WHERE s.spiller_id = (
        SELECT MIN(spiller_id) FROM BJSpillere WHERE gruppe_id = g.gruppe_id
    )
    ORDER BY g.opprettet_tidspunkt DESC
");

$grupper = $result->fetch_all(MYSQLI_ASSOC);
echo json_encode($grupper);
