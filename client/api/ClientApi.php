<?php
declare(strict_types=1);

final class ClientApiException extends RuntimeException {
    public function __construct(public readonly int $status, string $message) { parent::__construct($message); }
}

/** Server-side API client. Bearer tokens stay in the HttpOnly PHP session. */
final class ClientApi {
    public function __construct(private readonly ?string $token = null) {}

    private function baseUrl(): string {
        $configured = env_value('CLIENT_API_BASE_URL');
        if (is_string($configured) && trim($configured) !== '') {
            $base = rtrim(trim($configured), '/') . '/';
        } else {
            $scheme = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
            $host = (string)($_SERVER['SERVER_NAME'] ?? 'localhost');
            if (!preg_match('/^[a-z0-9.\-:\[\]]+$/i', $host)) throw new RuntimeException('Hôte API invalide.');
            $port = (int)($_SERVER['SERVER_PORT'] ?? ($scheme === 'https' ? 443 : 80));
            $portSuffix = ($scheme === 'http' && $port !== 80) || ($scheme === 'https' && $port !== 443) ? ':' . $port : '';
            $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/client/index.php'));
            $prefix = preg_replace('~/client/(?:index\.php)?$~', '', $script) ?? '';
            $base = $scheme . '://' . $host . $portSuffix . rtrim($prefix, '/') . '/';
        }
        if (!preg_match('~^https?://[^\s]+/$~i', $base)) throw new RuntimeException('Configurez CLIENT_API_BASE_URL avec une URL HTTP(S) absolue.');
        return $base;
    }

    public function request(string $method, string $path, ?array $body = null): array {
        if (!preg_match('~^[a-z0-9/_?=&.%+-]+$~i', $path)) throw new InvalidArgumentException('Chemin API invalide.');
        $headers = ['Accept: application/json'];
        if ($this->token !== null && $this->token !== '') $headers[] = 'Authorization: Bearer ' . $this->token;
        $content = '';
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
            $content = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }
        $context = stream_context_create(['http' => [
            'method' => strtoupper($method),
            'header' => implode("\r\n", $headers),
            'content' => $content,
            'timeout' => 15,
            'ignore_errors' => true,
            'follow_location' => 0,
            'max_redirects' => 0,
        ]]);
        $response = @file_get_contents($this->baseUrl() . ltrim($path, '/'), false, $context);
        $statusLine = $http_response_header[0] ?? '';
        preg_match('/\s([1-5][0-9]{2})\s/', $statusLine, $match);
        $status = isset($match[1]) ? (int)$match[1] : 0;
        if ($response === false) throw new ClientApiException(503, 'Le service est momentanément inaccessible. Réessayez plus tard.');
        try { $payload = json_decode($response, true, 512, JSON_THROW_ON_ERROR); }
        catch (JsonException) { throw new ClientApiException(502, 'La réponse du service est invalide.'); }
        if (!is_array($payload)) throw new ClientApiException(502, 'La réponse du service est invalide.');
        if ($status < 200 || $status >= 300) {
            $message = $payload['error']['message'] ?? 'La demande n’a pas abouti.';
            throw new ClientApiException($status ?: 502, is_string($message) ? $message : 'La demande n’a pas abouti.');
        }
        return $payload;
    }

    public static function data(array $payload): mixed { return $payload['data'] ?? null; }
}
