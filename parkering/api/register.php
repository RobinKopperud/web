<?php
require '../../../db.php';
header('Content-Type: application/json');

$brukernavn = trim($_POST['ny_brukernavn']);
$navn = trim($_POST['navn']);
$passord = $_POST['ny_passord'];

if (!$brukernavn || !$navn || !$passord) {
  echo json_encode(["success" => false, "message" => "Mangler felt"]);
  exit;
}

// Sjekk om brukernavn finnes
$stmt = $conn->prepare("SELECT id FROM bruker WHERE brukernavn = ?");
$stmt->bind_param("s", $brukernavn);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
  echo json_encode(["success" => false, "message" => "Brukernavn opptatt"]);
  exit;
}

$passord_hash = password_hash($passord, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO bruker (brukernavn, passord_hash, rolle, navn) VALUES (?, ?, 'bruker', ?)");
$stmt->bind_param("sss", $brukernavn, $passord_hash, $navn);
if ($stmt->execute()) {
  echo json_encode(["success" => true]);
} else {
  echo json_encode(["success" => false, "message" => "Kunne ikke lagre bruker"]);
}
