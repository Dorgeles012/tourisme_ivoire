<?php
declare(strict_types=1);
function json_response(array $payload, int $status = 200): never {
    if (ob_get_level() > 0) ob_clean();
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    exit;
}
function request_json(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') return [];
    try { $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR); }
    catch (JsonException) { json_response(['error' => ['code' => 'invalid_json', 'message' => 'Corps JSON invalide.']], 400); }
    if (!is_array($data)) json_response(['error' => ['code' => 'invalid_body', 'message' => 'Un objet JSON est requis.']], 400);
    return $data;
}
function api_error(string $code, string $message, int $status): never {
    json_response(['error' => ['code' => $code, 'message' => $message]], $status);
}
