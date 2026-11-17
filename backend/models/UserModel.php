<?php
declare(strict_types=1);

final class UserModel {
    public static function findForLogin(string $email): ?array {
        $stmt = db()->prepare('SELECT id,nom,prenom,email,mot_de_passe_hash,statut FROM utilisateurs WHERE email=? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findActiveProfile(int $id): ?array {
        $stmt = db()->prepare('SELECT id,nom,prenom,email,telephone,avatar_url,created_at FROM utilisateurs WHERE id=? AND statut=\'actif\' AND deleted_at IS NULL');
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function hasActiveApiToken(int $userId, string $tokenId): bool {
        $stmt = db()->prepare("SELECT t.id FROM jetons_auth t JOIN utilisateurs u ON u.id=t.utilisateur_id WHERE t.utilisateur_id=? AND t.jti=? AND t.revoque_at IS NULL AND t.expire_at>NOW() AND u.statut='actif' AND u.deleted_at IS NULL LIMIT 1");
        $stmt->execute([$userId, $tokenId]);
        return (bool)$stmt->fetchColumn();
    }
}
