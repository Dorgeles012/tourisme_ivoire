<?php
declare(strict_types=1);

/** Médiathèque catégorisée; les lignes images_destinations existantes sont reprises sans déplacer leurs fichiers. */
return static function (PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS medias (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        nom_original VARCHAR(255) NOT NULL,
        nom_fichier VARCHAR(120) NOT NULL,
        chemin_fichier VARCHAR(500) NOT NULL,
        extension VARCHAR(10) NOT NULL,
        type_mime VARCHAR(100) NOT NULL,
        taille BIGINT UNSIGNED NULL,
        largeur INT UNSIGNED NULL,
        hauteur INT UNSIGNED NULL,
        categorie VARCHAR(40) NOT NULL,
        description TEXT NULL,
        texte_alternatif VARCHAR(255) NULL,
        credit VARCHAR(190) NULL,
        licence VARCHAR(190) NULL,
        destination_id BIGINT UNSIGNED NULL,
        utilisateur_id BIGINT UNSIGNED NULL,
        est_principale BOOLEAN NOT NULL DEFAULT FALSE,
        statut ENUM('actif','inactif') NOT NULL DEFAULT 'actif',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_medias_chemin(chemin_fichier),
        KEY idx_medias_categorie_date(categorie,created_at),
        KEY idx_medias_recherche(nom_original),
        KEY idx_medias_destination(destination_id),
        KEY idx_medias_utilisateur(utilisateur_id),
        CONSTRAINT chk_medias_categorie CHECK(categorie IN ('logo','bannieres','destinations','sites_touristiques','hotels','restaurants','patrimoine','evenements','transports','profils','articles','videos','galerie','interface','autres')),
        CONSTRAINT fk_medias_destination FOREIGN KEY(destination_id) REFERENCES destinations(id) ON DELETE SET NULL,
        CONSTRAINT fk_medias_utilisateur FOREIGN KEY(utilisateur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $hasLegacy = $pdo->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='images_destinations' LIMIT 1");
    $hasLegacy->execute();
    if (!$hasLegacy->fetchColumn()) return;

    $legacy = $pdo->query('SELECT id,destination_id,url,texte_alternatif,credit,licence,est_principale,statut,created_at FROM images_destinations ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    $find = $pdo->prepare('SELECT id FROM medias WHERE chemin_fichier=? LIMIT 1');
    $insert = $pdo->prepare("INSERT INTO medias(nom_original,nom_fichier,chemin_fichier,extension,type_mime,categorie,texte_alternatif,credit,licence,destination_id,est_principale,statut,created_at)
        VALUES(?,?,?,?,?,'destinations',?,?,?,?,?,?,?)");
    $mimeByExtension = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp','avif'=>'image/avif'];
    foreach ($legacy as $row) {
        $urlPath = (string)(parse_url((string)$row['url'], PHP_URL_PATH) ?: $row['url']);
        $uploadsAt = strpos($urlPath, '/uploads/');
        $relativePath = $uploadsAt === false
            ? 'uploads/images/' . basename($urlPath)
            : ltrim(substr($urlPath, $uploadsAt + 1), '/');
        if (!preg_match('~^uploads/[a-z0-9_/-]+/[a-zA-Z0-9._-]+$~D', $relativePath)) continue;
        $filename = basename($relativePath);
        $extension = strtolower((string)pathinfo($filename, PATHINFO_EXTENSION));
        if (!isset($mimeByExtension[$extension])) continue;
        $find->execute([$relativePath]);
        if ($find->fetchColumn()) continue;
        $insert->execute([
            substr($filename, 0, 255), $filename, $relativePath, $extension,
            $mimeByExtension[$extension], $row['texte_alternatif'], $row['credit'], $row['licence'],
            (int)$row['destination_id'], (int)$row['est_principale'], $row['statut'], $row['created_at'],
        ]);
    }
};
