<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once CLIENT_ROOT . '/components/layout.php';

header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https:; media-src 'self' https: blob:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; script-src 'self' https://cdn.jsdelivr.net; connect-src 'self'; frame-ancestors 'none'; form-action 'self'; base-uri 'self'");

$requestedPage = $_GET['page'] ?? null;
$page = preg_match('/^[a-z_]+$/', (string)$requestedPage) ? (string)$requestedPage : 'dashboard';
if ($page === 'content_updates' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, private');
    try {
        echo json_encode(client_api()->request('GET', 'api/content/updates'), JSON_THROW_ON_ERROR);
    } catch (Throwable $e) {
        http_response_code(503);
        echo json_encode(['error' => ['code' => 'content_updates_unavailable', 'message' => 'Le statut des contenus est temporairement indisponible.']], JSON_THROW_ON_ERROR);
    }
    exit;
}
if ($page === 'notification_updates' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, private');
    if (empty($_SESSION['client_token'])) {
        http_response_code(401);
        echo json_encode(['error' => ['code' => 'authentication_required', 'message' => 'Session client requise.']], JSON_THROW_ON_ERROR);
        exit;
    }
    try {
        echo json_encode(client_api()->request('GET', 'api/notifications?page=1&per_page=20'), JSON_THROW_ON_ERROR);
    } catch (ClientApiException $e) {
        http_response_code($e->status === 401 || $e->status === 403 ? 401 : 503);
        echo json_encode(['error' => ['code' => 'notifications_unavailable', 'message' => 'Les notifications sont temporairement indisponibles.']], JSON_THROW_ON_ERROR);
    }
    exit;
}

