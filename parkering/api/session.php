<?php
session_start();
header('Content-Type: application/json');

if (isset($_SESSION['bruker_id'])) {
  echo json_encode([
    "loggedIn" => true,
    "rolle" => $_SESSION['rolle']
  ]);
} else {
  echo json_encode([
    "loggedIn" => false
  ]);
}
