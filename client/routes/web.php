<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once CLIENT_ROOT . '/components/layout.php';

header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; script-src 'self'; connect-src 'self'; frame-ancestors 'none'; form-action 'self'; base-uri 'self'");

$page = preg_match('/^[a-z_]+$/', (string)($_GET['page'] ?? '')) ? (string)$_GET['page'] : 'dashboard';
$publicPages = ['login', 'register', 'forgot', 'reset'];
$pageFiles = [
    'dashboard' => ['Tableau de bord', 'dashboard.php'], 'profile' => ['Mon profil', 'profile.php'],
    'reservations' => ['Mes réservations', 'reservations.php'], 'favorites' => ['Mes favoris', 'favorites.php'],
    'history' => ['Historique', 'history.php'], 'payments' => ['Mes paiements', 'payments.php'],
    'notifications' => ['Mes notifications', 'notifications.php'], 'messages' => ['Messagerie / Assistance', 'messages.php'],
    'settings' => ['Paramètres du compte', 'settings.php'], 'help' => ['Aide et FAQ', 'help.php'],
];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    client_check_csrf();
    $action = (string)($_POST['action'] ?? '');
    try {
        if ($action === 'login' || $action === 'register') {
            $email = strtolower(trim((string)($_POST['email'] ?? '')));
            $password = (string)($_POST['password'] ?? '');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190 || $password === '' || strlen($password) > 4096) {
                throw new ClientApiException(422, 'Saisissez une adresse e-mail valide et un mot de passe de 4096 caractères maximum.');
            }
            $body = ['email' => $email, 'mot_de_passe' => $password];
            if ($action === 'register') {
                $body += ['nom' => trim((string)($_POST['nom'] ?? '')), 'prenom' => trim((string)($_POST['prenom'] ?? '')), 'telephone' => trim((string)($_POST['telephone'] ?? ''))];
            }
            $result = client_api()->request('POST', $action === 'login' ? 'api/auth/login' : 'api/auth/register', $body);
            $token = $result['token'] ?? null;
            $userData = $result['data'] ?? null;
            if (!is_string($token) || $token === '' || !is_array($userData)) {
                throw new ClientApiException(502, 'La réponse de connexion est incomplète. Réessayez plus tard.');
            }
            if (($userData['role'] ?? null) !== 'Client') {
                try { (new ClientApi($token))->request('POST', 'api/auth/logout'); }
                catch (Throwable $e) { error_log('Rejected non-client login token could not be revoked: ' . get_class($e)); }
                throw new ClientApiException(403, 'Cet espace est réservé aux comptes clients.');
            }
            session_regenerate_id(true);
            $_SESSION['client_token'] = $token;
            $_SESSION['client_user'] = $userData;
            $_SESSION['client_csrf'] = bin2hex(random_bytes(32));
            client_flash($action === 'register' ? 'Votre compte est créé. Bienvenue !' : 'Connexion réussie. Bienvenue dans votre espace.');
            client_redirect('dashboard');
        }
        if ($action === 'forgot') {
            client_api()->request('POST', 'api/auth/forgot-password', ['email' => strtolower(trim((string)($_POST['email'] ?? '')))]);
            client_flash('Si un compte correspond à cette adresse, un code de réinitialisation a été envoyé.');
            client_redirect('reset');
        }
        if ($action === 'reset') {
            client_api()->request('POST', 'api/auth/reset-password', ['email' => strtolower(trim((string)($_POST['email'] ?? ''))), 'token' => trim((string)($_POST['token'] ?? '')), 'mot_de_passe' => (string)($_POST['password'] ?? '')]);
            client_flash('Mot de passe mis à jour. Vous pouvez vous connecter.');
            client_redirect('login');
        }
        if ($action === 'logout') {
            try { client_api()->request('POST', 'api/auth/logout'); }
            catch (Throwable $e) { error_log('Client logout token revocation failed: ' . $e->getMessage()); }
            $_SESSION = [];
            if (ini_get('session.use_cookies')) { $p = session_get_cookie_params(); setcookie(session_name(), '', time() - 42000, $p['path'], '', $p['secure'], $p['httponly']); }
            session_destroy();
            header('Location: ?page=login'); exit;
        }
        $user = client_require_user();
        $api = client_api();
        if ($action === 'profile') {
            $api->request('PUT', 'api/auth/me', ['nom' => trim((string)($_POST['nom'] ?? '')), 'prenom' => trim((string)($_POST['prenom'] ?? '')), 'telephone' => trim((string)($_POST['telephone'] ?? ''))]);
            client_flash('Votre profil a été mis à jour.'); client_redirect('profile');
        }
        if ($action === 'favorite_remove') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT); if (!$id || $id < 1) throw new ClientApiException(422, 'Favori invalide.');
            $api->request('DELETE', 'api/favorites/' . $id); client_flash('Destination retirée des favoris.'); client_redirect('favorites');
        }
        if ($action === 'favorite_add') {
            $destinationId = filter_var($_POST['destination_id'] ?? null, FILTER_VALIDATE_INT); if (!$destinationId || $destinationId < 1) throw new ClientApiException(422, 'Destination invalide.');
            $api->request('POST', 'api/favorites', ['destination_id' => $destinationId]); client_flash('Destination ajoutée à vos favoris.'); client_redirect('favorites');
        }
        if ($action === 'reservation_cancel') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT); if (!$id || $id < 1) throw new ClientApiException(422, 'Réservation invalide.');
            $api->request('POST', 'api/reservations/' . $id . '/cancel', []); client_flash('La demande de réservation a été annulée.'); client_redirect('reservations');
        }
        if ($action === 'notification_read') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT); if (!$id || $id < 1) throw new ClientApiException(422, 'Notification invalide.');
            $api->request('PUT', 'api/notifications/' . $id . '/read', []); client_flash('Notification marquée comme lue.'); client_redirect('notifications');
        }
        if ($action === 'support_create') {
            $api->request('POST', 'api/support/threads', ['subject' => trim((string)($_POST['subject'] ?? '')), 'message' => trim((string)($_POST['message'] ?? ''))]);
            client_flash('Votre demande a été transmise à l’assistance.'); client_redirect('messages');
        }
        if ($action === 'support_reply') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT); if (!$id || $id < 1) throw new ClientApiException(422, 'Conversation invalide.');
            $api->request('POST', 'api/support/threads/' . $id . '/messages', ['message' => trim((string)($_POST['message'] ?? ''))]);
            client_flash('Votre message a été envoyé.'); client_redirect('messages');
        }
        if ($action === 'settings') {
            $theme = in_array($_POST['theme'] ?? '', ['systeme','clair','sombre'], true) ? (string)$_POST['theme'] : 'systeme';
            $api->request('PUT', 'api/settings', ['theme' => $theme, 'notifications' => ['email' => isset($_POST['email_notifications'])], 'accessibilite' => ['contraste_eleve' => isset($_POST['contraste_eleve']), 'texte_agrandi' => isset($_POST['texte_agrandi'])]]);
            client_flash('Vos préférences ont été enregistrées.'); client_redirect('settings');
        }
        if ($action === 'change_password') {
            $api->request('POST', 'api/auth/change-password', ['mot_de_passe_actuel' => (string)($_POST['current_password'] ?? ''), 'nouveau_mot_de_passe' => (string)($_POST['new_password'] ?? '')]);
            client_flash('Mot de passe modifié.'); client_redirect('settings');
        }
        throw new ClientApiException(404, 'Action inconnue.');
    } catch (ClientApiException $e) {
        if ($e->status === 401 && !in_array($action, ['login','register'], true)) { unset($_SESSION['client_token'], $_SESSION['client_user']); client_flash('Votre session a expiré. Veuillez vous reconnecter.', 'error'); client_redirect('login'); }
        client_flash($e->getMessage(), 'error');
        client_redirect($page);
    } catch (Throwable $e) {
        error_log('Client portal action failed: ' . $e->getMessage());
        client_flash('Une erreur est survenue. Réessayez plus tard.', 'error'); client_redirect($page);
    }
}