$publicPages = ['login', 'register', 'forgot', 'reset'];
$guestPages = ['dashboard', 'explore', 'destination', 'heritage', 'map', 'transport', 'videos', 'video', 'profile', 'actualites', 'promotions', 'hebergements', 'activites'];
$pageFiles = [
    'dashboard' => ['Tourisme Ivoire', 'dashboard.php'], 'profile' => ['Mon profil', 'profile.php'],
    'profile_edit' => ['Modifier mon profil', 'profile_edit.php'],
    'profile_photo_setup' => ['Personnaliser mon espace', 'profile_photo_setup.php'],
    'reservations' => ['Mes réservations', 'reservations.php'], 'favorites' => ['Mes favoris', 'favorites.php'],
    'notifications' => ['Notifications', 'notifications.php'],
    'settings' => ['Paramètres & accessibilité', 'settings.php'],
    'offline_library' => ['Bibliothèque hors ligne', 'offline_library.php'],
    'explore' => ['Explorer', 'explore.php'], 'destination' => ['Destination', 'destination.php'],
    'heritage' => ['Patrimoine & culture', 'heritage.php'], 'map' => ['Carte touristique', 'map.php'],
    'transport' => ['Transports', 'transport.php'], 'passport' => ['Passeport numérique', 'passport.php'],
    'videos' => ['Communauté vidéo', 'videos.php'],
    'actualites' => ['Actualités', 'tourism_content.php'],
    'promotions' => ['Promotions', 'tourism_content.php'],
    'hebergements' => ['Hébergements', 'tourism_content.php'],
    'activites' => ['Activités touristiques', 'tourism_content.php'],
    'video_studio' => ['Studio de création', 'video_studio.php'], 'video' => ['Détail de la vidéo', 'video.php'],
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
            client_redirect($action === 'register' ? 'profile_photo_setup' : 'dashboard');
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
            session_regenerate_id(true);
            $_SESSION = [];
            client_flash('Vous êtes déconnecté de votre espace client.');
            client_redirect('login');
        }
        $user = client_require_user();
        $api = client_api();
        if ($action === 'profile') {
            $profileData = [
                'nom' => trim((string)($_POST['nom'] ?? '')),
                'prenom' => trim((string)($_POST['prenom'] ?? '')),
                'telephone' => trim((string)($_POST['telephone'] ?? '')),
            ];
            $api->request('PUT', 'api/auth/me', $profileData);
            if (is_array($_SESSION['client_user'] ?? null)) {
                $_SESSION['client_user'] = array_merge($_SESSION['client_user'], $profileData);
            }
            client_flash('Votre profil a été mis à jour.');
            client_redirect('profile');
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
        if ($action === 'notification_read_all') {
            $payload = ClientApi::data($api->request('GET', 'api/notifications?page=1'));
            $rows = is_array($payload['items'] ?? null) ? $payload['items'] : [];
            foreach ($rows as $notification) {
                if (!empty($notification['lu_at'])) continue;
                $id = filter_var($notification['id'] ?? null, FILTER_VALIDATE_INT);
                if (!$id || $id < 1) throw new ClientApiException(502, 'Une notification reçue est invalide.');
                $api->request('PUT', 'api/notifications/' . $id . '/read', []);
            }
            client_flash('Notifications marquées comme lues.'); client_redirect('notifications');
        }
        if ($action === 'visit_record') {
            $id = filter_var($_POST['destination_id'] ?? null, FILTER_VALIDATE_INT); if (!$id || $id < 1) throw new ClientApiException(422, 'Destination invalide.');
            $api->request('POST', 'api/passport/visits', ['destination_id' => $id]); client_flash('Visite ajoutée à votre passeport.'); header('Location: ?page=destination&id=' . $id); exit;
        }
        if ($action === 'heritage_view') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT); if (!$id || $id < 1) throw new ClientApiException(422, 'Contenu invalide.');
            $api->request('POST', 'api/patrimoines/' . $id . '/view', []); client_flash('Consultation enregistrée dans votre parcours culturel.'); client_redirect('heritage');
        }
        if ($action === 'reserve') {
            $id = filter_var($_POST['itineraire_id'] ?? null, FILTER_VALIDATE_INT); $quantity = filter_var($_POST['quantite'] ?? 1, FILTER_VALIDATE_INT);
            if (!$id || $id < 1 || !$quantity || $quantity < 1 || $quantity > 20) throw new ClientApiException(422, 'Itinéraire ou quantité invalide.');
            $reservation = ClientApi::data($api->request('POST', 'api/reservations', ['itineraire_id' => $id, 'quantite' => $quantity]));
            if (!is_array($reservation) || empty($reservation['reference'])) throw new ClientApiException(502, 'La référence de réservation est absente de la réponse.');
            client_flash('Demande envoyée : ' . (string)$reservation['reference']); client_redirect('reservations');
        }
        if ($action === 'avatar_upload') {
            $file = $_FILES['avatar'] ?? [];
            if (($file['size'] ?? 0) < 1 || $file['size'] > 5 * 1024 * 1024) throw new ClientApiException(422, 'L’image doit faire au maximum 5 Mo.');
            $api->upload('api/auth/me/avatar', [], 'avatar', $file); client_flash('Photo de profil mise à jour.'); client_redirect($page === 'profile_photo_setup' ? 'dashboard' : 'profile');
        }
        if ($action === 'video_upload') {
            $title = trim((string)($_POST['title'] ?? '')); $description = trim((string)($_POST['description'] ?? ''));
            $file = $_FILES['video'] ?? [];
            if ($title === '' || mb_strlen($title) > 180 || ($file['size'] ?? 0) < 1 || $file['size'] > 100 * 1024 * 1024) throw new ClientApiException(422, 'Un titre et une vidéo de 100 Mo maximum sont requis.');
            $fields = ['titre' => $title, 'description' => $description, 'autorisation_diffusion' => isset($_POST['rights_confirmed']) ? '1' : '0'];
            $api->upload('api/videos', $fields, 'video', $file); client_flash('Vidéo envoyée. Elle sera visible après validation.'); client_redirect('videos');
        }
        if ($action === 'video_like') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT); if (!$id || $id < 1) throw new ClientApiException(422, 'Vidéo invalide.');
            $api->request('POST', 'api/videos/' . $id . '/like', []); client_flash('Votre réaction a été enregistrée.'); header('Location: ?page=video&id=' . $id); exit;
        }
        if ($action === 'video_comment') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT); $comment = trim((string)($_POST['comment'] ?? ''));
            if (!$id || $id < 1 || $comment === '' || mb_strlen($comment) > 2000) throw new ClientApiException(422, 'Le commentaire doit contenir entre 1 et 2 000 caractères.');
            $api->request('POST', 'api/videos/' . $id . '/comment', ['contenu' => $comment]); client_flash('Commentaire publié.'); header('Location: ?page=video&id=' . $id); exit;
        }
        if ($action === 'settings') {
            $theme = in_array($_POST['theme'] ?? '', ['systeme','clair','sombre'], true) ? (string)$_POST['theme'] : 'systeme';
            $api->request('PUT', 'api/settings', ['theme' => $theme, 'accessibilite' => ['contraste_eleve' => isset($_POST['contraste_eleve']), 'texte_agrandi' => isset($_POST['texte_agrandi'])]]);
            client_flash('Vos préférences ont été enregistrées.'); client_redirect('settings');
        }
        throw new ClientApiException(404, 'Action inconnue.');
    } catch (ClientApiException $e) {
        if ($e->status === 401 && !in_array($action, ['login','register'], true)) { unset($_SESSION['client_token'], $_SESSION['client_user']); client_flash('Votre session a expiré. Veuillez vous reconnecter.', 'warning'); client_redirect('login'); }
        client_flash($e->getMessage(), 'error');
        if (in_array($action, ['video_like', 'video_comment'], true) && filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT)) { header('Location: ?page=video&id=' . (int)$_POST['id']); exit; }
        client_redirect($page);
    } catch (Throwable $e) {
        error_log('Client portal action failed: ' . $e->getMessage());
        client_flash('Une erreur est survenue. Réessayez plus tard.', 'error');
        if (in_array($action, ['video_like', 'video_comment'], true) && filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT)) { header('Location: ?page=video&id=' . (int)$_POST['id']); exit; }
        client_redirect($page);
    }
}

