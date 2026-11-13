<?php
declare(strict_types=1);

/** Applique le schéma SQL puis les migrations PHP versionnées de ../database/migrations. */
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

function skipExistingIndexes(string $sql, PDO $pdo): string {
    return preg_replace_callback(
        '/CREATE\s+(?:FULLTEXT\s+)?INDEX\s+([A-Za-z0-9_]+)\s+ON\s+([A-Za-z0-9_]+)\s*\([^;]*\)\s*;/i',
        static function (array $match) use ($pdo): string {
            $check = $pdo->prepare('SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1');
            $check->execute([$match[2],$match[1]]);
            return $check->fetchColumn() ? '' : $match[0];
        },
        $sql
    ) ?? $sql;
}

function applyPhpMigrations(PDO $pdo, string $directory, bool $statusOnly = false): void {
    $files = glob(rtrim($directory, '/\\') . '/*.php') ?: [];
    sort($files, SORT_STRING);
    if ($statusOnly) {
        $registryExists = $pdo->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='schema_migrations'");
        $registryExists->execute();
        if (!$registryExists->fetchColumn()) {
            foreach ($files as $file) echo 'en attente — ' . basename($file) . "\n";
            return;
        }
    } else {
        $pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (migration VARCHAR(255) PRIMARY KEY, applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB');
    }
    $check = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE migration=?');
    $record = $pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (?)');
    foreach ($files as $file) {
        $name = basename($file);
        $check->execute([$name]);
        $applied = (bool)$check->fetchColumn();
        if ($statusOnly) {
            echo ($applied ? 'appliquée' : 'en attente') . ' — ' . $name . "\n";
            continue;
        }
        if ($applied) continue;
        echo "Application de {$name}... ";
        $migration = require $file;
        if (!is_callable($migration)) throw new RuntimeException("Migration PHP invalide : {$name}");
        $migration($pdo);
        $record->execute([$name]);
        echo "OK\n";
    }
}

$databaseDir = realpath(__DIR__ . '/../database');
$files = $databaseDir ? glob($databaseDir . '/*.sql') : false;
if (!$databaseDir || !$files) {
    fwrite(STDERR, "Aucun script SQL trouvé dans ../database.\n");
    exit(1);
}
natsort($files);
$files = array_values($files);
// Installation vide : seules la structure, les index et les vues sont appliqués.
$structureFiles = ['01_create_database.sql','02_create_tables.sql','14_indexes.sql','15_views.sql'];
$files = array_values(array_filter($files, static fn(string $file): bool => in_array(basename($file),$structureFiles,true)));
if (!$files) {
    $singleFileSchema = $databaseDir . '/tourisme_ivoire.sql';
    if (!is_file($singleFileSchema)) {
        fwrite(STDERR,"Aucun schéma d’installation reconnu dans ../database.\n");
        exit(1);
    }
    if (in_array('--status',$argv ?? [],true)) {
        echo "Schéma complet disponible : database/tourisme_ivoire.sql.\n";
        $databaseExists = $pdo->prepare('SELECT 1 FROM information_schema.schemata WHERE schema_name=?');
        $databaseExists->execute([$dbName]);
        $hasDatabase = (bool)$databaseExists->fetchColumn();
        $hasRegistry = false;
        if ($hasDatabase) {
            $pdo->exec('USE tourisme_ivoire');
            $hasRegistry = (bool)$pdo->query("SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='schema_migrations'")->fetchColumn();
        }
        $migrationFiles = glob($databaseDir . '/migrations/*.php') ?: [];
        sort($migrationFiles, SORT_STRING);
        $check = $hasRegistry ? $pdo->prepare('SELECT 1 FROM schema_migrations WHERE migration=?') : null;
        foreach ($migrationFiles as $migrationFile) {
            $name = basename($migrationFile);
            $check?->execute([$name]);
            echo (($check && $check->fetchColumn()) ? 'appliquée' : 'en attente') . ' — ' . $name . "\n";
        }
        exit(0);
    }
    try {
        $pdo->exec('CREATE DATABASE IF NOT EXISTS tourisme_ivoire CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo->exec('USE tourisme_ivoire');
        $sql = file_get_contents($singleFileSchema);
        if ($sql===false) throw new RuntimeException('Lecture impossible : tourisme_ivoire.sql');
        $sql = skipExistingIndexes($sql,$pdo);
        $pdo->exec($sql);
        applyPhpMigrations($pdo, $databaseDir . '/migrations');
        echo "Schéma appliqué depuis database/tourisme_ivoire.sql; aucun jeu de données de démonstration n’a été chargé.\n";
        exit(0);
    } catch (Throwable $e) {
        fwrite(STDERR,"Échec de l’installation du schéma : {$e->getMessage()}\n");
        exit(1);
    }
}
if (basename($files[0]) !== '01_create_database.sql') {
    fwrite(STDERR, "Le premier script doit être 01_create_database.sql.\n");
    exit(1);
}

if (in_array('--status', $argv ?? [], true)) {
    $databaseExists = $pdo->prepare('SELECT 1 FROM information_schema.schemata WHERE schema_name=?');
    $databaseExists->execute([$dbName]);
    if (!$databaseExists->fetchColumn()) {
        echo "La base {$dbName} n’existe pas; tous les scripts SQL et PHP sont en attente.\n";
        foreach (array_slice($files, 1) as $file) echo 'en attente — ' . basename($file) . "\n";
        applyPhpMigrations($pdo, realpath(__DIR__ . '/../database/migrations') ?: __DIR__ . '/../database/migrations', true);
        exit(0);
    }
    $pdo->exec('USE tourisme_ivoire');
    $registryExists = $pdo->query("SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='schema_migrations'")->fetchColumn();
    $applied = $registryExists ? $pdo->query('SELECT migration FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN) : [];
    $appliedSet = array_fill_keys($applied, true);
    $pending = array_values(array_filter(array_slice($files, 1), static fn(string $file): bool => !isset($appliedSet[basename($file)])));
    echo count($appliedSet) . " migration(s) enregistrée(s), " . count($pending) . " en attente.\n";
    foreach ($pending as $file) echo ' - ' . basename($file) . "\n";
    applyPhpMigrations($pdo, realpath(__DIR__ . '/../database/migrations') ?: __DIR__ . '/../database/migrations', true);
    exit(0);
}

try {
    // 01 crée la base et la sélectionne; le registre conserve l'état des scripts suivants.
    $pdo->exec((string) file_get_contents(array_shift($files)));
    $pdo->exec('USE tourisme_ivoire');
    $pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (migration VARCHAR(255) PRIMARY KEY, applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB');
    $applied = $pdo->query('SELECT migration FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
    $appliedSet = array_fill_keys($applied, true);
    $pending = array_values(array_filter($files, static fn(string $file): bool => !isset($appliedSet[basename($file)])));

    echo "Base cible : {$dbName} — " . count($pending) . " migration(s) en attente.\n";
    foreach ($pending as $file) {
        $name = basename($file);
        $sql = file_get_contents($file);
        if ($sql === false) throw new RuntimeException("Lecture impossible : {$name}");
        $sql = skipExistingIndexes($sql,$pdo);

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
    applyPhpMigrations($pdo, realpath(__DIR__ . '/../database/migrations') ?: __DIR__ . '/../database/migrations');
    echo "Structure vide créée pour {$dbName}; aucun jeu de données n'a été chargé.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Erreur de migration : {$e->getMessage()}\n");
    exit(1);
}
