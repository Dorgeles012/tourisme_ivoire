<?php
declare(strict_types=1);

final class SupportController {
    public static function index(): never {
        $userId = AuthMiddleware::requireRole(['Client']);
        $stmt = db()->prepare('SELECT id,subject,status,created_at,updated_at FROM demandes_assistance WHERE utilisateur_id=? ORDER BY updated_at DESC LIMIT 100');
        $stmt->execute([$userId]); json_response(['data' => $stmt->fetchAll()]);
    }
    public static function show(int $id): never {
        $userId = AuthMiddleware::requireRole(['Client']);
        $stmt = db()->prepare('SELECT id,subject,status,created_at,updated_at FROM demandes_assistance WHERE id=? AND utilisateur_id=?');
        $stmt->execute([$id, $userId]); $thread = $stmt->fetch();
        if (!$thread) api_error('not_found', 'Conversation introuvable.', 404);
        $messages = db()->prepare('SELECT m.id,m.sender_role,m.body,m.created_at FROM messages_assistance m JOIN demandes_assistance t ON t.id=m.demande_id WHERE m.demande_id=? AND t.utilisateur_id=? ORDER BY m.created_at,m.id');
        $messages->execute([$id, $userId]); $thread['messages'] = $messages->fetchAll();
        json_response(['data' => $thread]);
    }
    public static function create(): never {
        $userId = AuthMiddleware::requireRole(['Client']); $data = request_json();
        $subject = trim((string)($data['subject'] ?? '')); $body = trim((string)($data['message'] ?? ''));
        if ($subject === '' || mb_strlen($subject) > 160 || $body === '' || mb_strlen($body) > 5000) api_error('validation_error', 'Un sujet (160 caractères maximum) et un message (5000 caractères maximum) sont requis.', 422);
        $pdo = db(); $pdo->beginTransaction();
        try {
            $limit = $pdo->prepare('SELECT COUNT(*) FROM demandes_assistance WHERE utilisateur_id=? AND created_at>=DATE_SUB(NOW(),INTERVAL 1 DAY)');
            $limit->execute([$userId]);
            if ((int)$limit->fetchColumn() >= 5) { $pdo->rollBack(); api_error('too_many_requests', 'Vous avez atteint la limite quotidienne de demandes d’assistance.', 429); }
            $pdo->prepare("INSERT INTO demandes_assistance(utilisateur_id,subject,status) VALUES(?,?,'ouvert')")->execute([$userId, $subject]);
            $threadId = (int)$pdo->lastInsertId();
            $pdo->prepare("INSERT INTO messages_assistance(demande_id,sender_id,sender_role,body) VALUES(?,?,'Client',?)")->execute([$threadId, $userId, $body]);
            $admins = $pdo->query("SELECT id FROM utilisateurs WHERE `role`='Sadmin' AND statut='actif' AND deleted_at IS NULL")->fetchAll(PDO::FETCH_COLUMN);
            $notify = $pdo->prepare('INSERT INTO notifications(utilisateur_id,titre,contenu,type,cible_url) VALUES(?,?,?,?,?)');
            foreach ($admins as $adminId) $notify->execute([(int)$adminId, 'Nouvelle demande d’assistance', $subject, 'assistance', 'support.php?id=' . $threadId]);
            $pdo->commit(); json_response(['data' => ['id' => $threadId, 'status' => 'ouvert']], 201);
        } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
    }
    public static function reply(int $id): never {
        $userId = AuthMiddleware::requireRole(['Client']); $body = trim((string)(request_json()['message'] ?? ''));
        if ($body === '' || mb_strlen($body) > 5000) api_error('validation_error', 'Un message de 5000 caractères maximum est requis.', 422);
        $pdo = db(); $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT id FROM demandes_assistance WHERE id=? AND utilisateur_id=? AND status<>'resolu' FOR UPDATE");
            $stmt->execute([$id, $userId]);
            if (!$stmt->fetchColumn()) { $pdo->rollBack(); api_error('not_found', 'Conversation introuvable ou clôturée.', 404); }
            $limit = $pdo->prepare('SELECT COUNT(*) FROM messages_assistance WHERE demande_id=? AND sender_role=\'Client\' AND created_at>=DATE_SUB(NOW(),INTERVAL 1 HOUR)');
            $limit->execute([$id]);
            if ((int)$limit->fetchColumn() >= 20) { $pdo->rollBack(); api_error('too_many_messages', 'Vous avez atteint la limite horaire de réponses pour cette conversation.', 429); }
            $pdo->prepare("INSERT INTO messages_assistance(demande_id,sender_id,sender_role,body) VALUES(?,?,'Client',?)")->execute([$id, $userId, $body]);
            $pdo->prepare("UPDATE demandes_assistance SET status='ouvert' WHERE id=?")->execute([$id]);
            $owner = $pdo->prepare('SELECT subject FROM demandes_assistance WHERE id=?');
            $owner->execute([$id]);
            $subject = (string)$owner->fetchColumn();
            $admins = $pdo->query("SELECT id FROM utilisateurs WHERE `role`='Sadmin' AND statut='actif' AND deleted_at IS NULL")->fetchAll(PDO::FETCH_COLUMN);
            $notify = $pdo->prepare('INSERT INTO notifications(utilisateur_id,titre,contenu,type,cible_url) VALUES(?,?,?,?,?)');
            foreach ($admins as $adminId) $notify->execute([(int)$adminId, 'Nouveau message client', $subject, 'assistance', 'support.php?id=' . $id]);
            $pdo->commit(); json_response(['data' => ['id' => $id, 'sent' => true]]);
        } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
    }
}
