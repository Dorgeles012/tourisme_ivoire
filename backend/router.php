<?php
declare(strict_types=1);

// Router for PHP's built-in server. Public assets and admin pages are served
// directly; all virtual API routes are sent to the public front controller.
$publicRoot = realpath(__DIR__ . '/public');
if ($publicRoot === false) {
    http_response_code(500);
    exit('Le répertoire public du backend est introuvable.');
}
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$relativePath = ltrim(str_replace('/', DIRECTORY_SEPARATOR, $requestPath), DIRECTORY_SEPARATOR);
$requestedFile = __DIR__ . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $relativePath;
$realFile = realpath($requestedFile);

if ($requestPath !== '/' && $realFile !== false
    && str_starts_with($realFile, $publicRoot . DIRECTORY_SEPARATOR) && is_file($realFile)) {
    return false;
}

// Let PHP's built-in server resolve directory URLs such as /client/ to
// their index.php instead of sending them to the API front controller.
if ($requestPath !== '/' && $realFile !== false && is_dir($realFile)
    && str_starts_with($realFile, $publicRoot . DIRECTORY_SEPARATOR)) {
    $indexFile = realpath($realFile . DIRECTORY_SEPARATOR . 'index.php');
    if ($indexFile !== false
        && str_starts_with($indexFile, $publicRoot . DIRECTORY_SEPARATOR)
        && is_file($indexFile)) {
        return false;
    }
}

require $publicRoot . '/index.php';
