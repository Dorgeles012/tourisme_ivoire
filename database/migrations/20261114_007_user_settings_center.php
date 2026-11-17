<?php
declare(strict_types=1);

/** Stockage des demandes de compte et des langues proposées dans les préférences. */
return static function (PDO $pdo): void {
    $tableExists = static function (string $name) use ($pdo): bool {
        $stmt = $pdo->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=? LIMIT 1');
        $stmt->execute([$name]);
        return (bool)$stmt->fetchColumn();
    };

    foreach (['utilisateurs', 'langues', 'parametres_utilisateurs'] as $table) {
        if (!$tableExists($table)) {
            throw new RuntimeException('Table nécessaire absente : ' . $table . '. Appliquez d’abord le schéma Tourisme Ivoire.');
        }
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS demandes_compte (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        utilisateur_id BIGINT UNSIGNED NOT NULL,
        type ENUM('desactivation','suppression') NOT NULL,
        statut ENUM('en_attente','approuvee','refusee','annulee') NOT NULL DEFAULT 'en_attente',
        demandee_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        traitee_at DATETIME NULL,
        traitee_par BIGINT UNSIGNED NULL,
        KEY idx_demande_compte_utilisateur_statut(utilisateur_id,statut,demandee_at),
        KEY idx_demande_compte_traitement(statut,demandee_at),
        CONSTRAINT fk_demande_compte_utilisateur FOREIGN KEY(utilisateur_id) REFERENCES utilisateurs(id) ON DELETE RESTRICT,
        CONSTRAINT fk_demande_compte_traitee_par FOREIGN KEY(traitee_par) REFERENCES utilisateurs(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Ces langues sont des références d’interface, pas des comptes ou des données de démonstration.
    $pdo->exec("INSERT INTO langues(code,nom,actif) VALUES ('fr','Français',TRUE),('en','English',TRUE) ON DUPLICATE KEY UPDATE code=VALUES(code)");
};
