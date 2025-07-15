<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require '../../db.php';

try {
    $stmt = $conn->query("SELECT facility_id, name, type, lat, lng FROM facilities");
    $facilities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($facilities);
} catch(PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>