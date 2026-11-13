<?php
declare(strict_types=1);

/** Rename tables with no code consumer; existing rows and constraints are preserved. */
return static function (PDO $pdo): void {
    $schema = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
    $tables = [
        'role_permissions',
        'destination_categories',
        'horaires_destinations',
        'abonnements',
        'signalements',
        'medias_patrimoine',
        'traductions',
        'historique_recherches',
        'contenus_hors_ligne',
    ];
    $exists = $pdo->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema=? AND table_name=? AND table_type='BASE TABLE'");
    foreach ($tables as $table) {
        $archive = $table . '_legacy_unwired_20261113';
        $exists->execute([$schema, $table]);
        $activeExists = (bool)$exists->fetchColumn();
        $exists->execute([$schema, $archive]);
        $archiveExists = (bool)$exists->fetchColumn();
        if ($activeExists && $archiveExists) {
            throw new RuntimeException('Les tables active et archive existent ensemble pour ' . $table . '; migration arrêtée sans suppression.');
        }
        if ($activeExists) {
            $pdo->exec('RENAME TABLE `' . $table . '` TO `' . $archive . '`');
        }
    }
};
