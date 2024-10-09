<?php
define('APP_INIT', true);
include_once '../../../includes/db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$destinationId = $data['destination_id'];

if ($destinationId) {
    $query = "UPDATE destinations SET votes = votes + 1 WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $destinationId);
    if ($stmt->execute()) {
        // Fetch the new vote count
        $query = "SELECT votes FROM destinations WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $destinationId);
        $stmt->execute();
        $stmt->bind_result($newVoteCount);
        $stmt->fetch();

        echo json_encode(['success' => true, 'newVoteCount' => $newVoteCount]);
    } else {
        echo json_encode(['success' => false]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false]);
}

$conn->close();
?>
