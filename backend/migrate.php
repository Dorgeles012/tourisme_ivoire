<?php
declare(strict_types=1);

/** Applique une fois, dans l'ordre, chaque script SQL de ../database. */
require_once __DIR__ . '/config/bootstrap.php';

$host = env_value('DB_HOST', '127.0.0.1');
$dbName = env_value('DB_NAME', 'tourisme_ivoire');
$user = env_value('DB_USER', 'root') ?? 'root';
$password = env_value('DB_PASSWORD', '') ?? '';
$charset = env_value('DB_CHARSET', 'utf8mb4');
if ($dbName !== 'tourisme_ivoire') {
    fwrite(STDERR, "Migration interrompue : DB_NAME doit être tourisme_ivoire.\n");
    exit(1);
}

try {
    $pdo = new PDO("mysql:host={$host};charset={$charset}", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, "Connexion MySQL impossible : {$e->getMessage()}\n");
    exit(1);
}

$databaseDir = realpath(__DIR__ . '/../database');
$files = $databaseDir ? glob($databaseDir . '/*.sql') : false;
if (!$databaseDir || !$files) {
    fwrite(STDERR, "Aucun script SQL trouvé dans ../database.\n");
    exit(1);
}
natsort($files);
$files = array_values($files);
if (basename($files[0]) !== '01_create_database.sql') {
    fwrite(STDERR, "Le premier script doit être 01_create_database.sql.\n");
    exit(1);
}

try {
    // 01 crée la base et la sélectionne; le registre conserve l'état des scripts suivants.
    $pdo->exec((string) file_get_contents(array_shift($files)));
    $pdo->exec('USE tourisme_ivoire');
    $pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (migration VARCHAR(255) PRIMARY KEY, applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB');
    $applied = $pdo->query('SELECT migration FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
    $appliedSet = array_fill_keys($applied, true);
    $pending = array_values(array_filter($files, static fn(string $file): bool => !isset($appliedSet[basename($file)])));

    if (in_array('--status', $argv ?? [], true)) {
        echo count($appliedSet) . " migration(s) enregistrée(s), " . count($pending) . " en attente.\n";
        foreach ($pending as $file) echo ' - ' . basename($file) . "\n";
        exit(0);
    }

    echo "Base cible : {$dbName} — " . count($pending) . " migration(s) en attente.\n";
    foreach ($pending as $file) {
        $name = basename($file);
        $sql = file_get_contents($file);
        if ($sql === false) throw new RuntimeException("Lecture impossible : {$name}");
        // Permet de reprendre une base où certains index avaient déjà été créés.
        $sql = preg_replace_callback(
            '/CREATE\s+(?:FULLTEXT\s+)?INDEX\s+([A-Za-z0-9_]+)\s+ON\s+([A-Za-z0-9_]+)\s*\([^;]*\)\s*;/i',
            static function (array $match) use ($pdo): string {
                $check = $pdo->prepare('SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1');
                $check->execute([$match[2], $match[1]]);
                return $check->fetchColumn() ? '' : $match[0];
            },
            $sql
        ) ?? $sql;

        echo "Application de {$name}... ";
        try {
            if (trim($sql) !== '') $pdo->exec($sql);
            $record = $pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (?)');
            $record->execute([$name]);
            echo "OK\n";
        } catch (Throwable $e) {
            fwrite(STDERR, "\nÉchec dans {$name} : {$e->getMessage()}\n");
            exit(1);
        }
    }
    // Activer les contenus de base pour l'affichage dans l'application mobile Flutter
    $pdo->exec("UPDATE destinations SET statut_publication='publie', published_at=NOW() WHERE statut_publication='brouillon'");
    $pdo->exec("UPDATE quiz SET statut='publie' WHERE statut='brouillon'");
    $pdo->exec("UPDATE patrimoines SET statut='publie' WHERE statut='brouillon'");
    echo "Migrations terminées pour {$dbName}. Les contenus sont prêts pour Flutter.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Erreur de migration : {$e->getMessage()}\n");
    exit(1);
}
