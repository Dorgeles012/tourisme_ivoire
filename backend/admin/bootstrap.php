<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';

function admin_start_session(?string $cookiePath = null): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_name('tourisme_admin');
    if ($cookiePath === null) {
        $cookiePath = rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/index.php')),'/');
        if ($cookiePath === '') $cookiePath = '/';
    }
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $cookiePath,
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

if (!defined('ADMIN_SESSION_DEFERRED')) admin_start_session();

function admin_escape(string|int|null $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function admin_csrf(): string {
    if (!isset($_SESSION['admin_csrf'])) $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['admin_csrf'];
}

function admin_check_csrf(): void {
    $provided = (string)($_POST['csrf'] ?? '');
    if (!isset($_SESSION['admin_csrf']) || !hash_equals($_SESSION['admin_csrf'], $provided)) {
        http_response_code(403);
        exit('Jeton CSRF invalide. Rechargez la page et réessayez.');
    }
}

function admin_require_login(): array {
    if (empty($_SESSION['admin_user']['id'])) {
        header('Location: login.php');
        exit;
    }
    $user = $_SESSION['admin_user'];
    $stmt = db()->prepare("SELECT id,nom,email FROM utilisateurs WHERE id=? AND statut='actif' AND deleted_at IS NULL AND `role`='Sadmin'");
    $stmt->execute([(int)$user['id']]);
    $currentUser = $stmt->fetch();
    if (!$currentUser) {
        $_SESSION = [];
        session_regenerate_id(true);
        header('Location: login.php');
        exit;
    }
    $_SESSION['admin_user'] = $currentUser;
    return $currentUser;
}

function admin_role_codes(int $userId): array {
    static $cache = [];
    if (!isset($cache[$userId])) {
        $stmt = db()->prepare('SELECT `role` FROM utilisateurs WHERE id=? AND deleted_at IS NULL');
        $stmt->execute([$userId]);
        $cache[$userId] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    return $cache[$userId];
}

function admin_can_manage_roles(?array $user = null): bool {
    $user = $user ?? admin_require_login();
    $codes = admin_role_codes((int)$user['id']);
    return in_array('Sadmin',$codes,true);
}

function admin_can_module(string $module, bool $manage = false, ?array $user = null): bool {
    $user = $user ?? admin_require_login();
    return in_array('Sadmin', admin_role_codes((int)$user['id']), true);
}

function admin_require_module(string $module, bool $manage = false): array {
    $user = admin_require_login();
    if (!admin_can_module($module,$manage,$user)) {
        http_response_code(403);
        exit('Accès réservé à un rôle autorisé pour ce module.');
    }
    return $user;
}

function admin_require_role_management(): array {
    $user = admin_require_login();
    if (!admin_can_manage_roles($user)) {
        http_response_code(403);
    exit('La gestion des rôles est réservée au Sadmin.');
    }
    return $user;
}

function admin_require_role(array $roles): array {
    $user = admin_require_login();
    $userRoles = admin_role_codes((int)$user['id']);
    if (!in_array('Sadmin',$userRoles,true) || !in_array('Sadmin',$roles,true)) {
        http_response_code(403);
        exit('Accès réservé au Sadmin.');
    }
    return $user;
}

function admin_flash(?string $message = null): ?string {
    if ($message !== null) {
        $_SESSION['admin_flash'] = $message;
        return null;
    }
    $current = $_SESSION['admin_flash'] ?? null;
    unset($_SESSION['admin_flash']);
    return is_string($current) ? $current : null;
}
