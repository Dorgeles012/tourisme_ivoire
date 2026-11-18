<?php
declare(strict_types=1);

final class ClientApiException extends RuntimeException {
    public function __construct(
        public readonly int $status,
        string $message,
        public readonly ?string $apiCode = null
    ) {
        parent::__construct($message);
    }
}

/** Server-side API client. Bearer tokens stay in the HttpOnly PHP session. */
final class ClientApi {
    public function __construct(private readonly ?string $token = null) {}

    private function baseUrl(): string {
        $configured = trim((string)env_value('CLIENT_API_BASE_URL', ''));
        if ($configured === '') {
            $scheme = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
            $host = (string)($_SERVER['SERVER_NAME'] ?? 'localhost');
            if (!preg_match('/^[a-z0-9.\\-:\\[\\]]+$/i', $host)) {
                throw new ClientApiException(500, 'L’adresse de l’API n’est pas configurée correctement.');
            }
            $port = (int)($_SERVER['SERVER_PORT'] ?? ($scheme === 'https' ? 443 : 80));
            $suffix = (($scheme === 'http' && $port !== 80) || ($scheme === 'https' && $port !== 443)) ? ':' . $port : '';
            $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/client/index.php'));
            $prefix = preg_replace('~/client/(?:index\\.php)?$~', '', $script) ?? '';
            $configured = $scheme . '://' . $host . $suffix . rtrim($prefix, '/') . '/';
        }

        $parts = parse_url($configured);
        if (!is_array($parts)
            || !in_array(strtolower((string)($parts['scheme'] ?? '')), ['http', 'https'], true)
            || empty($parts['host'])
            || isset($parts['user']) || isset($parts['pass'])
            || isset($parts['query']) || isset($parts['fragment'])
            || preg_match('/\\s/', $configured)
        ) {
            throw new ClientApiException(500, 'L’adresse de l’API est invalide. Vérifiez CLIENT_API_BASE_URL.');
        }

        if (env_value('APP_ENV', 'production') === 'production' && strtolower((string)$parts['scheme']) !== 'https') {
            throw new ClientApiException(500, 'L’API doit utiliser HTTPS en production.');
        }
        return rtrim($configured, '/') . '/';
    }

    public function request(string $method, string $path, ?array $body = null): array {
        $method = strtoupper($method);
        if (!in_array($method, ['GET', 'POST', 'PUT', 'DELETE'], true)) {
            throw new InvalidArgumentException('Méthode API non autorisée.');
        }
        if (!preg_match('~^[a-z0-9/_?=&.%+-]+$~i', $path) || str_contains($path, '..')) {
            throw new InvalidArgumentException('Chemin API invalide.');
        }

        $base = $this->baseUrl();
        $url = $base . ltrim($path, '/');
        $headers = ['Accept: application/json'];
        if ($this->token !== null && $this->token !== '') {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }
        $content = null;
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json; charset=utf-8';
            try {
                $content = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new ClientApiException(422, 'Les données envoyées sont invalides.', 'invalid_request');
            }
        }

        if (function_exists('curl_init')) {
            [$response, $status] = $this->requestWithCurl($url, $base, $method, $headers, $content);
        } else {
            [$response, $status] = $this->requestWithStreams($url, $base, $method, $headers, $content);
        }

        try {
            $payload = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            error_log(sprintf('Client API returned invalid JSON: host=%s status=%d bytes=%d', (string)parse_url($base, PHP_URL_HOST), $status, strlen($response)));
            throw new ClientApiException(502, 'L’API a renvoyé une réponse illisible.', 'invalid_api_response');
        }
        if (!is_array($payload)) {
            error_log(sprintf('Client API returned a non-object JSON response: host=%s status=%d', (string)parse_url($base, PHP_URL_HOST), $status));
            throw new ClientApiException(502, 'L’API a renvoyé une réponse invalide.', 'invalid_api_response');
        }

