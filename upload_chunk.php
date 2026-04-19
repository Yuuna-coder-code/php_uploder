<?php
require_once 'config.php';

apply_cors('POST, OPTIONS');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$uploadId = $_POST['uploadId'] ?? null;
$chunkNumber = $_POST['chunkNumber'] ?? null;

if (!$uploadId || $chunkNumber === null || !isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid upload request']);
    exit;
}

$chunkDir = TEMP_DIR . '/' . $uploadId;
ensure_directory($chunkDir);

$chunkPath = $chunkDir . '/chunk_' . $chunkNumber;
if (move_uploaded_file($_FILES['file']['tmp_name'], $chunkPath)) {
    echo json_encode(['success' => true, 'message' => "Chunk {$chunkNumber} uploaded"]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save chunk']);
}
?>