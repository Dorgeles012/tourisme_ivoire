<?php
declare(strict_types=1);
final class AuthMiddleware {
    public static function userId(): int {
        $claims = self::claims();
        $id = (int)$claims['sub'];
        $stmt = db()->prepare('SELECT id FROM jetons_auth WHERE utilisateur_id=? AND jti=? AND revoque_at IS NULL AND expire_at>NOW() LIMIT 1');
        $stmt->execute([$id, $claims['jti']]);
        if (!$stmt->fetchColumn()) api_error('invalid_token', 'Jeton révoqué ou expiré.', 401);
        return $id;
    }

    public static function claims(): array {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) api_error('unauthorized', 'Authentification requise.', 401);
        $claims = JwtService::verify(trim($matches[1]));
        if (!$claims || empty($claims['sub']) || empty($claims['jti'])) api_error('invalid_token', 'Jeton invalide ou expiré.', 401);
        return $claims;
    }

    public static function requireRole(array $roles): int {
        $userId = self::userId();
        $stmt = db()->prepare('SELECT 1 FROM utilisateurs_roles ur JOIN roles r ON r.id=ur.role_id WHERE ur.utilisateur_id=? AND r.code IN (' . implode(',', array_fill(0, count($roles), '?')) . ') LIMIT 1');
        $stmt->execute(array_merge([$userId], $roles));
        if (!$stmt->fetchColumn()) api_error('forbidden', 'Permissions insuffisantes.', 403);
        return $userId;
    }
}
