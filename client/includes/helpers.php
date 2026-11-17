<?php
declare(strict_types=1);

function client_h(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function client_csrf(): string {
    if (!isset($_SESSION['client_csrf'])) $_SESSION['client_csrf'] = bin2hex(random_bytes(32));
    return (string)$_SESSION['client_csrf'];
}
function client_csrf_field(): string { return '<input type="hidden" name="csrf" value="' . client_h(client_csrf()) . '">'; }
function client_check_csrf(): void {
    if (!isset($_SESSION['client_csrf']) || !hash_equals((string)$_SESSION['client_csrf'], (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403); exit('Jeton de sécurité invalide. Rechargez la page.');
    }
}
function client_api(): ClientApi { return new ClientApi(isset($_SESSION['client_token']) ? (string)$_SESSION['client_token'] : null); }
function client_require_user(): array {
    if (empty($_SESSION['client_token'])) { header('Location: ?page=login'); exit; }
    try {
        $payload = client_api()->request('GET', 'api/auth/me');
        $user = ClientApi::data($payload);
        if (!is_array($user) || ($user['role'] ?? '') !== 'Client') throw new ClientApiException(403, 'Espace réservé aux comptes clients.');
        $_SESSION['client_user'] = $user;
        return $user;
    } catch (ClientApiException $e) {
        if ($e->status === 401 || $e->status === 403) {
            unset($_SESSION['client_token'], $_SESSION['client_user']);
            header('Location: ?page=login&expired=1'); exit;
        }
        throw $e;
    }
}
function client_flash(?string $message = null, string $type = 'success'): ?array {
    if ($message !== null) { $_SESSION['client_flash'] = ['message' => $message, 'type' => $type]; return null; }
    $flash = $_SESSION['client_flash'] ?? null; unset($_SESSION['client_flash']);
    return is_array($flash) ? $flash : null;
}
function client_redirect(string $page): never { header('Location: ?page=' . rawurlencode($page)); exit; }
function client_format_date(mixed $value): string {
    if (!is_string($value) || $value === '') return '—';
    try { return (new DateTimeImmutable($value))->format('d/m/Y à H:i'); } catch (Throwable) { return '—'; }
}
function client_money(mixed $amount, mixed $currency): string {
    if (!is_numeric($amount)) return 'Montant à confirmer';
    return number_format((float)$amount, 0, ',', ' ') . ' ' . client_h($currency ?: 'XOF');
}
