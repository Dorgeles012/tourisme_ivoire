<?php
declare(strict_types=1);
final class JwtService {
    private static function encode(string $value): string { return rtrim(strtr(base64_encode($value), '+/', '-_'), '='); }
    public static function issue(array $claims, int $ttl = 3600): string {
        $secret = env_value('JWT_SECRET');
        if (!$secret || strlen($secret) < 32) throw new RuntimeException('JWT_SECRET must contain at least 32 characters.');
        $now = time();
        $hex = bin2hex(random_bytes(16));
        $jti = substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20);
        $header = self::encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256'], JSON_THROW_ON_ERROR));
        $payload = self::encode(json_encode($claims + ['iat' => $now, 'exp' => $now + $ttl, 'jti' => $jti], JSON_THROW_ON_ERROR));
        $signature = self::encode(hash_hmac('sha256', "$header.$payload", $secret, true));
        return "$header.$payload.$signature";
    }
    public static function verify(string $token): ?array {
        $secret = env_value('JWT_SECRET');
        if (!$secret || strlen($secret) < 32) return null;
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;
        [$head, $body, $signature] = $parts;
        $expected = self::encode(hash_hmac('sha256', "$head.$body", $secret, true));
        if (!hash_equals($expected, $signature)) return null;
        $decode = static function (string $value): ?array {
            $decoded = base64_decode(strtr($value, '-_', '+/'), true);
            if ($decoded === false) return null;
            try { $data = json_decode($decoded, true, 512, JSON_THROW_ON_ERROR); return is_array($data) ? $data : null; }
            catch (JsonException) { return null; }
        };
        $header = $decode($head); $claims = $decode($body);
        if (($header['alg'] ?? null) !== 'HS256' || !$claims || (int)($claims['exp'] ?? 0) < time()) return null;
        return $claims;
    }
}
