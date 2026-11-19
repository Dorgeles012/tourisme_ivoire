<?php
declare(strict_types=1);

$assets = [
    'portal.css' => ['text/css; charset=UTF-8', 'client/assets/portal.css'],
    'portal.js' => ['application/javascript; charset=UTF-8', 'client/assets/portal.js'],
    'alerts.css' => ['text/css; charset=UTF-8', 'client/assets/alerts.css'],
    'alerts.js' => ['application/javascript; charset=UTF-8', 'client/assets/alerts.js'],
];
$name = (string)($_GET['file'] ?? '');
if (!isset($assets[$name])) {
    http_response_code(404);
    header('X-Content-Type-Options: nosniff');
    exit;
}
[$contentType, $relativePath] = $assets[$name];
$projectRoot = dirname(__DIR__, 3);
$filePath = realpath($projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
$assetRoot = realpath($projectRoot . DIRECTORY_SEPARATOR . 'client' . DIRECTORY_SEPARATOR . 'assets');
if ($filePath === false || $assetRoot === false || !is_file($filePath)
    || !str_starts_with($filePath, $assetRoot . DIRECTORY_SEPARATOR)) {
    http_response_code(404);
    header('X-Content-Type-Options: nosniff');
    exit;
}
header('Content-Type: ' . $contentType);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=3600');
header('Content-Length: ' . (string)filesize($filePath));
readfile($filePath);
