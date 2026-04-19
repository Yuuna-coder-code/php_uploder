<?php

function env_value($key, $default = null)
{
    $value = getenv($key);

    if ($value === false || $value === '') {
        return $default;
    }

    return $value;
}

function normalize_path($path)
{
    return rtrim(str_replace('\\', '/', $path), '/');
}

function join_path($basePath, $suffix = '')
{
    $basePath = rtrim($basePath, DIRECTORY_SEPARATOR . '/\\');

    if ($suffix === '') {
        return $basePath;
    }

    return $basePath . DIRECTORY_SEPARATOR . ltrim($suffix, DIRECTORY_SEPARATOR . '/\\');
}

function ensure_directory($path)
{
    if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to prepare storage directories']);
        exit;
    }
}

function get_allowed_origins()
{
    $origins = array_filter(array_map('trim', explode(',', env_value('ALLOWED_ORIGINS', 'https://zitod-admin-front-2.vercel.app/'))));

    if (empty($origins)) {
        return ['https://zitod-admin-front-2.vercel.app/','http://localhost:5173',];
    }

    return $origins;
}

function apply_cors($methods = 'GET, POST, OPTIONS')
{
    $allowedOrigins = get_allowed_origins();
    $requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';

    if (in_array('*', $allowedOrigins, true)) {
        header('Access-Control-Allow-Origin: *');
    } elseif ($requestOrigin !== '' && in_array($requestOrigin, $allowedOrigins, true)) {
        header('Access-Control-Allow-Origin: ' . $requestOrigin);
        header('Vary: Origin');
    } else {
        header('Access-Control-Allow-Origin: ' . $allowedOrigins[0]);
    }

    header('Access-Control-Allow-Methods: ' . $methods);
    header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization');
    header('Access-Control-Allow-Credentials: true');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

function get_public_base_url()
{
    $configuredUrl = env_value('PHP_ROOT_URL');
    if ($configuredUrl) {
        return rtrim($configuredUrl, '/');
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $scriptDir = $scriptDir === '/' || $scriptDir === '.' ? '' : rtrim($scriptDir, '/');

    return $scheme . '://' . $host . $scriptDir;
}

function get_storage_root()
{
    $storageRoot = env_value('STORAGE_ROOT');

    if (!$storageRoot) {
        $volumePath = env_value('RAILWAY_VOLUME_MOUNT_PATH');
        if ($volumePath) {
            $storageRoot = $volumePath;
        } else {
            $storageRoot = join_path(__DIR__, 'uploads');
        }
    }

    return $storageRoot;
}

function get_public_file_url($type, $fileName)
{
    return FILE_BASE_URL . rawurlencode($type) . '/' . rawurlencode($fileName);
}

function resolve_managed_file_path($filePath)
{
    if (!$filePath) {
        return null;
    }

    $normalizedStorageRoot = normalize_path(STORAGE_ROOT);

    if (preg_match('#^https?://#i', $filePath)) {
        $path = parse_url($filePath, PHP_URL_PATH);
        if (!$path) {
            return null;
        }

        $uploadsPrefix = '/uploads/files/';
        $position = strpos($path, $uploadsPrefix);
        if ($position === false) {
            return null;
        }

        $relativePath = substr($path, $position + strlen($uploadsPrefix));
        $relativePath = rawurldecode(ltrim($relativePath, '/'));
    } else {
        $normalizedInput = normalize_path($filePath);

        if (strpos($normalizedInput, $normalizedStorageRoot) === 0) {
            $relativePath = ltrim(substr($normalizedInput, strlen($normalizedStorageRoot)), '/');
        } else {
            $relativePath = ltrim(str_replace('uploads/files/', '', $normalizedInput), '/');
        }
    }

    if ($relativePath === '' || strpos($relativePath, '..') !== false) {
        return null;
    }

    return join_path(UPLOAD_DIR, str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
}

define('SPRING_BOOT_API_URL', env_value('SPRING_BOOT_API_URL', 'https://zitodbackendm-production.up.railway.app/api/attachment/php-upload'));
define('PHP_ROOT_URL', get_public_base_url());
define('STORAGE_ROOT', get_storage_root());
define('UPLOAD_DIR', join_path(STORAGE_ROOT, 'files'));
define('TEMP_DIR', join_path(STORAGE_ROOT, 'temp'));
define('FILE_BASE_URL', PHP_ROOT_URL . '/uploads/files/');

ensure_directory(UPLOAD_DIR);
ensure_directory(TEMP_DIR);
