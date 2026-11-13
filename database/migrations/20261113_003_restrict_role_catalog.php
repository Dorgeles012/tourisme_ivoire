<?php
declare(strict_types=1);

/** Restrict the role catalog to the two application roles. */
return static function (PDO $pdo): void {
    $schema = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
    $unexpected = $pdo->query("SELECT code FROM roles WHERE BINARY code NOT IN (BINARY 'Sadmin',BINARY 'Client') LIMIT 1")->fetchColumn();
    if ($unexpected !== false) {
        throw new RuntimeException('Un code de rôle non pris en charge existe dans roles; corrigez-le avant la migration.');
    }
    $check = $pdo->prepare("SELECT 1 FROM information_schema.table_constraints WHERE constraint_schema=? AND table_name='roles' AND constraint_name='chk_roles_code' AND constraint_type='CHECK'");
    $check->execute([$schema]);
    if (!$check->fetchColumn()) {
        $pdo->exec("ALTER TABLE roles ADD CONSTRAINT chk_roles_code CHECK (BINARY code IN (BINARY 'Sadmin',BINARY 'Client'))");
    }
};
