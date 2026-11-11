<?php
declare(strict_types=1);

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $host = env_value('DB_HOST', '127.0.0.1');
    $name = env_value('DB_NAME', 'tourisme_ivoire');
    $charset = env_value('DB_CHARSET', 'utf8mb4');
    $dsn = "mysql:host={$host};dbname={$name};charset={$charset}";
    $pdo = new PDO($dsn, env_value('DB_USER', 'root') ?? 'root', env_value('DB_PASSWORD', '') ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}
