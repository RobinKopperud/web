<?php
session_start();
require '../../../db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $brukernavn = $_POST['brukernavn'];
  $passord = $_POST['passord'];

  $stmt = $conn->prepare("SELECT id, passord_hash, rolle FROM bruker WHERE brukernavn = ?");
  $stmt->bind_param("s", $brukernavn);
  $stmt->execute();
  $result = $stmt->get_result()->fetch_assoc();

  if ($result && password_verify($passord, $result['passord_hash'])) {
    $_SESSION['bruker_id'] = $result['id'];
    $_SESSION['rolle'] = $result['rolle'];
    echo json_encode(["success" => true, "rolle" => $result['rolle']]);
  } else {
    echo json_encode(["success" => false, "message" => "Feil brukernavn/passord"]);
  }
}
