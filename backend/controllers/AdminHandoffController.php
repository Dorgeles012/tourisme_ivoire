<?php
declare(strict_types=1);
final class AdminHandoffController {
    public static function create(): never {
        $userId = AuthMiddleware::requireRole(['Sadmin']);
        $code = bin2hex(random_bytes(32));
        $stmt = db()->prepare('INSERT INTO jetons_handoff_admin(utilisateur_id,code_hash,expire_at) VALUES(?,?,DATE_ADD(NOW(),INTERVAL 60 SECOND))');
        $stmt->execute([$userId,hash('sha256',$code)]);
        json_response(['data' => ['code' => $code, 'expires_in' => 60]]);
    }

    public static function consume(): never {
        $data = request_json();
        $code = (string)($data['code'] ?? '');
        if (!preg_match('/^[a-f0-9]{64}$/D', $code)) {
            api_error('invalid_handoff', 'Code de connexion administrateur invalide ou expiré.', 401);
        }
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT h.id,u.id AS user_id,u.nom,u.email FROM jetons_handoff_admin h JOIN utilisateurs u ON u.id=h.utilisateur_id WHERE h.code_hash=? AND h.utilise_at IS NULL AND h.expire_at>NOW() AND u.`role`='Sadmin' AND u.statut='actif' AND u.deleted_at IS NULL LIMIT 1 FOR UPDATE");
            $stmt->execute([hash('sha256',$code)]);
            $user = $stmt->fetch();
            if (!$user) {
                $pdo->rollBack();
                api_error('invalid_handoff', 'Code de connexion administrateur invalide ou expiré.', 401);
            }
            $pdo->prepare('UPDATE jetons_handoff_admin SET utilise_at=NOW() WHERE id=? AND utilise_at IS NULL')->execute([$user['id']]);
            $pdo->commit();

            define('ADMIN_SESSION_DEFERRED', true);
            require_once APP_ROOT . '/admin/bootstrap.php';
            $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
            $apiOffset = strpos($requestPath, '/api/');
            if ($apiOffset === false) throw new RuntimeException('Impossible de déterminer le chemin de session administrateur.');
            $sessionPath = rtrim(substr($requestPath, 0, $apiOffset), '/') . '/admin';
            admin_start_session($sessionPath);
            session_regenerate_id(true);
            $_SESSION['admin_user'] = ['id' => (int)$user['user_id'], 'nom' => $user['nom'], 'email' => $user['email']];
            $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
            json_response(['data' => ['redirect' => $sessionPath . '/index.php']]);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}
