<?php
$ts = json_encode([
    'success' => true,
    'message' => 'File uploaded and metadata saved directly to database',
    'file' => "mudrik"
]);
$hh = $ts['file'];

echo $hh;
?>