        if ($status < 200 || $status >= 300) {
            $apiCode = $payload['error']['code'] ?? null;
            if ($status >= 500) {
                error_log(sprintf('Client API server error: host=%s status=%d code=%s', (string)parse_url($base, PHP_URL_HOST), $status, is_string($apiCode) ? $apiCode : 'unknown'));
                throw new ClientApiException($status, 'Une erreur est survenue sur le serveur. Réessayez plus tard.', is_string($apiCode) ? $apiCode : null);
            }
            $message = $payload['error']['message'] ?? 'La demande n’a pas abouti.';
            throw new ClientApiException(
                $status ?: 502,
                is_string($message) ? $message : 'La demande n’a pas abouti.',
                is_string($apiCode) ? $apiCode : null
            );
        }
        return $payload;
    }

    /** Upload one validated PHP upload without exposing the API token to the browser. */
    public function upload(string $path, array $fields, string $fileField, array $file): array {
        if (!function_exists('curl_init') || !class_exists(CURLFile::class)) {
            throw new ClientApiException(501, 'Le téléversement nécessite l’extension PHP cURL.', 'upload_unavailable');
        }
        if (!preg_match('~^[a-z0-9/_?=&.%+-]+$~i', $path) || str_contains($path, '..')) {
            throw new InvalidArgumentException('Chemin API invalide.');
        }
        $tmp = (string)($file['tmp_name'] ?? '');
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($tmp)) {
            throw new ClientApiException(422, 'Le fichier envoyé est invalide.', 'upload_error');
        }
        $base = $this->baseUrl();
        $parts = $fields;
        $parts[$fileField] = new CURLFile($tmp, (string)($file['type'] ?? 'application/octet-stream'), basename((string)($file['name'] ?? 'upload')));
        $headers = ['Accept: application/json'];
        if ($this->token !== null && $this->token !== '') $headers[] = 'Authorization: Bearer ' . $this->token;
        [$response, $status] = $this->requestWithCurl($base . ltrim($path, '/'), $base, 'POST', $headers, $parts);
        try { $payload = json_decode($response, true, 512, JSON_THROW_ON_ERROR); }
        catch (JsonException) { throw new ClientApiException(502, 'L’API a renvoyé une réponse illisible.', 'invalid_api_response'); }
        if (!is_array($payload)) throw new ClientApiException(502, 'L’API a renvoyé une réponse invalide.', 'invalid_api_response');
        if ($status < 200 || $status >= 300) {
            $apiCode = $payload['error']['code'] ?? null;
            $message = $status >= 500 ? 'Une erreur est survenue sur le serveur. Réessayez plus tard.' : ($payload['error']['message'] ?? 'Le téléversement a échoué.');
            throw new ClientApiException($status ?: 502, is_string($message) ? $message : 'Le téléversement a échoué.', is_string($apiCode) ? $apiCode : null);
        }
        return $payload;
    }

    /** @return array{0:string,1:int} */
    private function requestWithCurl(string $url, string $base, string $method, array $headers, mixed $content): array {
        $handle = curl_init($url);
        if ($handle === false) {
            throw new ClientApiException(503, 'Impossible d’initialiser la connexion à l’API.', 'api_unavailable');
        }
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_MAXREDIRS => 0,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];
        $host = strtolower((string)parse_url($base, PHP_URL_HOST));
        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            $options[CURLOPT_NOPROXY] = '*';
        }
        if (defined('CURLOPT_PROTOCOLS_STR')) {
            $options[CURLOPT_PROTOCOLS_STR] = 'http,https';
        } elseif (defined('CURLOPT_PROTOCOLS')) {
            $options[CURLOPT_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
        }
        if ($content !== null) {
            $options[CURLOPT_POSTFIELDS] = $content;
        }
        curl_setopt_array($handle, $options);
        $response = curl_exec($handle);
        $status = (int)curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        if ($response === false) {
            $errno = curl_errno($handle);
            $category = $this->curlErrorCategory($errno);
            curl_close($handle);
            error_log(sprintf('Client API transport failure: host=%s category=%s errno=%d', (string)parse_url($base, PHP_URL_HOST), $category, $errno));
            throw new ClientApiException(503, $this->transportMessage($category), 'api_unavailable');
        }
        curl_close($handle);
        return [$response, $status];
    }

    /** @return array{0:string,1:int} */
    private function requestWithStreams(string $url, string $base, string $method, array $headers, ?string $content): array {
        $context = stream_context_create(['http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers),
            'content' => $content ?? '',
            'timeout' => 15,
            'ignore_errors' => true,
            'follow_location' => 0,
            'max_redirects' => 0,
            'protocol_version' => 1.1,
        ]]);
        $lastError = null;
        set_error_handler(static function (int $severity, string $message) use (&$lastError): bool {
            $lastError = $message;
            return true;
        });
        try {
            $response = file_get_contents($url, false, $context);
        } finally {
            restore_error_handler();
        }
        $statusLine = $http_response_header[0] ?? '';
        preg_match('/\\s([1-5][0-9]{2})\\s/', $statusLine, $match);
        $status = isset($match[1]) ? (int)$match[1] : 0;
        if ($response === false) {
            error_log(sprintf('Client API stream transport failure: host=%s detail=%s', (string)parse_url($base, PHP_URL_HOST), preg_replace('/[\\r\\n]+/', ' ', (string)$lastError)));
            $category = stripos((string)$lastError, 'certificate') !== false || stripos((string)$lastError, 'SSL') !== false ? 'ssl' : 'network';
            throw new ClientApiException(503, $this->transportMessage($category), 'api_unavailable');
        }
        return [$response, $status];
    }

    private function curlErrorCategory(int $errno): string {
        // cURL error numbers are stable across the Windows and Unix PHP builds.
        if ($errno === 28) return 'timeout';
        if (in_array($errno, [35, 51, 58, 59, 60, 64], true)) return 'ssl';
        if (in_array($errno, [5, 6], true)) return 'dns';
        return 'network';
    }

    private function transportMessage(string $category): string {
        return match ($category) {
            'dns' => 'Le nom du serveur API est introuvable. Vérifiez CLIENT_API_BASE_URL et le réseau.',
            'ssl' => 'La connexion sécurisée à l’API a échoué. Vérifiez son certificat HTTPS.',
            'timeout' => 'L’API ne répond pas dans le délai prévu. Réessayez plus tard.',
            default => 'L’API est inaccessible. Vérifiez que son serveur est démarré et que CLIENT_API_BASE_URL est correct.',
        };
    }

    public static function data(array $payload): mixed {
        return $payload['data'] ?? null;
    }
}
