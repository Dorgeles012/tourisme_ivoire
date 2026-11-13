<?php
declare(strict_types=1);
// Keep startup/runtime notices out of API bodies while retaining them in logs.
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
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
// Under Apache mod_rewrite SCRIPT_NAME may refer to index.php or to the
// rewritten request. Normalize from REQUEST_URI so both forms route correctly.
$apiOffset = strpos($requestPath, '/api/');
if ($apiOffset !== false) {
    $path = substr($requestPath, $apiOffset);
} else {
    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    $path = $basePath !== '' && str_starts_with($requestPath, $basePath)
        ? substr($requestPath, strlen($basePath))
        : $requestPath;
}
$path = '/' . trim($path, '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
try {
    if ($method === 'POST' && $path === '/api/auth/register') AuthController::register();
    if ($method === 'POST' && $path === '/api/auth/login') AuthController::login();
    if ($method === 'POST' && $path === '/api/auth/logout') AuthController::logout();
    if ($method === 'GET' && $path === '/api/auth/me') AuthController::me();
    if ($method === 'PUT' && $path === '/api/auth/me') AuthController::updateMe();
    if ($method === 'POST' && $path === '/api/auth/me/avatar') AuthController::uploadAvatar();
    if ($method === 'POST' && $path === '/api/auth/forgot-password') AuthController::forgotPassword();
    if ($method === 'POST' && $path === '/api/auth/reset-password') AuthController::resetPassword();
    if ($method === 'POST' && $path === '/api/admin/handoff') AdminHandoffController::create();
    if ($method === 'POST' && $path === '/api/admin/handoff/consume') AdminHandoffController::consume();
    if ($method === 'GET' && $path === '/api/destinations') DestinationController::index();
    if ($method === 'GET' && $path === '/api/destinations/featured') DestinationController::featured();
    if ($method === 'GET' && preg_match('#^/api/destinations/(\d+)$#', $path, $m)) DestinationController::show((int)$m[1]);
    if ($method === 'GET' && $path === '/api/destinations/search') DestinationController::index();
    if ($method === 'GET' && $path === '/api/categories') ReferenceController::categories();
    if ($method === 'GET' && $path === '/api/regions') ReferenceController::regions();
    if ($method === 'GET' && $path === '/api/districts') ReferenceController::districts();
    if ($method === 'GET' && $path === '/api/localites') ReferenceController::localites();
    if ($method === 'GET' && $path === '/api/langues') ReferenceController::languages();
    if ($method === 'GET' && $path === '/api/favorites') FavoriteController::index();
    if ($method === 'POST' && $path === '/api/favorites') FavoriteController::store();
    if ($method === 'DELETE' && preg_match('#^/api/favorites/(\d+)$#', $path, $m)) FavoriteController::destroy((int)$m[1]);
    if ($method === 'GET' && $path === '/api/transports') TransportController::transports();
    if ($method === 'GET' && $path === '/api/itineraires') TransportController::itineraries();
    if ($method === 'POST' && $path === '/api/itineraires') TransportController::search();
    if ($method === 'POST' && $path === '/api/reservations') TransportController::reserve();
    if ($method === 'GET' && $path === '/api/reservations') TransportController::reservations();
    if ($method === 'GET' && $path === '/api/videos') VideoController::index();
    if ($method === 'POST' && $path === '/api/videos') VideoController::upload();
    if ($method === 'GET' && preg_match('#^/api/videos/(\d+)/comments$#', $path, $m)) VideoController::comments((int)$m[1]);
    if ($method === 'POST' && preg_match('#^/api/videos/(\d+)/like$#', $path, $m)) VideoController::toggleLike((int)$m[1]);
    if ($method === 'POST' && preg_match('#^/api/videos/(\d+)/comment$#', $path, $m)) VideoController::comment((int)$m[1]);
    if ($method === 'GET' && preg_match('#^/api/videos/(\d+)$#', $path, $m)) VideoController::show((int)$m[1]);
    if ($method === 'GET' && $path === '/api/quizzes') QuizController::index();
    if ($method === 'GET' && $path === '/api/quizzes/results') QuizController::results();
    if ($method === 'POST' && preg_match('#^/api/quizzes/(\d+)/submit$#', $path, $m)) QuizController::submit((int)$m[1]);
    if ($method === 'GET' && preg_match('#^/api/quizzes/(\d+)$#', $path, $m)) QuizController::show((int)$m[1]);
    if ($method === 'GET' && $path === '/api/passport') PassportController::show();
    if ($method === 'GET' && $path === '/api/passport/badges') PassportController::badges();
    if ($method === 'GET' && $path === '/api/passport/visits') PassportController::visits();
    if ($method === 'POST' && $path === '/api/passport/visits') PassportController::recordVisit();
    if ($method === 'GET' && $path === '/api/notifications') NotificationController::index();
    if ($method === 'PUT' && preg_match('#^/api/notifications/(\d+)/read$#', $path, $m)) NotificationController::markRead((int)$m[1]);
    if ($method === 'GET' && $path === '/api/settings') SettingsController::show();
    if ($method === 'PUT' && $path === '/api/settings') SettingsController::update();
    if ($method === 'GET' && $path === '/api/patrimoines') HeritageController::index();
    if ($method === 'GET' && $path === '/api/visites-virtuelles') HeritageController::virtualVisits();
    if ($method === 'POST' && preg_match('#^/api/patrimoines/(\d+)/view$#', $path, $m)) HeritageController::recordView((int)$m[1]);
    api_error('route_not_found', 'Route API introuvable.', 404);
} catch (Throwable $e) {
    error_log((string)$e);
    $error = ['code' => 'server_error', 'message' => 'Une erreur interne est survenue.'];
    // Expose the exception only in the local XAMPP development environment.
    // Production responses must not disclose SQL or filesystem details.
    if (env_value('APP_ENV', 'production') === 'local') {
        $error['details'] = $e->getMessage();
    }
    json_response(['error' => $error], 500);
}
