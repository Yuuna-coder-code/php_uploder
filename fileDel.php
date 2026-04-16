<?php
require_once 'config.php';

apply_cors('GET, OPTIONS');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$filePath = resolve_managed_file_path($_GET['filePath'] ?? null);

// Validation
if (!$filePath) {
    echo json_encode(['success' => false, 'message' => 'Missing or invalid required filePath']);
    exit;
}
if(file_exists($filePath)) {
    if(unlink($filePath)){
        echo json_encode(['success' => true, 'message' => 'Successful delete previous file']);
    }
    else{
        echo json_encode(['success' => false, 'message' => 'failed to delete previous file']);
    }

}
else{
    echo json_encode(['success' => false, 'message' => 'Previous file not found in php server']);

}

?>
