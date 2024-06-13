<?php
include_once '../../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id']) && isset($_POST['action'])) {
    $id = intval($_POST['id']);
    $action = $_POST['action'];
    
    if ($action === 'like') {
        $stmt = $conn->prepare("UPDATE pizza SET hearts = hearts + 1 WHERE id = ?");
    } elseif ($action === 'unlike') {
        $stmt = $conn->prepare("UPDATE pizza SET hearts = hearts - 1 WHERE id = ?");
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        exit();
    }

    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // Fetch the new hearts count
        $stmt = $conn->prepare("SELECT hearts FROM pizza WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($hearts);
        $stmt->fetch();
        
        echo json_encode(['status' => 'success', 'hearts' => $hearts]);
    } else {
        echo json_encode(['status' => 'error', 'message' => $stmt->error]);
    }
    
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>
