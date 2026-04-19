<?php
require_once 'config.php';

// Weka CORS moja kwa moja hapa. Achana na apply_cors kwanza
$allowed_origins = [
    "https://zitod-admin-front-2.vercel.app",
    "http://localhost:5173",
    "http://localhost:3000"
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
}

header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Max-Age: 86400");
header('Content-Type: application/json');

// Jibu OPTIONS hapa hapa na toka. Usiruhusu iende chini
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(); // Hii ndio muhimu - isiendelee
}

// Kuanzia hapa ni kwa POST tu
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

$chunkDir = TEMP_DIR . $uploadId;
if (!is_dir($chunkDir)) {
    if (!mkdir($chunkDir, 0777, true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create temp directory']);
        exit;
    }
}

$chunkPath = $chunkDir . '/chunk_' . $chunkNumber;
if (move_uploaded_file($_FILES['file']['tmp_name'], $chunkPath)) {
    echo json_encode(['success' => true, 'message' => "Chunk {$chunkNumber} uploaded"]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save chunk']);
}

?>