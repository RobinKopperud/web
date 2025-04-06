<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

$gruppekode = $_GET['gruppekode'] ?? '';

$stmt = $conn->prepare("
    SELECT s.spiller_id, s.navn, s.saldo
    FROM BJSpillere s
    JOIN BJGrupper g ON s.gruppe_id = g.gruppe_id
    WHERE g.gruppekode = ?
");
$stmt->bind_param("s", $gruppekode);
$stmt->execute();
$result = $stmt->get_result();

$spillere = [];
while ($row = $result->fetch_assoc()) {
    $spillere[] = $row;
}
echo json_encode($spillere);
?>
