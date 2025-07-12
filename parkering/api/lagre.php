<?php
session_start();
require '../../../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['rolle']) || $_SESSION['rolle'] !== 'admin') {
  http_response_code(403);
  echo json_encode(["success" => false, "message" => "Ikke autorisert"]);
  exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$anleggId = $data['anlegg_id'];
$nummer = (int)$data['nummer'];
$status = $data['status'];
$kontrakt = $data['kontrakt'] ?? '';
$eierNavn = $data['eier'] ?? null;

// Finn eier_id fra navn hvis gitt
$eier_id = null;
if ($eierNavn) {
  $stmt = $conn->prepare("SELECT id FROM bruker WHERE navn = ?");
  $stmt->bind_param("s", $eierNavn);
  $stmt->execute();
  $res = $stmt->get_result()->fetch_assoc();
  if ($res) {
    $eier_id = $res['id'];
  }
}

$stmt = $conn->prepare("
  UPDATE parkeringsplass 
  SET status = ?, kontrakt = ?, eier_id = ? 
  WHERE anlegg_id = ? AND nummer = ?
");

$stmt->bind_param("ssisi", $status, $kontrakt, $eier_id, $anleggId, $nummer);

if ($stmt->execute()) {
  echo json_encode(["success" => true]);
} else {
  http_response_code(500);
  echo json_encode(["success" => false, "message" => "Kunne ikke lagre"]);
}
