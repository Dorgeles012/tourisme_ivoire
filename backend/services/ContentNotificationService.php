<?php
declare(strict_types=1);

final class ContentNotificationService {
    /** Store one in-app notification per active client using the existing MySQL table. */
    public static function broadcast(string $title, string $body, string $type, string $slug): int {
        $allowed = ['actualites','promotions','hebergements','activites'];
        if (!in_array($type, $allowed, true) || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            throw new InvalidArgumentException('Cible de notification invalide.');
        }
        $statement = db()->prepare(
            "INSERT INTO notifications (utilisateur_id,titre,contenu,type,cible_url,is_demo,created_at)
             SELECT id,?,?, 'nouveau_contenu', ?,0,NOW()
             FROM utilisateurs
             WHERE `role`='Client' AND statut='actif' AND deleted_at IS NULL"
        );
        $statement->execute([
            mb_substr($title, 0, 180),
            mb_substr(trim(strip_tags($body)), 0, 1500),
            '?page='.$type.'&slug='.rawurlencode($slug),
        ]);
        return $statement->rowCount();
    }
}
