<?php
declare(strict_types=1);

return static function (PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS actualites (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        titre VARCHAR(190) NOT NULL,
        slug VARCHAR(220) NOT NULL UNIQUE,
        resume VARCHAR(500) NULL,
        contenu LONGTEXT NOT NULL,
        categorie VARCHAR(100) NULL,
        image_url VARCHAR(500) NULL,
        statut ENUM('brouillon','publie','archive') NOT NULL DEFAULT 'brouillon',
        is_demo TINYINT(1) NOT NULL DEFAULT 0,
        published_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        deleted_at DATETIME NULL,
        INDEX idx_actualites_public (statut, deleted_at, published_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS hebergements (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(190) NOT NULL,
        slug VARCHAR(220) NOT NULL UNIQUE,
        type VARCHAR(80) NOT NULL DEFAULT 'hotel',
        description LONGTEXT NOT NULL,
        localite VARCHAR(190) NULL,
        adresse VARCHAR(300) NULL,
        telephone VARCHAR(50) NULL,
        site_web VARCHAR(500) NULL,
        prix_min DECIMAL(12,2) NULL,
        devise CHAR(3) NOT NULL DEFAULT 'XOF',
        image_url VARCHAR(500) NULL,
        statut ENUM('brouillon','publie','archive') NOT NULL DEFAULT 'brouillon',
        is_demo TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        deleted_at DATETIME NULL,
        INDEX idx_hebergements_public (statut, deleted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS activites_touristiques (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(190) NOT NULL,
        slug VARCHAR(220) NOT NULL UNIQUE,
        type VARCHAR(80) NOT NULL DEFAULT 'activite',
        description LONGTEXT NOT NULL,
        localite VARCHAR(190) NULL,
        adresse VARCHAR(300) NULL,
        duree VARCHAR(100) NULL,
        prix DECIMAL(12,2) NULL,
        devise CHAR(3) NOT NULL DEFAULT 'XOF',
        image_url VARCHAR(500) NULL,
        statut ENUM('brouillon','publie','archive') NOT NULL DEFAULT 'brouillon',
        is_demo TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        deleted_at DATETIME NULL,
        INDEX idx_activites_public (statut, deleted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS promotions (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        titre VARCHAR(190) NOT NULL,
        slug VARCHAR(220) NOT NULL UNIQUE,
        description LONGTEXT NOT NULL,
        reduction VARCHAR(100) NULL,
        date_debut DATETIME NOT NULL,
        date_fin DATETIME NOT NULL,
        image_url VARCHAR(500) NULL,
        statut ENUM('brouillon','publie','archive') NOT NULL DEFAULT 'brouillon',
        is_demo TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        deleted_at DATETIME NULL,
        INDEX idx_promotions_public (statut, deleted_at, date_debut, date_fin)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
};
