<?php
declare(strict_types=1);

/** Extension additive du référentiel géographique. Ne supprime ni table ni donnée. */
return static function (PDO $pdo): void {
    $hasColumn = static function (string $table, string $column) use ($pdo): bool {
        $q = $pdo->prepare('SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=?');
        $q->execute([$table, $column]);
        return (bool)$q->fetchColumn();
    };
    $hasIndex = static function (string $table, string $index) use ($pdo): bool {
        $q = $pdo->prepare('SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name=? AND index_name=?');
        $q->execute([$table, $index]);
        return (bool)$q->fetchColumn();
    };
    $hasUniqueColumn = static function (string $table, string $column) use ($pdo): bool {
        $q = $pdo->prepare('SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name=? AND column_name=? AND non_unique=0');
        $q->execute([$table, $column]);
        return (bool)$q->fetchColumn();
    };
    $hasForeignKey = static function (string $table, string $name) use ($pdo): bool {
        $q = $pdo->prepare("SELECT 1 FROM information_schema.table_constraints WHERE constraint_schema=DATABASE() AND table_name=? AND constraint_name=? AND constraint_type='FOREIGN KEY'");
        $q->execute([$table, $name]);
        return (bool)$q->fetchColumn();
    };
    foreach (['districts','regions','departements'] as $table) {
        if (!$pdo->query('SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=' . $pdo->quote($table))->fetchColumn()) {
            throw new RuntimeException('Table géographique absente : ' . $table . '. Appliquez d’abord le schéma Tourisme Ivoire.');
        }
    }
    if (!$hasColumn('districts','code')) $pdo->exec('ALTER TABLE districts ADD COLUMN code VARCHAR(30) NULL AFTER nom');
    if (!$hasColumn('districts','type')) $pdo->exec("ALTER TABLE districts ADD COLUMN type ENUM('ordinaire','autonome') NOT NULL DEFAULT 'ordinaire' AFTER code");
    if (!$hasColumn('districts','updated_at')) $pdo->exec('ALTER TABLE districts ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    if (!$hasColumn('regions','code')) $pdo->exec('ALTER TABLE regions ADD COLUMN code VARCHAR(30) NULL AFTER nom');
    if (!$hasColumn('regions','district_id')) $pdo->exec('ALTER TABLE regions ADD COLUMN district_id BIGINT UNSIGNED NULL AFTER id');
    if (!$hasColumn('regions','updated_at')) $pdo->exec('ALTER TABLE regions ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    if (!$hasColumn('departements','code')) $pdo->exec('ALTER TABLE departements ADD COLUMN code VARCHAR(30) NULL AFTER nom');
    if (!$hasColumn('departements','updated_at')) $pdo->exec('ALTER TABLE departements ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    foreach ([['districts','uq_districts_code'],['regions','uq_regions_code'],['departements','uq_departements_code']] as [$table,$idx]) {
        if (!$hasUniqueColumn($table,'code')) $pdo->exec('ALTER TABLE ' . $table . ' ADD UNIQUE KEY ' . $idx . ' (code)');
    }
    if (!$hasIndex('regions','idx_regions_district')) $pdo->exec('ALTER TABLE regions ADD KEY idx_regions_district (district_id)');
    if (!$hasForeignKey('regions','fk_regions_district')) $pdo->exec('ALTER TABLE regions ADD CONSTRAINT fk_regions_district FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE SET NULL ON UPDATE CASCADE');
};
