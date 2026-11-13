<?php
declare(strict_types=1);

/** Indexes the per-account hourly password-reset limit query. */
return static function (PDO $pdo): void {
    $schema = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
    $index = $pdo->prepare('SELECT 1 FROM information_schema.statistics WHERE table_schema=? AND table_name=? AND index_name=?');
    $index->execute([$schema, 'historique_mot_de_passe', 'idx_password_reset_user_created']);
    if (!$index->fetchColumn()) {
        $pdo->exec('CREATE INDEX idx_password_reset_user_created ON historique_mot_de_passe(utilisateur_id,created_at)');
    }
};
