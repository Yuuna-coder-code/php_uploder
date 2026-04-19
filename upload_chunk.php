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
    echo json_encode(['success' => false, 'message' => 'Invalid upload request']);
    exit;
}

$chunkDir = TEMP_DIR . $uploadId;
if (!is_dir($chunkDir)) mkdir($chunkDir, 0777, true);

$chunkPath = $chunkDir . '/chunk_' . $chunkNumber;
if (move_uploaded_file($_FILES['file']['tmp_name'], $chunkPath)) {
    echo json_encode(['success' => true, 'message' => "Chunk {$chunkNumber} uploaded"]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save chunk']);
}

?>
