<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/lib.php';

ensure_logged_in($conn);
$entryId = (int)($_GET['entry'] ?? 0);
if ($entryId <= 0 || !training_images_table_available($conn)) {
    http_response_code(404);
    exit;
}

$stmt = $conn->prepare('SELECT file_name, mime_type FROM treningslogg_entry_images WHERE entry_id = ? AND user_id = ? LIMIT 1');
if (!$stmt) {
    http_response_code(404);
    exit;
}
$stmt->bind_param('ii', $entryId, $_SESSION['user_id']);
$stmt->execute();
$image = $stmt->get_result()->fetch_assoc();
if (!$image) {
    http_response_code(404);
    exit;
}

$uploadDir = realpath(__DIR__ . '/uploads');
$filePath = $uploadDir ? realpath($uploadDir . DIRECTORY_SEPARATOR . basename($image['file_name'])) : false;
if (!$filePath || strpos($filePath, $uploadDir . DIRECTORY_SEPARATOR) !== 0 || !is_file($filePath)) {
    http_response_code(404);
    exit;
}

$allowed = ['image/jpeg', 'image/png', 'image/webp'];
$mime = in_array($image['mime_type'], $allowed, true) ? $image['mime_type'] : 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($filePath));
header('Content-Disposition: inline; filename="progress-image"');
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');
readfile($filePath);
exit;
