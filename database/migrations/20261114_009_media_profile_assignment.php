<?php
declare(strict_types=1);

/** Permet d’associer explicitement un média de catégorie profils au compte qu’il illustre. */
return static function (PDO $pdo): void {
    $column = $pdo->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='medias' AND column_name='profil_utilisateur_id' LIMIT 1");
    $column->execute();
    if (!$column->fetchColumn()) {
        $pdo->exec("ALTER TABLE medias
            ADD COLUMN profil_utilisateur_id BIGINT UNSIGNED NULL AFTER destination_id,
            ADD KEY idx_medias_profil_utilisateur(profil_utilisateur_id),
            ADD CONSTRAINT fk_medias_profil_utilisateur FOREIGN KEY(profil_utilisateur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL");
    }
};
