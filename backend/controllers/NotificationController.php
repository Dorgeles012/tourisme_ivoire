<?php
declare(strict_types=1);
final class NotificationController {
    public static function index(): never {
        $userId = AuthMiddleware::userId();
        $page = max(1,(int)($_GET['page'] ?? 1));
        $perPage = min(50,max(1,(int)($_GET['per_page'] ?? 20)));
        $stmt = db()->prepare('SELECT id,titre,contenu,type,cible_url,lu_at,created_at FROM notifications WHERE utilisateur_id=? ORDER BY created_at DESC LIMIT ? OFFSET ?');
        $stmt->bindValue(1,$userId,PDO::PARAM_INT);
        $stmt->bindValue(2,$perPage,PDO::PARAM_INT);
        $stmt->bindValue(3,($page-1)*$perPage,PDO::PARAM_INT);
        $stmt->execute();
        json_response(['data' => ['items' => $stmt->fetchAll(),'page' => $page,'per_page' => $perPage]]);
    }

    public static function markRead(int $id): never {
        $stmt = db()->prepare('UPDATE notifications SET lu_at=COALESCE(lu_at,NOW()) WHERE id=? AND utilisateur_id=?');
        $stmt->execute([$id,AuthMiddleware::userId()]);
        if ($stmt->rowCount() === 0) {
            $check = db()->prepare('SELECT id FROM notifications WHERE id=? AND utilisateur_id=?');
            $check->execute([$id,AuthMiddleware::userId()]);
            if (!$check->fetchColumn()) api_error('not_found','Notification introuvable.',404);
        }
        json_response(['data' => ['id' => $id,'lu' => true]]);
    }
}