if ($page === 'offline_library' && ($_GET['offline_data'] ?? '') === '1') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, private');
    if (empty($_SESSION['client_token'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Votre session a expiré. Veuillez vous reconnecter.']);
        exit;
    }
    try {
        $api = client_api();
        $user = ClientApi::data($api->request('GET', 'api/auth/me'));
        if (!is_array($user) || ($user['role'] ?? '') !== 'Client') {
            throw new ClientApiException(403, 'Espace réservé aux comptes clients.');
        }
        $destinations = [];
        for ($pageNumber = 1; $pageNumber <= 20; $pageNumber++) {
            $payload = ClientApi::data($api->request(
                'GET',
                'api/destinations?' . http_build_query(['page' => $pageNumber, 'per_page' => 50])
            ));
            $batch = is_array($payload['items'] ?? null) ? $payload['items'] : [];
            foreach ($batch as $destination) {
                $destinations[] = [
                    'id' => (int)($destination['id'] ?? 0),
                    'nom' => (string)($destination['nom'] ?? ''),
                    'description' => (string)($destination['description'] ?? ''),
                    'localite' => (string)($destination['localite'] ?? ''),
                    'categorie' => (string)($destination['categorie'] ?? ''),
                    'slug' => (string)($destination['slug'] ?? ''),
                    'image_principale' => (string)($destination['image_principale'] ?? ''),
                    'histoire' => (string)($destination['histoire'] ?? ''),
                    'adresse' => (string)($destination['adresse'] ?? ''),
                    'latitude' => $destination['latitude'] ?? null,
                    'longitude' => $destination['longitude'] ?? null,
                ];
            }
            if (count($batch) < 50) break;
        }
        echo json_encode(['destinations' => $destinations], JSON_THROW_ON_ERROR);
    } catch (ClientApiException $e) {
        http_response_code($e->status >= 500 ? 503 : $e->status);
        echo json_encode(['error' => $e->getMessage()], JSON_THROW_ON_ERROR);
    } catch (Throwable $e) {
        error_log('Client offline library download failed: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'La bibliothèque hors ligne n’a pas pu être téléchargée.'], JSON_THROW_ON_ERROR);
    }
    exit;
}

if (in_array($page, $publicPages, true)) {
    if (!empty($_SESSION['client_token']) && $page === 'login') client_redirect('dashboard');
    ob_start(); require CLIENT_ROOT . '/pages/' . $page . '.php'; $content = ob_get_clean();
    client_layout($page, ['login'=>'Connexion','register'=>'Créer un compte','forgot'=>'Mot de passe oublié','reset'=>'Réinitialiser le mot de passe'][$page], $content);
    exit;
}

if (in_array($page, $guestPages, true)) {
    try {
        $user = null;
        $api = client_api();
        if (!empty($_SESSION['client_token'])) {
            $user = client_require_user();
            $settings = ClientApi::data($api->request('GET', 'api/settings'));
            if (!is_array($settings)) throw new ClientApiException(502, 'Les préférences du compte sont indisponibles.');
            $GLOBALS['client_settings'] = $settings;
        }
        ob_start(); require CLIENT_ROOT . '/pages/' . $pageFiles[$page][1]; $content = ob_get_clean();
        client_layout($page, $pageFiles[$page][0], $content, $user);
    } catch (ClientApiException $e) {
        http_response_code($e->status >= 500 ? 503 : 400);
        ob_start(); ?><section class="card" role="alert"><div class="empty"><div class="empty-icon">!</div><h3>Service temporairement indisponible</h3><p data-client-alert data-client-alert-type="error"><?= client_h($e->getMessage()) ?></p><a class="btn" href="?page=<?= client_h($page) ?>">Réessayer</a></div></section><?php $content = ob_get_clean();
        client_layout($page, $pageFiles[$page][0], $content, $user ?? null);
    } catch (Throwable $e) {
        error_log('Client public page render failed: ' . $e->getMessage()); http_response_code(500);
        client_layout($page, 'Erreur', '<section class="card" role="alert"><div class="empty"><h3 data-client-alert data-client-alert-type="error">La page n’a pas pu être chargée.</h3></div></section>', $user ?? null);
    }
    exit;
}

if (!isset($pageFiles[$page])) $page = 'dashboard';
try {
    $user = client_require_user();
    $api = client_api();
    $settings = ClientApi::data($api->request('GET', 'api/settings'));
    if (!is_array($settings)) throw new ClientApiException(502, 'Les préférences du compte sont indisponibles.');
    $GLOBALS['client_settings'] = $settings;
    if ($page === 'notifications') {
        $unread = ClientApi::data($api->request('GET', 'api/notifications?page=1'));
        $GLOBALS['unread_count'] = count(array_filter(is_array($unread['items'] ?? null) ? $unread['items'] : [], static fn($n) => empty($n['lu_at'])));
    }
    ob_start(); require CLIENT_ROOT . '/pages/' . $pageFiles[$page][1]; $content = ob_get_clean();
    client_layout($page, $pageFiles[$page][0], $content, $user);
} catch (ClientApiException $e) {
    if ($e->status === 401 || $e->status === 403) { unset($_SESSION['client_token'], $_SESSION['client_user']); client_redirect('login'); }
    http_response_code($e->status >= 500 ? 503 : 400);
    $retryUrl = '?page=' . rawurlencode($page);
    if ($page === 'video' && filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT)) $retryUrl .= '&id=' . (int)$_GET['id'];
    ob_start(); ?><section class="card" role="alert"><div class="empty"><div class="empty-icon">!</div><h3>Service temporairement indisponible</h3><p data-client-alert data-client-alert-type="error"><?= client_h($e->getMessage()) ?></p><a class="btn" href="<?= client_h($retryUrl) ?>">Réessayer</a></div></section><?php $content = ob_get_clean();
    client_layout($page, $pageFiles[$page][0], $content, $_SESSION['client_user'] ?? null);
} catch (Throwable $e) {
    error_log('Client portal render failed: ' . $e->getMessage()); http_response_code(500);
    client_layout($page, 'Erreur', '<section class="card" role="alert"><div class="empty"><h3 data-client-alert data-client-alert-type="error">La page n’a pas pu être chargée.</h3></div></section>', $_SESSION['client_user'] ?? null);
}
