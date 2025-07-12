<?php
session_start();
require '../../../db.php';

header('Content-Type: application/json');

$rolle = $_SESSION['rolle'] ?? 'gjest';

$sql = "
  SELECT 
    p.*, 
    b.navn AS eier_navn
  FROM 
    parkeringsplass p
  LEFT JOIN 
    bruker b ON p.eier_id = b.id
";

$result = $conn->query($sql);

$data = [];

while ($row = $result->fetch_assoc()) {
  $anleggId = $row['anlegg_id'];

  if (!isset($data[$anleggId])) {
    $data[$anleggId] = [
      "id" => $row['anlegg_id'],
      "navn" => $row['anlegg_navn'],
      "type" => $row['type'],
      "posisjon" => [(float)$row['lat'], (float)$row['lng']],
      "plasser" => []
    ];
  }

  $kontrakt = ($rolle === 'admin') ? $row['kontrakt'] : '';

  $data[$anleggId]['plasser'][] = [
    "nummer" => (int)$row['nummer'],
    "status" => $row['status'],
    "elbillader" => (bool)$row['elbillader'],
    "eier" => $row['eier_navn'] ?: 'Ikke tildelt',
    "kontrakt" => $kontrakt,
    "pris_per_mnd" => (int)$row['pris_per_mnd']
  ];
}

echo json_encode(array_values($data));
