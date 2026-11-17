<?php
declare(strict_types=1);

define('CLIENT_ROOT', dirname(__DIR__));
require_once dirname(__DIR__, 2) . '/backend/config/bootstrap.php';
require_once CLIENT_ROOT . '/api/ClientApi.php';
require_once CLIENT_ROOT . '/includes/helpers.php';

function client_start_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('tourisme_client');
    $secure = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
        || strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https'
        || strtolower((string)($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '')) === 'on';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

client_start_session();