if (in_array($page, $publicPages, true)) {
    if (!empty($_SESSION['client_token']) && $page === 'login') client_redirect('dashboard');
    ob_start(); require CLIENT_ROOT . '/pages/' . $page . '.php'; $content = ob_get_clean();
    client_layout($page, ['login'=>'Connexion','register'=>'Créer un compte','forgot'=>'Mot de passe oublié','reset'=>'Réinitialiser le mot de passe'][$page], $content);
    exit;
}

if (!isset($pageFiles[$page])) $page = 'dashboard';
try {
    $user = client_require_user();
    $api = client_api();
    if ($page === 'notifications') {
        $unread = ClientApi::data($api->request('GET', 'api/notifications?page=1&per_page=50'));
        $GLOBALS['unread_count'] = count(array_filter(is_array($unread['items'] ?? null) ? $unread['items'] : [], static fn($n) => empty($n['lu_at'])));
    }
    ob_start(); require CLIENT_ROOT . '/pages/' . $pageFiles[$page][1]; $content = ob_get_clean();
    client_layout($page, $pageFiles[$page][0], $content, $user);
} catch (ClientApiException $e) {
    if ($e->status === 401 || $e->status === 403) { unset($_SESSION['client_token'], $_SESSION['client_user']); client_redirect('login'); }
    http_response_code($e->status >= 500 ? 503 : 400);
    ob_start(); ?><section class="card"><div class="empty"><div class="empty-icon">!</div><h3>Service temporairement indisponible</h3><p><?= client_h($e->getMessage()) ?></p><a class="btn" href="?page=<?= client_h($page) ?>">Réessayer</a></div></section><?php $content = ob_get_clean();
    client_layout($page, $pageFiles[$page][0], $content, $_SESSION['client_user'] ?? null);
} catch (Throwable $e) {
    error_log('Client portal render failed: ' . $e->getMessage()); http_response_code(500);
    client_layout($page, 'Erreur', '<section class="card"><div class="empty"><h3>La page n’a pas pu être chargée.</h3></div></section>', $_SESSION['client_user'] ?? null);
}
