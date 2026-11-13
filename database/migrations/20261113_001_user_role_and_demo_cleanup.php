<?php
declare(strict_types=1);

/**
 * Migrate the normalized user-role join table to utilisateurs.role.
 * The former associations table is renamed and retained so this migration is reversible.
 */
return static function (PDO $pdo): void {
    $schema = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
    $tableExists = static function (string $table) use ($pdo, $schema): bool {
        $q = $pdo->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema=? AND table_name=?');
        $q->execute([$schema, $table]);
        return (bool)$q->fetchColumn();
    };
    $columnExists = static function (string $table, string $column) use ($pdo, $schema): bool {
        $q = $pdo->prepare('SELECT 1 FROM information_schema.columns WHERE table_schema=? AND table_name=? AND column_name=?');
        $q->execute([$schema, $table, $column]);
        return (bool)$q->fetchColumn();
    };

    $joinTable = 'utilisateurs_roles';
    $archiveTable = 'utilisateurs_roles_pre_role_column';
    $demoColumnExists = $columnExists('utilisateurs', 'is_demo');
    $hasRole = $columnExists('utilisateurs', 'role');
    $hasJoin = $tableExists($joinTable);
    $hasArchive = $tableExists($archiveTable);

    if (!$tableExists('utilisateurs') || !$tableExists('roles')) {
        throw new RuntimeException('Schéma incomplet : utilisateurs et roles doivent exister avant cette migration.');
    }
    if ($hasJoin && $hasArchive) {
        throw new RuntimeException('Les tables utilisateurs_roles et son archive existent ensemble; inspectez le schéma avant de continuer.');
    }
    if ($hasJoin) {
        $invalid = $pdo->query("SELECT ur.utilisateur_id FROM utilisateurs_roles ur JOIN roles r ON r.id=ur.role_id GROUP BY ur.utilisateur_id HAVING COUNT(*)<>1 OR SUM(LOWER(r.code) NOT IN ('sadmin','client'))>0 LIMIT 1")->fetchColumn();
        if ($invalid !== false) {
            throw new RuntimeException('Au moins un utilisateur possède plusieurs rôles ou un rôle non pris en charge. Corrigez ses associations avant la migration.');
        }
        if ($hasRole) {
            $conflict = $pdo->query("SELECT u.id FROM utilisateurs u JOIN (SELECT ur.utilisateur_id, MIN(r.code) AS code FROM utilisateurs_roles ur JOIN roles r ON r.id=ur.role_id GROUP BY ur.utilisateur_id) legacy ON legacy.utilisateur_id=u.id WHERE u.`role` IS NOT NULL AND BINARY u.`role` <> BINARY (CASE LOWER(legacy.code) WHEN 'sadmin' THEN 'Sadmin' WHEN 'client' THEN 'Client' ELSE legacy.code END) LIMIT 1")->fetchColumn();
            if ($conflict !== false) {
                throw new RuntimeException('La colonne role contredit des associations historiques. Corrigez le conflit avant la migration.');
            }
        }
    }
    if (!$hasRole) {
        if (!$hasJoin) {
            throw new RuntimeException('La table utilisateurs_roles est absente; impossible de vérifier les rôles existants.');
        }
        $pdo->exec("ALTER TABLE utilisateurs ADD COLUMN `role` VARCHAR(40) NULL AFTER email");
    }

    if ($hasJoin) {
        $pdo->exec("UPDATE utilisateurs u JOIN (SELECT ur.utilisateur_id, MIN(r.code) AS code FROM utilisateurs_roles ur JOIN roles r ON r.id=ur.role_id GROUP BY ur.utilisateur_id) role_map ON role_map.utilisateur_id=u.id SET u.`role`=CASE LOWER(role_map.code) WHEN 'sadmin' THEN 'Sadmin' WHEN 'client' THEN 'Client' ELSE u.`role` END WHERE u.`role` IS NULL");
    }
    $pdo->exec("UPDATE utilisateurs SET `role`=CASE LOWER(`role`) WHEN 'sadmin' THEN 'Sadmin' WHEN 'client' THEN 'Client' ELSE `role` END WHERE `role` IS NOT NULL");
    $pdo->exec("UPDATE utilisateurs SET `role`='Client' WHERE `role` IS NULL OR `role`=''");
    $invalidRole = $pdo->query("SELECT id FROM utilisateurs WHERE `role` NOT IN ('Sadmin','Client') LIMIT 1")->fetchColumn();
    if ($invalidRole !== false) {
        throw new RuntimeException('Un rôle utilisateur invalide a été trouvé; aucune association historique n’a été supprimée.');
    }
    $pdo->exec("ALTER TABLE utilisateurs MODIFY `role` VARCHAR(40) NOT NULL DEFAULT 'Client'");

    $index = $pdo->prepare('SELECT 1 FROM information_schema.statistics WHERE table_schema=? AND table_name=? AND index_name=?');
    $index->execute([$schema, 'utilisateurs', 'idx_utilisateurs_role']);
    if (!$index->fetchColumn()) $pdo->exec('CREATE INDEX idx_utilisateurs_role ON utilisateurs(`role`)');

    $fk = $pdo->prepare("SELECT 1 FROM information_schema.table_constraints WHERE constraint_schema=? AND table_name=? AND constraint_name=? AND constraint_type='FOREIGN KEY'");
    $fk->execute([$schema, 'utilisateurs', 'fk_utilisateurs_role']);
    if (!$fk->fetchColumn()) {
        $pdo->exec('ALTER TABLE utilisateurs ADD CONSTRAINT fk_utilisateurs_role FOREIGN KEY(`role`) REFERENCES roles(code) ON UPDATE CASCADE ON DELETE RESTRICT');
    }

    $checkConstraint = $pdo->prepare("SELECT 1 FROM information_schema.table_constraints WHERE constraint_schema=? AND table_name=? AND constraint_name=? AND constraint_type='CHECK'");
    $checkConstraint->execute([$schema, 'utilisateurs', 'chk_utilisateurs_role']);
    if (!$checkConstraint->fetchColumn()) {
        $pdo->exec("ALTER TABLE utilisateurs ADD CONSTRAINT chk_utilisateurs_role CHECK (BINARY `role` IN (BINARY 'Sadmin',BINARY 'Client'))");
    }

    if ($hasJoin && !$hasArchive) {
        $pdo->exec('RENAME TABLE utilisateurs_roles TO utilisateurs_roles_pre_role_column');
    } elseif ($hasJoin && $hasArchive) {
        throw new RuntimeException('Les tables utilisateurs_roles et son archive existent ensemble; migration interrompue pour éviter toute perte.');
    }

    if ($demoColumnExists) {
        $pdo->exec('CREATE TABLE IF NOT EXISTS utilisateurs_is_demo_pre_role_column (utilisateur_id BIGINT UNSIGNED PRIMARY KEY, is_demo BOOLEAN NOT NULL) ENGINE=InnoDB');
        $pdo->exec('INSERT INTO utilisateurs_is_demo_pre_role_column(utilisateur_id,is_demo) SELECT u.id,u.is_demo FROM utilisateurs u LEFT JOIN utilisateurs_is_demo_pre_role_column backup ON backup.utilisateur_id=u.id WHERE backup.utilisateur_id IS NULL');
        $pdo->exec('ALTER TABLE utilisateurs DROP COLUMN is_demo');
    }
};
