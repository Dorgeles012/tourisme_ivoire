<?php
declare(strict_types=1);
 
ob_start();
ini_set('display_errors', '0');
ini_set('log_errors', '1');
require_once dirname(__DIR__) . '/config/bootstrap.php';
header('X-Content-Type-Options: nosniff');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = env_value('CORS_ORIGIN', '');
if ($origin !== '' && $allowed !== '' && hash_equals($allowed, $origin)) {
    header("Access-Control-Allow-Origin: $origin"); header('Vary: Origin');
}
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, DELETE, PUT, OPTIONS');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(204); exit; }
require_once APP_ROOT . '/controllers/AuthController.php';
require_once APP_ROOT . '/controllers/AdminHandoffController.php';
require_once APP_ROOT . '/controllers/DestinationController.php';
require_once APP_ROOT . '/controllers/ReferenceController.php';
require_once APP_ROOT . '/controllers/FavoriteController.php';
require_once APP_ROOT . '/controllers/TransportController.php';
require_once APP_ROOT . '/controllers/VideoController.php';
require_once APP_ROOT . '/controllers/QuizController.php';
require_once APP_ROOT . '/controllers/PassportController.php';
require_once APP_ROOT . '/controllers/NotificationController.php';
require_once APP_ROOT . '/controllers/SettingsController.php';
require_once APP_ROOT . '/controllers/HeritageController.php';
require_once APP_ROOT . '/routes/api.php';
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// Public URLs can be mounted at the domain root, behind a VirtualHost, or in
// any subdirectory. Keep API paths independent of that deployment prefix.
$apiOffset = strpos($requestPath, '/api/');
if ($apiOffset !== false) {
    $path = substr($requestPath, $apiOffset);
} else {
    $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    $path = $basePath !== '' && str_starts_with($requestPath, $basePath)
        ? substr($requestPath, strlen($basePath))
        : $requestPath;
}
$path = '/' . trim($path, '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
try {
    if (dispatch_api_route($method, $path)) exit;
    api_error('route_not_found', 'Route API introuvable.', 404);
} catch (Throwable $e) {
    error_log((string)$e);
    $error = ['code' => 'server_error', 'message' => 'Une erreur interne est survenue.'];
     
     
    if (env_value('APP_ENV', 'production') === 'local') {
        $error['details'] = $e->getMessage();
    }
    json_response(['error' => $error], 500);
}
