<?php

header('Content-Type: application/json');

echo json_encode([
    'service' => 'php-uploader',
    'status' => 'ok',
    'endpoints' => [
        'POST /upload_chunk.php',
        'POST /merge_chunks.php',
        'GET /download.php?filePath=...',
        'GET /fileDel.php?filePath=...'
    ]
]);
