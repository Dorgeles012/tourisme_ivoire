<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('tourisme_admin');
    $cookiePath = rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/index.php')),'/');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $cookiePath === '' ? '/' : $cookiePath,
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

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
    return $_SESSION['admin_user'];
}

function admin_require_role(array $roles): array {
    $user = admin_require_login();
    $stmt = db()->prepare('SELECT r.code FROM utilisateurs_roles ur JOIN roles r ON r.id=ur.role_id WHERE ur.utilisateur_id=?');
    $stmt->execute([$user['id']]);
    $userRoles = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (!array_intersect($roles, $userRoles)) {
        http_response_code(403);
        exit('Accès réservé à un administrateur ou éditeur autorisé.');
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
