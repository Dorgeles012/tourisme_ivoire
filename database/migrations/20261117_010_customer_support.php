<?php
declare(strict_types=1);
return static function (PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS demandes_assistance (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, utilisateur_id BIGINT UNSIGNED NOT NULL, subject VARCHAR(160) NOT NULL, status ENUM('ouvert','en_cours','resolu') NOT NULL DEFAULT 'ouvert', created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, KEY idx_support_user_updated(utilisateur_id,updated_at), FOREIGN KEY(utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE IF NOT EXISTS messages_assistance (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, demande_id BIGINT UNSIGNED NOT NULL, sender_id BIGINT UNSIGNED NOT NULL, sender_role ENUM('Client','Sadmin') NOT NULL, body TEXT NOT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, KEY idx_support_messages_thread(demande_id,created_at), FOREIGN KEY(demande_id) REFERENCES demandes_assistance(id) ON DELETE CASCADE, FOREIGN KEY(sender_id) REFERENCES utilisateurs(id) ON DELETE RESTRICT) ENGINE=InnoDB");
};
