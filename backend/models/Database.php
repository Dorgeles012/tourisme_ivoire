<?php
declare(strict_types=1);

final class Database {
    private static ?PDO $connection = null;

    public static function connection(): PDO {
        if (self::$connection instanceof PDO) return self::$connection;

        $host = env_value('DB_HOST', '127.0.0.1');
        $name = env_value('DB_NAME', 'tourisme_ivoire');
        $charset = env_value('DB_CHARSET', 'utf8mb4');
        $dsn = "mysql:host={$host};dbname={$name};charset={$charset}";
        self::$connection = new PDO(
            $dsn,
            env_value('DB_USER', 'root') ?? 'root',
            env_value('DB_PASSWORD', '') ?? '',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        );
        return self::$connection;
    }
}
