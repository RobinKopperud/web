<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';

$gruppe_id = $_GET['gruppe_id'];
$spiller_id = $_GET['spiller_id'];

$sql = "
SELECT t.transaksjon_id, t.belop, s.navn
FROM BJTransaksjoner t
JOIN BJSpillere s ON s.spiller_id = t.maal_spiller_id
WHERE t.status = 'ventende'
  AND t.maal_spiller_id != ?
  AND t.gruppe_id = ?
  AND NOT EXISTS (
    SELECT 1 FROM BJGodkjenninger g
    WHERE g.transaksjon_id = t.transaksjon_id
      AND g.spiller_id = ?
  )
LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $spiller_id, $gruppe_id, $spiller_id);
$stmt->execute();
$result = $stmt->get_result();

$response = $result->fetch_assoc() ?? null;
echo json_encode($response);

$conn->close();
?>
