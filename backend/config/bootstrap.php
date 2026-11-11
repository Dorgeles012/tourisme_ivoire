<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
function env_value(string $key, ?string $default = null): ?string {
    static $values = null;
    if ($values === null) {
        $values = [];
        $file = APP_ROOT . '/.env';
        if (is_file($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
                [$name, $value] = explode('=', $line, 2);
                $values[trim($name)] = trim(trim($value), " \"");
            }
        }
    }
    return $_ENV[$key] ?? getenv($key) ?: ($values[$key] ?? $default);
}
require_once APP_ROOT . '/utils/response.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/services/JwtService.php';
require_once APP_ROOT . '/services/BadgeService.php';
require_once APP_ROOT . '/middleware/AuthMiddleware.php';
