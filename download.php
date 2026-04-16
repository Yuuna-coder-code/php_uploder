<?php
require_once 'config.php';

apply_cors('GET, OPTIONS');

// Angalia kama parameter 'file' imetumwa
if (!isset($_GET['filePath'])) {
    http_response_code(400);
    echo "Missing file parameter";
    exit();
}

$filePath = resolve_managed_file_path($_GET['filePath']);

if (!$filePath) {
    http_response_code(400);
    echo "Invalid file parameter";
    exit();
}

if (!file_exists($filePath)) {
    http_response_code(404);
    echo "File not found";
    exit();
}

// Tambua mime type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $filePath);
finfo_close($finfo);

// Soma faili na uitoe
header("Content-Type: $mimeType");
header("Content-Disposition: attachment; filename=\"" . basename($filePath) . "\"");
header("Content-Length: " . filesize($filePath));
header("Cache-Control: no-cache");
readfile($filePath);
exit();
?>
