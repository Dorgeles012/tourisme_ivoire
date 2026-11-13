<?php
declare(strict_types=1);

/** Stocke les codes éphémères à usage unique pour ouvrir le back-office depuis Flutter. */
return static function (PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS jetons_handoff_admin (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        utilisateur_id BIGINT UNSIGNED NOT NULL,
        code_hash CHAR(64) NOT NULL UNIQUE,
        expire_at DATETIME NOT NULL,
        utilise_at DATETIME NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_handoff_admin_expiration(expire_at,utilise_at),
        FOREIGN KEY(utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
};
