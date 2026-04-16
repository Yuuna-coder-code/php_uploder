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
$originalFileName = $_POST['fileName'] ?? null;
$mimeType = $_POST['mimeType'] ?? null;
$totalChunks = (int)($_POST['totalChunks'] ?? 0);
$refId = (int)($_POST['refId'] ?? 0);
$type = strtolower($_POST['type'] ?? ''); // home, research, news, user

// Validation
if (!$uploadId || !$originalFileName || !$mimeType || $totalChunks <= 0 || !$refId || !$type) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}
$validTypes = ['home', 'research', 'news', 'user', 'gallery'];
if (!in_array($type, $validTypes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid type']);
    exit;
}

$typeUploadDir = join_path(UPLOAD_DIR, $type);

$chunkDir = TEMP_DIR . $uploadId;
if (!is_dir($chunkDir)) {
    echo json_encode(['success' => false, 'message' => 'Upload session not found']);
    exit;
}

// Create final file
ensure_directory($typeUploadDir);
$extension = pathinfo($originalFileName, PATHINFO_EXTENSION);
$uniqueName = 'ZIToD_' . time() . '_' . bin2hex(random_bytes(4));
if ($extension !== '') {
    $uniqueName .= '.' . $extension;
}
$finalPath = join_path($typeUploadDir, $uniqueName);
$finalFile = fopen($finalPath, 'wb');
if (!$finalFile) {
    echo json_encode(['success' => false, 'message' => 'Cannot create final file']);
    exit;
}

$missing = false;
for ($i = 0; $i < $totalChunks; $i++) {
    $chunkPath = $chunkDir . '/chunk_' . $i;
    if (!file_exists($chunkPath)) {
        $missing = true;
        break;
    }
    fwrite($finalFile, file_get_contents($chunkPath));
}
fclose($finalFile);

if ($missing) {
    unlink($finalPath);
    echo json_encode(['success' => false, 'message' => 'Missing chunks']);
    exit;
}

// Cleanup temp folder
array_map('unlink', glob("$chunkDir/*"));
rmdir($chunkDir);

$fileSize = filesize($finalPath);
$fileUrl = get_public_file_url($type, $uniqueName);

// ** Tuma metadata kwa Spring Boot **
$metadata = [
    'fileName' => $uniqueName,
    'filePath' => $finalPath,
    'mimeType' => $mimeType,
    'size' => $fileSize,
    'url' => $fileUrl,
    'refId' => $refId,
    'type' => $type
];

$ch = curl_init(SPRING_BOOT_API_URL);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($metadata));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode >= 200 && $httpCode < 300) {
    echo json_encode(['success' => true, 'message' => 'File merged and metadata sent to Spring Boot', 'file' => $uniqueName]);

} else {
    // Ikiwa Spring Boot imeshindwa, bado faili ipo lakini metadata haijahifadhiwa
    if(file_exists($finalPath)) {
        unlink($finalPath);
    }
    
    echo json_encode(['success' => false, 'message' => 'File saved but failed to notify Spring Boot']);
}

?>
