<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/admin/bootstrap.php';
admin_require_login();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    exit('Méthode non autorisée.');
}
admin_check_csrf();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], '', $params['secure'], $params['httponly']);
}
session_destroy();
header('Location: login.php');
