<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/admin/bootstrap.php';
$admin = admin_require_login();
$pdo = db();
$userId = (int)$admin['id'];

function settings_finish(string $kind, string $key, string $section = ''): never {
    admin_flash($kind . ':' . $key, $kind);
    header('Location: parametres.php' . ($section !== '' ? '#' . rawurlencode($section) : ''));
    exit;
}
function settings_len(string $value): int {
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}
function settings_remove_avatar(?string $url): void {
    if (!$url || !preg_match('~^uploads/avatars/[a-f0-9]{40}\\.(?:jpg|png|webp)$~D', $url)) return;
    $path = dirname(__DIR__) . '/uploads/avatars/' . basename($url);
    if (is_file($path)) @unlink($path);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!isset($_SESSION['admin_csrf']) || !hash_equals((string)$_SESSION['admin_csrf'], (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        settings_finish('error', 'csrf');
    }
    try {
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'profile') {
            $name = trim((string)($_POST['nom'] ?? ''));
            $first = trim((string)($_POST['prenom'] ?? ''));
            $email = strtolower(trim((string)($_POST['email'] ?? '')));
            $phone = trim((string)($_POST['telephone'] ?? ''));
            $languageRaw = trim((string)($_POST['langue_id'] ?? ''));
            $languageId = null;
            if ($name === '' || settings_len($name) > 100 || settings_len($first) > 100 || settings_len($phone) > 30) settings_finish('error', 'profile_invalid', 'profil');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || settings_len($email) > 190) settings_finish('error', 'email_invalid', 'profil');
            if ($languageRaw !== '') {
                $languageId = filter_var($languageRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if ($languageId === false) settings_finish('error', 'language_invalid', 'profil');
                $languageCheck = $pdo->prepare('SELECT id FROM langues WHERE id=? AND actif=1');
                $languageCheck->execute([$languageId]);
                if (!$languageCheck->fetchColumn()) settings_finish('error', 'language_invalid', 'profil');
                $languageId = (int)$languageId;
            }
            $duplicate = $pdo->prepare('SELECT 1 FROM utilisateurs WHERE email=? AND id<>? LIMIT 1');
            $duplicate->execute([$email, $userId]);
            if ($duplicate->fetchColumn()) settings_finish('error', 'email_exists', 'profil');
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE utilisateurs SET nom=?,prenom=?,email=?,telephone=?,langue_id=? WHERE id=? AND deleted_at IS NULL')
                ->execute([$name, $first !== '' ? $first : null, $email, $phone !== '' ? $phone : null, $languageId, $userId]);
            $pdo->commit();
            $_SESSION['admin_user']['nom'] = $name;
            $_SESSION['admin_user']['email'] = $email;
            settings_finish('success', 'profile_saved', 'profil');
        }

        if ($action === 'avatar_upload') {
            $upload = $_FILES['avatar'] ?? null;
            if (!is_array($upload) || (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string)($upload['tmp_name'] ?? ''))) settings_finish('error', 'avatar_required', 'photo');
            if ((int)$upload['size'] < 1 || (int)$upload['size'] > 5 * 1024 * 1024) settings_finish('error', 'avatar_size', 'photo');
            $image = @getimagesize((string)$upload['tmp_name']);
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file((string)$upload['tmp_name']);
            $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if (!$image || !isset($extensions[$mime]) || ($image['mime'] ?? '') !== $mime) settings_finish('error', 'avatar_type', 'photo');
            $width = (int)($image[0] ?? 0);
            $height = (int)($image[1] ?? 0);
            if ($width < 1 || $height < 1 || $width > 8000 || $height > 8000 || $width * $height > 25000000) settings_finish('error', 'avatar_dimensions', 'photo');
            $directory = dirname(__DIR__) . '/uploads/avatars';
            if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) settings_finish('error', 'avatar_storage', 'photo');
            $filename = bin2hex(random_bytes(20)) . '.' . $extensions[$mime];
            $target = $directory . DIRECTORY_SEPARATOR . $filename;
            if (!move_uploaded_file((string)$upload['tmp_name'], $target)) settings_finish('error', 'avatar_storage', 'photo');
            $newUrl = 'uploads/avatars/' . $filename;
            try {
                $pdo->beginTransaction();
                $read = $pdo->prepare('SELECT avatar_url FROM utilisateurs WHERE id=? AND deleted_at IS NULL FOR UPDATE');
                $read->execute([$userId]);
                $oldUrl = $read->fetchColumn();
                if ($oldUrl === false) throw new RuntimeException('Compte introuvable.');
                $pdo->prepare('UPDATE utilisateurs SET avatar_url=? WHERE id=?')->execute([$newUrl, $userId]);
                $pdo->commit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                @unlink($target);
                throw $e;
            }
            settings_remove_avatar(is_string($oldUrl) ? $oldUrl : null);
            settings_finish('success', 'avatar_saved', 'photo');
        }

        if ($action === 'avatar_reset') {
            $pdo->beginTransaction();
            $read = $pdo->prepare('SELECT avatar_url FROM utilisateurs WHERE id=? AND deleted_at IS NULL FOR UPDATE');
            $read->execute([$userId]);
            $oldUrl = $read->fetchColumn();
            if ($oldUrl === false) {
                $pdo->rollBack();
                settings_finish('error', 'account_missing', 'photo');
            }
            $pdo->prepare('UPDATE utilisateurs SET avatar_url=NULL WHERE id=?')->execute([$userId]);
            $pdo->commit();
            settings_remove_avatar(is_string($oldUrl) ? $oldUrl : null);
            settings_finish('success', 'avatar_reset', 'photo');
        }

        if ($action === 'password') {
            $current = (string)($_POST['mot_de_passe_actuel'] ?? '');
            $new = (string)($_POST['nouveau_mot_de_passe'] ?? '');
            $confirmation = (string)($_POST['confirmation_mot_de_passe'] ?? '');
            $read = $pdo->prepare('SELECT mot_de_passe_hash FROM utilisateurs WHERE id=? AND deleted_at IS NULL');
            $read->execute([$userId]);
            $hash = $read->fetchColumn();
            if (!is_string($hash) || !password_verify($current, $hash)) settings_finish('error', 'password_current', 'securite');
            $classes = (int)(bool)preg_match('/[a-z]/', $new) + (int)(bool)preg_match('/[A-Z]/', $new) + (int)(bool)preg_match('/[0-9]/', $new) + (int)(bool)preg_match('/[^a-zA-Z0-9]/', $new);
            if (strlen($new) < 10 || $classes < 3) settings_finish('error', 'password_weak', 'securite');
            if (!hash_equals($new, $confirmation)) settings_finish('error', 'password_mismatch', 'securite');
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE utilisateurs SET mot_de_passe_hash=? WHERE id=?')->execute([password_hash($new, PASSWORD_DEFAULT), $userId]);
            $pdo->prepare('UPDATE jetons_auth SET revoque_at=NOW() WHERE utilisateur_id=? AND revoque_at IS NULL')->execute([$userId]);
            $pdo->commit();
            session_regenerate_id(true);
            $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
            settings_finish('success', 'password_saved', 'securite');
        }

        if ($action === 'preferences') {
            $theme = (string)($_POST['theme'] ?? '');
            if (!in_array($theme, ['systeme', 'clair', 'sombre'], true)) settings_finish('error', 'theme_invalid', 'preferences');
            $notifications = [
                'enabled' => isset($_POST['notifications_enabled']),
                'actualites' => isset($_POST['notifications_actualites']),
                'activites' => isset($_POST['notifications_activites']),
                'reservations' => isset($_POST['notifications_reservations']),
                'informations_importantes' => isset($_POST['notifications_importantes']),
            ];
            $pdo->prepare('UPDATE parametres_utilisateurs SET theme=?,notifications_json=? WHERE utilisateur_id=?')
                ->execute([$theme, json_encode($notifications, JSON_THROW_ON_ERROR), $userId]);
            settings_finish('success', 'preferences_saved', 'preferences');
        }

        if ($action === 'account_request') {
            $type = (string)($_POST['request_type'] ?? '');
            if (!in_array($type, ['desactivation', 'suppression'], true)) settings_finish('error', 'request_invalid', 'compte');
            $roleColumn = chr(96) . 'role' . chr(96);
            $pdo->beginTransaction();
            $lock = $pdo->prepare("SELECT id,$roleColumn AS role FROM utilisateurs WHERE id=? AND statut='actif' AND deleted_at IS NULL FOR UPDATE");
            $lock->execute([$userId]);
            $self = $lock->fetch();
            if (!$self) {
                $pdo->rollBack();
                settings_finish('error', 'account_missing', 'compte');
            }
            if ($self['role'] === 'Sadmin') {
                $q = $pdo->query("SELECT MIN(id) FROM utilisateurs WHERE $roleColumn='Sadmin' AND statut='actif' AND deleted_at IS NULL");
                if ((int)$q->fetchColumn() === $userId) {
                    $pdo->rollBack();
                    settings_finish('error', 'primary_admin_protected', 'compte');
                }
            }
            $pending = $pdo->prepare("SELECT id FROM demandes_compte WHERE utilisateur_id=? AND type=? AND statut='en_attente' LIMIT 1 FOR UPDATE");
            $pending->execute([$userId, $type]);
            if ($pending->fetchColumn()) {
                $pdo->rollBack();
                settings_finish('warning', 'request_pending', 'compte');
            }
            $pdo->prepare('INSERT INTO demandes_compte(utilisateur_id,type) VALUES(?,?)')->execute([$userId, $type]);
            $pdo->commit();
            settings_finish('success', 'request_sent', 'compte');
        }
        settings_finish('error', 'action_invalid');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Erreur parametres.php: ' . $e->getMessage());
        settings_finish('error', 'save_failed');
    }
}

$pdo->prepare('INSERT INTO parametres_utilisateurs(utilisateur_id) VALUES(?) ON DUPLICATE KEY UPDATE utilisateur_id=VALUES(utilisateur_id)')->execute([$userId]);
$roleColumn = chr(96) . 'role' . chr(96);
$userStmt = $pdo->prepare("SELECT u.id,u.nom,u.prenom,u.email,u.telephone,u.avatar_url,u.langue_id,u.statut,u.created_at,u.$roleColumn AS role,l.code AS langue_code FROM utilisateurs u LEFT JOIN langues l ON l.id=u.langue_id WHERE u.id=? AND u.deleted_at IS NULL LIMIT 1");
$userStmt->execute([$userId]);
$user = $userStmt->fetch();
if (!$user) {
    $_SESSION = [];
    header('Location: login.php');
    exit;
}

$languages = $pdo->query('SELECT id,code,nom FROM langues WHERE actif=1 ORDER BY CASE code WHEN \'fr\' THEN 0 WHEN \'en\' THEN 1 ELSE 2 END,nom')->fetchAll();
$locale = ($user['langue_code'] ?? '') === 'en' ? 'en' : 'fr';
$messages = [
    'csrf' => $locale === 'en' ? 'Security token invalid. Reload the page and try again.' : 'Jeton de sécurité invalide. Rechargez la page et réessayez.',
    'profile_invalid' => $locale === 'en' ? 'A name is required. Name, first name, and phone must fit the limits shown.' : 'Le nom est obligatoire. Vérifiez également les limites de longueur indiquées.',
    'email_invalid' => $locale === 'en' ? 'Enter a valid email address.' : 'Saisissez une adresse e-mail valide.',
    'email_exists' => $locale === 'en' ? 'This email address is already in use.' : 'Cette adresse e-mail est déjà utilisée.',
    'language_invalid' => $locale === 'en' ? 'The selected language is unavailable.' : 'La langue sélectionnée n’est plus disponible.',
    'profile_saved' => $locale === 'en' ? 'Your personal information was saved.' : 'Vos informations personnelles ont été enregistrées.',
    'avatar_required' => $locale === 'en' ? 'Choose an image before uploading it.' : 'Choisissez une image avant de l’envoyer.',
    'avatar_size' => $locale === 'en' ? 'The image must be between 1 byte and 5 MB.' : 'L’image doit peser entre 1 octet et 5 Mo.',
    'avatar_type' => $locale === 'en' ? 'Accepted formats: JPEG, PNG, or WebP.' : 'Formats acceptés : JPEG, PNG ou WebP.',
    'avatar_dimensions' => $locale === 'en' ? 'This image resolution is too large.' : 'La résolution de cette image est trop élevée.',
    'avatar_storage' => $locale === 'en' ? 'The server could not store this image.' : 'Le serveur n’a pas pu enregistrer cette image.',
    'avatar_saved' => $locale === 'en' ? 'Your profile photo was updated.' : 'La photo de profil a été mise à jour.',
    'avatar_reset' => $locale === 'en' ? 'The photo was reset. The default avatar is shown.' : 'La photo a été réinitialisée. L’avatar par défaut est affiché.',
    'password_current' => $locale === 'en' ? 'The current password is incorrect.' : 'Le mot de passe actuel est incorrect.',
    'password_weak' => $locale === 'en' ? 'Use at least 10 characters and at least three character types.' : 'Choisissez au moins 10 caractères avec trois types de caractères.',
    'password_mismatch' => $locale === 'en' ? 'The new passwords do not match.' : 'Les deux nouveaux mots de passe ne correspondent pas.',
    'password_saved' => $locale === 'en' ? 'Password changed. Other API sessions were revoked.' : 'Mot de passe modifié. Les autres sessions API ont été révoquées.',
    'theme_invalid' => $locale === 'en' ? 'The selected theme is invalid.' : 'Le thème sélectionné est invalide.',
    'preferences_saved' => $locale === 'en' ? 'Your preferences were saved.' : 'Vos préférences ont été enregistrées.',
    'request_invalid' => $locale === 'en' ? 'This account request is invalid.' : 'Cette demande de compte n’est pas valide.',
    'account_missing' => $locale === 'en' ? 'The account is missing or inactive.' : 'Le compte est introuvable ou inactif.',
    'primary_admin_protected' => $locale === 'en' ? 'The primary Sadmin account cannot request deactivation or deletion.' : 'Le compte Sadmin principal ne peut pas demander sa désactivation ou sa suppression.',
    'request_pending' => $locale === 'en' ? 'An identical request is already pending.' : 'Une demande identique est déjà en attente.',
    'request_sent' => $locale === 'en' ? 'Your request was recorded. The account will not change before review.' : 'Votre demande a été enregistrée. Le compte ne sera pas modifié avant son examen.',
    'action_invalid' => $locale === 'en' ? 'This action is not allowed.' : 'Cette action n’est pas autorisée.',
    'save_failed' => $locale === 'en' ? 'Saving failed. Check the information and try again.' : 'L’enregistrement a échoué. Vérifiez les informations et réessayez.',
];
$flash = admin_flash();
$flashPayload = null;
if (is_string($flash) && str_contains($flash, ':')) {
    [$flashType, $flashKey] = explode(':', $flash, 2);
    $flashPayload = [
        'type' => in_array($flashType, ['success', 'error', 'warning', 'info'], true) ? $flashType : 'error',
        'title' => $flashType === 'success' ? ($locale === 'en' ? 'Saved' : 'Enregistré') : ($locale === 'en' ? 'Please check' : 'Vérification'),
        'text' => $messages[$flashKey] ?? ($locale === 'en' ? 'An operation was completed.' : 'Une opération a été effectuée.'),
    ];
}
$csrf = admin_csrf();
$settingsStmt = $pdo->prepare('SELECT theme,notifications_json FROM parametres_utilisateurs WHERE utilisateur_id=?');
$settingsStmt->execute([$userId]);
$settings = $settingsStmt->fetch() ?: [];
$theme = in_array($settings['theme'] ?? '', ['systeme', 'clair', 'sombre'], true) ? $settings['theme'] : 'systeme';
$notificationData = json_decode((string)($settings['notifications_json'] ?? ''), true);
if (!is_array($notificationData)) $notificationData = [];
$notificationValues = ['enabled' => (bool)($notificationData['enabled'] ?? true), 'actualites' => (bool)($notificationData['actualites'] ?? true), 'activites' => (bool)($notificationData['activites'] ?? true), 'reservations' => (bool)($notificationData['reservations'] ?? true), 'informations_importantes' => (bool)($notificationData['informations_importantes'] ?? true)];
$requestStmt = $pdo->prepare('SELECT type,statut,demandee_at FROM demandes_compte WHERE utilisateur_id=? ORDER BY demandee_at DESC,id DESC LIMIT 5');
$requestStmt->execute([$userId]);
$requests = $requestStmt->fetchAll();
$hasPending = in_array('en_attente', array_column($requests, 'statut'), true);
$primaryId = (int)$pdo->query("SELECT MIN(id) FROM utilisateurs WHERE $roleColumn='Sadmin' AND statut='actif' AND deleted_at IS NULL")->fetchColumn();
$isPrimaryAdmin = $primaryId === $userId;
$avatarUrl = '';
if (is_string($user['avatar_url']) && preg_match('~^uploads/(?:avatars|profils)/[a-f0-9]{40}\\.(?:jpg|png|webp|avif)$~D', $user['avatar_url'])) {
    $avatarUrl = '../' . ltrim($user['avatar_url'], '/');
}
$fullName = trim((string)($user['prenom'] ?? '') . ' ' . (string)$user['nom']);
$initials = function_exists('mb_substr') ? mb_strtoupper(mb_substr($fullName, 0, 2, 'UTF-8'), 'UTF-8') : strtoupper(substr($fullName, 0, 2));
?>
<!doctype html>
<html lang="<?= admin_escape($locale) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="color-scheme" content="light dark">
  <title><?= $locale === 'en' ? 'Account settings' : 'Paramètres du compte' ?> — Tourisme Ivoire</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
  <style>
    :root {
      --primary: #e67e22;
      --primary-dark: #d35400;
      --bg-body: #f4f6f9;
      --border-color: #e9ecef;
      --sidebar-width: 260px;
      --topbar-height: 64px;
      --text-dark: #1a1d29;
      --text-muted: #8892a6;
      --nav-blue: #2d7ff9;
    }
    [data-theme="sombre"] {
      --bg-body: #12141c;
      --border-color: #2a2e3f;
      --text-dark: #e8eaf0;
      --text-muted: #8892a6;
      --nav-blue: #4d94ff;
    }
    * { font-family: 'Inter', sans-serif; box-sizing: border-box; }
    body { background: var(--bg-body); min-height: 100vh; margin: 0; transition: background 0.3s; }

    .sidebar {
      position: fixed !important;
      top: 0 !important; left: 0 !important; bottom: 0 !important;
      height: 100vh !important; overflow-y: auto; z-index: 1000;
    }
    .topbar {
      position: fixed; top: 0; left: var(--sidebar-width); right: 0;
      height: var(--topbar-height);
      background: #fff; border-bottom: 1px solid var(--border-color);
      padding: 0 1.75rem;
      display: flex; justify-content: space-between; align-items: center;
      z-index: 900; box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }
    [data-theme="sombre"] .topbar { background: #1a1d29; }
    .topbar strong { font-weight: 700; letter-spacing: 0.5px; color: var(--text-dark); font-size: 0.95rem; }
    .topbar a {
      color: #4a5568; text-decoration: none; font-size: 0.875rem; font-weight: 500;
      padding: 0.45rem 1rem; border-radius: 8px; border: 1px solid var(--border-color);
      background: #fff; transition: all 0.2s;
    }
    .topbar a:hover { background: #f8f9fb; border-color: #d0d5dd; color: var(--text-dark); }

    .content {
      margin-left: var(--sidebar-width);
      padding: calc(var(--topbar-height) + 2rem) 2.5rem 2rem;
      min-height: 100vh;
    }
    .settings-shell { max-width: 900px; margin: 0 auto; }

    .settings-heading {
      display: flex; justify-content: space-between; align-items: flex-start;
      margin-bottom: 2rem; gap: 1rem; flex-wrap: wrap;
    }
    .eyebrow {
      font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1.5px;
      color: var(--primary); font-weight: 600; margin-bottom: 0.25rem;
    }
    .settings-heading h1 { font-size: 1.75rem; font-weight: 700; color: var(--text-dark); margin-bottom: 0.25rem; }
    .muted { color: var(--text-muted); font-size: 0.9rem; margin: 0; }
    .settings-role {
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      color: #fff; padding: 0.4rem 1rem; border-radius: 50px;
      font-size: 0.78rem; font-weight: 600; letter-spacing: 0.5px;
      box-shadow: 0 4px 12px rgba(230,126,34,0.3);
    }

    /* ===== Navigation avec barre bleue active ===== */
    .settings-nav {
      display: flex; gap: 0.35rem; flex-wrap: wrap; margin-bottom: 1.75rem;
      background: #fff; padding: 0.5rem; border-radius: 12px;
      border: 1px solid var(--border-color);
      box-shadow: 0 2px 8px rgba(0,0,0,0.03);
      position: sticky; top: var(--topbar-height); z-index: 50;
    }
    [data-theme="sombre"] .settings-nav { background: #1a1d29; }
    .settings-nav a {
      position: relative;
      padding: 0.5rem 1rem 0.65rem; border-radius: 8px; text-decoration: none;
      color: #4a5568; font-size: 0.85rem; font-weight: 500;
      transition: all 0.2s;
    }
    .settings-nav a::after {
      content: ''; position: absolute; left: 50%; bottom: 0;
      width: 0; height: 3px; border-radius: 3px 3px 0 0;
      background: var(--nav-blue);
      transform: translateX(-50%);
      transition: width 0.25s ease;
    }
    .settings-nav a:hover { background: #f8f9fb; color: var(--text-dark); }
    [data-theme="sombre"] .settings-nav a:hover { background: #2a2e3f; color: #fff; }
    .settings-nav a.is-active {
      color: var(--nav-blue);
      font-weight: 600;
    }
    .settings-nav a.is-active::after {
      width: 70%;
    }

    .settings-card {
      background: #fff; border-radius: 14px; padding: 1.75rem 2rem;
      border: 1px solid var(--border-color);
      box-shadow: 0 4px 16px rgba(0,0,0,0.04);
      margin-bottom: 1.25rem; scroll-margin-top: calc(var(--topbar-height) + 60px);
    }
    [data-theme="sombre"] .settings-card { background: #1a1d29; }
    .settings-card-heading {
      margin-bottom: 1.5rem;
      padding-bottom: 1.25rem; border-bottom: 1px solid var(--border-color);
    }
    .settings-card-heading h2 { font-size: 1.1rem; font-weight: 700; color: var(--text-dark); margin: 0 0 0.2rem; }
    .settings-card-heading p { font-size: 0.85rem; color: var(--text-muted); margin: 0; }

    .settings-grid {
      display: grid; grid-template-columns: 1fr 1fr; gap: 1rem 1.25rem;
    }
    .settings-grid > label { display: block; }
    .settings-grid label,
    .settings-form > label,
    .settings-select-label {
      font-size: 0.82rem; font-weight: 600; color: var(--text-dark);
      display: block; margin-bottom: 0.35rem;
    }
    .settings-form input[type="text"],
    .settings-form input[type="email"],
    .settings-form input[type="tel"],
    .settings-form input[type="password"],
    .settings-form input:not([type]),
    .settings-form select {
      width: 100%; border: 1px solid var(--border-color); border-radius: 10px;
      padding: 0.65rem 0.95rem; font-size: 0.9rem; color: var(--text-dark);
      background: #fafbfc; transition: all 0.2s; font-family: inherit;
    }
    [data-theme="sombre"] .settings-form input,
    [data-theme="sombre"] .settings-form select { background: #12141c; color: #e8eaf0; }
    .settings-form input:focus, .settings-form select:focus {
      outline: none; border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(230,126,34,0.15); background: #fff;
    }
    [data-theme="sombre"] .settings-form input:focus,
    [data-theme="sombre"] .settings-form select:focus { background: #1a1d29; }
    .settings-form input[readonly] { background: #f0f2f5; color: var(--text-muted); cursor: not-allowed; }

    .settings-actions { display: flex; gap: 0.75rem; flex-wrap: wrap; margin-top: 1.5rem; }
    .settings-button {
      border-radius: 10px; padding: 0.7rem 1.5rem; font-size: 0.9rem; font-weight: 600;
      border: 1px solid var(--border-color); background: #fff; color: #4a5568;
      transition: all 0.2s; cursor: pointer; font-family: inherit;
    }
    .settings-button:hover { background: #f8f9fb; border-color: #d0d5dd; color: var(--text-dark); }
    .settings-button.primary {
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      color: #fff; border: none; box-shadow: 0 4px 12px rgba(230,126,34,0.3);
    }
    .settings-button.primary:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(230,126,34,0.4); }
    .settings-button.secondary { background: #fff; color: var(--text-dark); }
    .settings-button.danger { background: #fff; color: #c0392b; border-color: #f5b7b1; }
    .settings-button.danger:hover { background: #fdecea; border-color: #e74c3c; }
    .settings-button:disabled { opacity: 0.5; cursor: not-allowed; }

    .settings-help { font-size: 0.78rem; color: var(--text-muted); margin-top: 0.5rem; }

    .avatar-editor {
      display: flex; gap: 2rem; align-items: flex-start; flex-wrap: wrap;
    }
    .avatar-frame {
      width: 110px; height: 110px; border-radius: 50%;
      background: linear-gradient(135deg, #fff5e6, #fdecea);
      border: 3px solid #fff; box-shadow: 0 4px 20px rgba(0,0,0,0.1);
      display: flex; align-items: center; justify-content: center;
      font-size: 2rem; font-weight: 700; color: var(--primary);
      overflow: hidden; flex-shrink: 0;
    }
    .avatar-frame img { width: 100%; height: 100%; object-fit: cover; }
    .avatar-controls { flex: 1; min-width: 240px; display: flex; flex-direction: column; gap: 1rem; }
    .avatar-form input[type="file"] {
      width: 100%; padding: 0.55rem 0.75rem; font-size: 0.85rem;
      background: #fafbfc; border: 1px dashed var(--border-color);
      border-radius: 10px; cursor: pointer;
    }
    .avatar-form input[type="file"]:hover { border-color: var(--primary); background: #fffaf5; }

    .password-field span {
      position: relative; display: block;
    }
    .password-field span input { padding-right: 2.75rem; }
    .password-field button {
      position: absolute; right: 0.5rem; top: 50%; transform: translateY(-50%);
      background: transparent; border: none; color: var(--text-muted); cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      padding: 0.35rem; border-radius: 6px;
    }
    .password-field button svg { width: 20px; height: 20px; display: block; }
    .password-field button svg[hidden] { display: none !important; }
    .password-field button:hover { background: #f0f2f5; color: var(--primary); }

    .password-strength {
      margin-top: 1rem; display: flex; align-items: center; gap: 0.75rem;
    }
    .password-strength > span {
      flex: 1; height: 6px; background: #f0f2f5; border-radius: 50px;
      position: relative; overflow: hidden;
    }
    .password-strength > span::after {
      content: ''; position: absolute; inset: 0; width: 0%;
      background: #e74c3c; transition: width 0.3s, background 0.3s;
    }
    .password-strength[data-level="1"] > span::after { width: 25%; background: #e74c3c; }
    .password-strength[data-level="2"] > span::after { width: 50%; background: #f39c12; }
    .password-strength[data-level="3"] > span::after { width: 75%; background: #3498db; }
    .password-strength[data-level="4"] > span::after { width: 100%; background: #27ae60; }
    .password-strength small { font-size: 0.75rem; color: var(--text-muted); white-space: nowrap; }

    .settings-select-label { margin-bottom: 1rem; }

    .notification-title {
      font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;
      color: var(--text-muted); font-weight: 700;
      margin: 1.5rem 0 0.75rem;
    }
    .settings-switch {
      display: flex; justify-content: space-between; align-items: center;
      gap: 1rem; padding: 0.85rem 1rem;
      background: #fafbfc; border: 1px solid var(--border-color);
      border-radius: 10px; cursor: pointer; transition: all 0.2s;
      margin-bottom: 0.6rem;
    }
    [data-theme="sombre"] .settings-switch { background: #12141c; }
    .settings-switch:hover { border-color: var(--primary); background: #fffaf5; }
    .settings-switch span { display: flex; flex-direction: column; gap: 0.15rem; }
    .settings-switch b { font-size: 0.875rem; font-weight: 600; color: var(--text-dark); }
    .settings-switch small { font-size: 0.78rem; color: var(--text-muted); }
    .settings-switch input[type="checkbox"] {
      width: 44px; height: 24px; appearance: none; background: #d0d5dd;
      border-radius: 50px; position: relative; cursor: pointer;
      transition: background 0.2s; flex-shrink: 0;
    }
    .settings-switch input[type="checkbox"]::after {
      content: ''; position: absolute; top: 3px; left: 3px;
      width: 18px; height: 18px; background: #fff; border-radius: 50%;
      transition: transform 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }
    .settings-switch input[type="checkbox"]:checked { background: var(--primary); }
    .settings-switch input[type="checkbox"]:checked::after { transform: translateX(20px); }

    .notification-options { padding-left: 1rem; border-left: 2px solid #f0f2f5; margin-left: 0.5rem; }
    .settings-switch.compact { padding: 0.7rem 0.9rem; }

    .account-summary {
      display: grid; grid-template-columns: auto 1fr; gap: 0.6rem 1.5rem;
      padding: 1rem 1.25rem; background: #fafbfc; border-radius: 10px;
      border: 1px solid var(--border-color); margin-bottom: 1.25rem;
      font-size: 0.875rem;
    }
    [data-theme="sombre"] .account-summary { background: #12141c; }
    .account-summary span { color: var(--text-muted); font-weight: 500; }
    .account-summary b { color: var(--text-dark); font-weight: 600; }
    .status-pill {
      display: inline-block; padding: 0.2rem 0.7rem; border-radius: 50px;
      background: #e8f8f0; color: #1e8449; font-size: 0.75rem; font-weight: 700;
      text-transform: uppercase; letter-spacing: 0.5px; width: fit-content;
    }

    .request-history { margin-bottom: 1.25rem; }
    .request-history h3 {
      font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;
      color: var(--text-muted); font-weight: 700; margin-bottom: 0.75rem;
    }
    .request-row {
      display: grid; grid-template-columns: 1fr auto auto; gap: 1rem;
      padding: 0.65rem 0.9rem; background: #fafbfc;
      border-radius: 8px; font-size: 0.82rem; margin-bottom: 0.4rem;
      align-items: center;
    }
    [data-theme="sombre"] .request-row { background: #12141c; }
    .request-row time { color: var(--text-muted); font-size: 0.78rem; }

    .protected-note {
      background: #fff8e5; color: #b9770e; border-left: 4px solid #f39c12;
      padding: 0.85rem 1.1rem; border-radius: 8px; font-size: 0.85rem;
      font-weight: 500; margin: 0;
    }
    .account-actions { display: flex; gap: 0.75rem; flex-wrap: wrap; }

    .settings-footer {
      text-align: center; font-size: 0.8rem; color: var(--text-muted);
      margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color);
    }

    @media (max-width: 992px) {
      .sidebar { position: static !important; height: auto !important; width: 100% !important; }
      .topbar { position: static; left: 0; height: auto; padding: 0.85rem 1rem; }
      .content { margin-left: 0; padding: 1.5rem 1rem; }
      .settings-card { padding: 1.5rem; }
      .settings-grid { grid-template-columns: 1fr; }
      .settings-nav { position: static; }
    }
  </style>
</head>
<body class="admin-page settings-page" data-theme="<?= admin_escape($theme) ?>" data-locale="<?= admin_escape($locale) ?>">
<?php require __DIR__ . '/_sidebar.php'; ?>
<header class="topbar">
  <strong>TOURISME IVOIRE · ADMIN</strong>
  <a href="index.php"><?= $locale === 'en' ? '← Back to dashboard' : '← Retour au tableau de bord' ?></a>
</header>

<main class="content settings-content">
  <div class="settings-shell">
    <header class="settings-heading">
      <div>
        <p class="eyebrow"><?= $locale === 'en' ? 'YOUR SPACE' : 'VOTRE ESPACE' ?></p>
        <h1><?= $locale === 'en' ? 'Settings' : 'Paramètres' ?></h1>
        <p class="muted"><?= $locale === 'en' ? 'Manage your profile, security, and preferences.' : 'Gérez vos informations, votre sécurité et vos préférences.' ?></p>
      </div>
      <span class="settings-role"><?= admin_escape((string)$user['role']) ?></span>
    </header>

    <nav class="settings-nav" aria-label="<?= $locale === 'en' ? 'Settings sections' : 'Sections des paramètres' ?>">
      <a href="#profil" data-section="profil"><?= $locale === 'en' ? 'Profile' : 'Profil' ?></a>
      <a href="#photo" data-section="photo"><?= $locale === 'en' ? 'Photo' : 'Photo' ?></a>
      <a href="#securite" data-section="securite"><?= $locale === 'en' ? 'Security' : 'Sécurité' ?></a>
      <a href="#compte" data-section="compte"><?= $locale === 'en' ? 'Account' : 'Compte' ?></a>
    </nav>

    <section class="settings-card" id="profil">
      <div class="settings-card-heading">
        <h2><?= $locale === 'en' ? 'Personal information' : 'Informations personnelles' ?></h2>
        <p><?= $locale === 'en' ? 'These details identify your account.' : 'Ces informations identifient votre compte.' ?></p>
      </div>
      <form method="post" class="settings-form" novalidate data-validate="profile">
        <input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>">
        <input type="hidden" name="action" value="profile">
        <div class="settings-grid">
          <label><?= $locale === 'en' ? 'Last name' : 'Nom' ?><input name="nom" maxlength="100" required value="<?= admin_escape((string)$user['nom']) ?>" autocomplete="family-name"></label>
          <label><?= $locale === 'en' ? 'First name' : 'Prénom' ?><input name="prenom" maxlength="100" value="<?= admin_escape((string)($user['prenom'] ?? '')) ?>" autocomplete="given-name"></label>
          <label><?= $locale === 'en' ? 'Email address' : 'Adresse e-mail' ?><input name="email" type="email" maxlength="190" required value="<?= admin_escape((string)$user['email']) ?>" autocomplete="email"></label>
          <label><?= $locale === 'en' ? 'Phone' : 'Téléphone' ?><input name="telephone" type="tel" maxlength="30" value="<?= admin_escape((string)($user['telephone'] ?? '')) ?>" autocomplete="tel"></label>
          <label><?= $locale === 'en' ? 'Preferred language' : 'Langue préférée' ?>
            <select name="langue_id">
              <option value=""><?= $locale === 'en' ? 'Choose a language' : 'Choisir une langue' ?></option>
              <?php foreach ($languages as $language): ?>
                <option value="<?= (int)$language['id'] ?>" <?= (int)($user['langue_id'] ?? 0) === (int)$language['id'] ? 'selected' : '' ?>><?= admin_escape($language['nom']) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label><?= $locale === 'en' ? 'Account created' : 'Compte créé le' ?><input value="<?= admin_escape(date('d/m/Y', strtotime((string)$user['created_at']))) ?>" readonly></label>
        </div>
        <div class="settings-actions">
          <button class="settings-button primary" type="submit"><?= $locale === 'en' ? 'Save profile' : 'Enregistrer le profil' ?></button>
        </div>
      </form>
    </section>

    <section class="settings-card" id="photo">
      <div class="settings-card-heading">
        <h2><?= $locale === 'en' ? 'Profile photo' : 'Photo de profil' ?></h2>
        <p>JPEG, PNG, WebP · 5 Mo maximum.</p>
      </div>
      <div class="avatar-editor">
        <div class="avatar-frame">
          <?php if ($avatarUrl): ?>
            <img id="avatar-preview" src="<?= admin_escape($avatarUrl) ?>" alt="<?= $locale === 'en' ? 'Current profile photo' : 'Photo de profil actuelle' ?>">
          <?php else: ?>
            <span id="avatar-initials"><?= admin_escape($initials) ?></span>
            <img id="avatar-preview" alt="" hidden>
          <?php endif; ?>
        </div>
        <div class="avatar-controls">
          <form method="post" enctype="multipart/form-data" class="settings-form avatar-form" novalidate data-validate="avatar">
            <input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>">
            <input type="hidden" name="action" value="avatar_upload">
            <label><?= $locale === 'en' ? 'Choose an image' : 'Sélectionner une image' ?>
              <input id="avatar-file" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" required>
            </label>
            <p class="settings-help"><?= $locale === 'en' ? 'Preview the photo before uploading it.' : 'Prévisualisez la photo avant son envoi.' ?></p>
            <button class="settings-button primary" type="submit"><?= $locale === 'en' ? 'Save photo' : 'Enregistrer la photo' ?></button>
          </form>
          <form method="post" data-confirm="avatar-reset">
            <input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>">
            <input type="hidden" name="action" value="avatar_reset">
            <button class="settings-button secondary" type="submit"><?= $locale === 'en' ? 'Reset photo' : 'Réinitialiser la photo' ?></button>
          </form>
        </div>
      </div>
    </section>

    <section class="settings-card" id="securite">
      <div class="settings-card-heading">
        <h2><?= $locale === 'en' ? 'Security and password' : 'Sécurité et mot de passe' ?></h2>
        <p><?= $locale === 'en' ? 'Choose a strong, unique password.' : 'Choisissez un mot de passe solide et unique.' ?></p>
      </div>
      <form method="post" class="settings-form" novalidate data-validate="password">
        <input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>">
        <input type="hidden" name="action" value="password">
        <div class="settings-grid">
          <label class="password-field"><?= $locale === 'en' ? 'Current password' : 'Mot de passe actuel' ?>
            <span>
              <input name="mot_de_passe_actuel" type="password" required autocomplete="current-password">
              <button type="button" data-password-toggle data-show-label="<?= $locale === 'en' ? 'Show password' : 'Afficher le mot de passe' ?>" data-hide-label="<?= $locale === 'en' ? 'Hide password' : 'Masquer le mot de passe' ?>" aria-label="<?= $locale === 'en' ? 'Show password' : 'Afficher le mot de passe' ?>" aria-pressed="false">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" data-eye-open><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="2.5" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" data-eye-closed hidden><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="2.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="m3 3 18 18" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
              </button>
            </span>
          </label>
          <div></div>
          <label class="password-field"><?= $locale === 'en' ? 'New password' : 'Nouveau mot de passe' ?>
            <span>
              <input id="new-password" name="nouveau_mot_de_passe" type="password" required autocomplete="new-password">
              <button type="button" data-password-toggle data-show-label="<?= $locale === 'en' ? 'Show password' : 'Afficher le mot de passe' ?>" data-hide-label="<?= $locale === 'en' ? 'Hide password' : 'Masquer le mot de passe' ?>" aria-label="<?= $locale === 'en' ? 'Show password' : 'Afficher le mot de passe' ?>" aria-pressed="false">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" data-eye-open><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="2.5" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" data-eye-closed hidden><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="2.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="m3 3 18 18" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
              </button>
            </span>
          </label>
          <label class="password-field"><?= $locale === 'en' ? 'Confirm new password' : 'Confirmer le nouveau mot de passe' ?>
            <span>
              <input name="confirmation_mot_de_passe" type="password" required autocomplete="new-password">
              <button type="button" data-password-toggle data-show-label="<?= $locale === 'en' ? 'Show password' : 'Afficher le mot de passe' ?>" data-hide-label="<?= $locale === 'en' ? 'Hide password' : 'Masquer le mot de passe' ?>" aria-label="<?= $locale === 'en' ? 'Show password' : 'Afficher le mot de passe' ?>" aria-pressed="false">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" data-eye-open><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="2.5" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" data-eye-closed hidden><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="2.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="m3 3 18 18" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
              </button>
            </span>
          </label>
        </div>
        <div class="password-strength" aria-live="polite">
          <span data-strength-bar></span>
          <small data-strength-text><?= $locale === 'en' ? 'Password strength' : 'Robustesse du mot de passe' ?></small>
        </div>
        <p class="settings-help"><?= $locale === 'en' ? 'At least 10 characters and three types: lowercase, uppercase, number, or symbol.' : '10 caractères minimum et au moins trois types : minuscules, majuscules, chiffres ou symboles.' ?></p>
        <div class="settings-actions">
          <button class="settings-button primary" type="submit"><?= $locale === 'en' ? 'Change password' : 'Modifier le mot de passe' ?></button>
        </div>
      </form>
    </section>

    <section class="settings-card account-card" id="compte">
      <div class="settings-card-heading">
        <h2><?= $locale === 'en' ? 'Privacy and account' : 'Confidentialité et compte' ?></h2>
        <p><?= $locale === 'en' ? 'Sensitive actions are requests and require review.' : 'Les actions sensibles sont des demandes et nécessitent un examen.' ?></p>
      </div>
      <div class="account-summary">
        <span><?= $locale === 'en' ? 'Account status' : 'Statut du compte' ?></span>
        <b class="status-pill"><?= admin_escape($user['statut'] === 'actif' ? ($locale === 'en' ? 'Active' : 'Actif') : ($locale === 'en' ? 'Pending' : 'En attente')) ?></b>
        <span><?= $locale === 'en' ? 'Role' : 'Rôle' ?></span>
        <b><?= admin_escape((string)$user['role']) ?></b>
        <span><?= $locale === 'en' ? 'Email address' : 'Adresse e-mail' ?></span>
        <b><?= admin_escape((string)$user['email']) ?></b>
      </div>
      <?php if ($requests): ?>
        <div class="request-history">
          <h3><?= $locale === 'en' ? 'Recent requests' : 'Demandes récentes' ?></h3>
          <?php foreach ($requests as $request): ?>
            <div class="request-row">
              <span><?= $request['type'] === 'suppression' ? ($locale === 'en' ? 'Account deletion' : 'Suppression du compte') : ($locale === 'en' ? 'Account deactivation' : 'Désactivation du compte') ?></span>
              <span><?= $request['statut'] === 'en_attente' ? ($locale === 'en' ? 'Pending' : 'En attente') : ($request['statut'] === 'approuvee' ? ($locale === 'en' ? 'Approved' : 'Approuvée') : ($request['statut'] === 'refusee' ? ($locale === 'en' ? 'Declined' : 'Refusée') : ($locale === 'en' ? 'Cancelled' : 'Annulée'))) ?></span>
              <time><?= admin_escape(date('d/m/Y H:i', strtotime((string)$request['demandee_at']))) ?></time>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <?php if ($isPrimaryAdmin): ?>
        <p class="protected-note"><?= $locale === 'en' ? 'The primary Sadmin account is protected from deactivation and deletion requests.' : 'Le compte Sadmin principal est protégé contre les demandes de désactivation et de suppression.' ?></p>
      <?php else: ?>
        <div class="account-actions">
          <form method="post" data-confirm="deactivation">
            <input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>">
            <input type="hidden" name="action" value="account_request">
            <input type="hidden" name="request_type" value="desactivation">
            <button class="settings-button secondary" type="submit" <?= $hasPending ? 'disabled' : '' ?>><?= $locale === 'en' ? 'Request deactivation' : 'Demander la désactivation' ?></button>
          </form>
          <form method="post" data-confirm="deletion">
            <input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>">
            <input type="hidden" name="action" value="account_request">
            <input type="hidden" name="request_type" value="suppression">
            <button class="settings-button danger" type="submit" <?= $hasPending ? 'disabled' : '' ?>><?= $locale === 'en' ? 'Request deletion' : 'Demander la suppression' ?></button>
          </form>
        </div>
      <?php endif; ?>
    </section>
    <p class="settings-footer"><?= $locale === 'en' ? 'Roles and permissions cannot be changed from this page.' : 'Les rôles et autorisations ne peuvent pas être modifiés depuis cette page.' ?></p>
  </div>
</main>

<?php if ($flashPayload !== null): ?>
  <script id="settings-flash" type="application/json"><?= json_encode($flashPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<?php endif; ?>

<script>
(function () {
  'use strict';

  const body = document.body;
  const english = body.dataset.locale === 'en';
  const text = english ? {
    error: 'Please check',
    required: 'Complete all required fields.',
    invalidEmail: 'Enter a valid email address.',
    profile: 'Check the name, email, and field lengths.',
    image: 'Choose a JPEG, PNG, or WebP image up to 5 MB.',
    imageSize: 'The image must be 5 MB or smaller.',
    password: 'The new password needs 10 characters and at least three character types.',
    mismatch: 'The new passwords do not match.',
    saving: 'Saving your changes…',
    confirmTitle: 'Are you sure?',
    confirmCancel: 'Cancel',
    confirmButton: 'Continue',
    resetTitle: 'Reset your profile photo?',
    resetBody: 'The uploaded photo will be removed and the default avatar will appear.',
    deactivateTitle: 'Request account deactivation?',
    deactivateBody: 'This request will be reviewed. Your account remains active until it is processed.',
    deleteTitle: 'Request account deletion?',
    deleteBody: 'This request will be reviewed. Your account and data will not be deleted immediately.',
    passwordStrength: ['Password strength', 'Weak', 'Fair', 'Good', 'Strong'],
  } : {
    error: 'Vérification',
    required: 'Veuillez remplir tous les champs obligatoires.',
    invalidEmail: 'Saisissez une adresse e-mail valide.',
    profile: 'Vérifiez le nom, l’adresse e-mail et la longueur des champs.',
    image: 'Choisissez une image JPEG, PNG ou WebP de 5 Mo maximum.',
    imageSize: 'L’image doit peser 5 Mo maximum.',
    password: 'Le nouveau mot de passe doit contenir 10 caractères et au moins trois types de caractères.',
    mismatch: 'Les deux nouveaux mots de passe ne correspondent pas.',
    saving: 'Enregistrement en cours…',
    confirmTitle: 'Confirmer cette action ?',
    confirmCancel: 'Annuler',
    confirmButton: 'Continuer',
    resetTitle: 'Réinitialiser la photo de profil ?',
    resetBody: 'La photo envoyée sera supprimée et l’avatar par défaut sera affiché.',
    deactivateTitle: 'Demander la désactivation du compte ?',
    deactivateBody: 'La demande sera examinée. Le compte reste actif jusqu’à son traitement.',
    deleteTitle: 'Demander la suppression du compte ?',
    deleteBody: 'La demande sera examinée. Le compte et ses données ne seront pas supprimés immédiatement.',
    passwordStrength: ['Robustesse du mot de passe', 'Faible', 'Moyenne', 'Bonne', 'Forte'],
  };

  /* ===== Navigation : barre bleue sous l'onglet actif ===== */
  const navLinks = [...document.querySelectorAll('.settings-nav a[data-section]')];
  const sections = navLinks
    .map((link) => document.getElementById(link.dataset.section))
    .filter(Boolean);

  const setActiveNav = (id) => {
    navLinks.forEach((link) => {
      link.classList.toggle('is-active', link.dataset.section === id);
    });
  };

  if (sections.length && 'IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
      const visible = entries
        .filter((entry) => entry.isIntersecting)
        .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
      if (visible) setActiveNav(visible.target.id);
    }, {
      rootMargin: `-${parseInt(getComputedStyle(document.documentElement).getPropertyValue('--topbar-height')) + 80}px 0px -55% 0px`,
      threshold: [0.1, 0.25, 0.5, 0.75],
    });
    sections.forEach((section) => observer.observe(section));
  }

  navLinks.forEach((link) => {
    link.addEventListener('click', () => {
      setActiveNav(link.dataset.section);
    });
  });

  if (sections.length) {
    setActiveNav(
      (location.hash || '').replace('#', '') || 'profil'
    );
  }

  const showError = (message) => Swal.fire({
    icon: 'error',
    title: text.error,
    text: message,
    confirmButtonColor: '#ed7600',
  });

  const showLoading = () => Swal.fire({
    title: text.saving,
    allowOutsideClick: false,
    allowEscapeKey: false,
    didOpen: () => Swal.showLoading(),
  });

  const flash = document.getElementById('settings-flash');
  if (flash && window.Swal) {
    try {
      const payload = JSON.parse(flash.textContent || '{}');
      Swal.fire({
        icon: payload.type || 'info',
        title: payload.title || '',
        text: payload.text || '',
        confirmButtonColor: '#ed7600',
      });
    } catch (_) {
      showError(text.error);
    }
  }

  const fileInput = document.getElementById('avatar-file');
  if (fileInput) {
    fileInput.addEventListener('change', () => {
      const file = fileInput.files && fileInput.files[0];
      if (!file) return;
      if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        fileInput.value = '';
        showError(text.image);
        return;
      }
      if (file.size > 5 * 1024 * 1024) {
        fileInput.value = '';
        showError(text.imageSize);
        return;
      }
      const preview = document.getElementById('avatar-preview');
      const initials = document.getElementById('avatar-initials');
      if (preview) {
        if (preview.dataset.objectUrl) URL.revokeObjectURL(preview.dataset.objectUrl);
        const objectUrl = URL.createObjectURL(file);
        preview.dataset.objectUrl = objectUrl;
        preview.src = objectUrl;
        preview.hidden = false;
        if (initials) initials.hidden = true;
      }
    });
  }

  document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    const input = button.closest('label')?.querySelector('input') || button.parentElement?.querySelector('input');
    if (!input) return;
    const eyeOpen = button.querySelector('[data-eye-open]');
    const eyeClosed = button.querySelector('[data-eye-closed]');

    const syncPasswordToggle = () => {
      const isVisible = input.type === 'text';
      if (eyeOpen) eyeOpen.hidden = isVisible;
      if (eyeClosed) eyeClosed.hidden = !isVisible;
      button.setAttribute(
        'aria-label',
        isVisible ? (button.dataset.hideLabel || 'Hide password') : (button.dataset.showLabel || 'Show password')
      );
      button.setAttribute('aria-pressed', isVisible ? 'true' : 'false');
    };

    if (input.type !== 'password') input.type = 'password';
    syncPasswordToggle();

    button.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();
      input.setAttribute('type', input.getAttribute('type') === 'password' ? 'text' : 'password');
      syncPasswordToggle();
      input.focus({ preventScroll: true });
    });
  });

  const newPassword = document.getElementById('new-password');
  const strengthWrap = document.querySelector('.password-strength');
  const strengthText = document.querySelector('[data-strength-text]');
  if (newPassword && strengthWrap) {
    const updateStrength = () => {
      const value = newPassword.value;
      let level = 0;
      if (value) {
        if (value.length >= 10) level++;
        if (/[a-z]/.test(value) && /[A-Z]/.test(value)) level++;
        if (/[0-9]/.test(value)) level++;
        if (/[^a-zA-Z0-9]/.test(value)) level++;
        level = Math.min(level, 4);
      }
      strengthWrap.setAttribute('data-level', String(level));
      if (strengthText) strengthText.textContent = text.passwordStrength[level];
    };
    newPassword.addEventListener('input', updateStrength);
    updateStrength();
  }

  const notificationMaster = document.querySelector('[name="notifications_enabled"]');
  const notificationOptions = [...document.querySelectorAll('.notification-options input[type="checkbox"]')];
  const syncNotifications = () => {
    if (!notificationMaster) return;
    notificationOptions.forEach((input) => { input.disabled = !notificationMaster.checked; });
  };
  if (notificationMaster) {
    notificationMaster.addEventListener('change', syncNotifications);
    syncNotifications();
  }

  const themeSelect = document.querySelector('[name="theme"]');
  if (themeSelect) {
    themeSelect.addEventListener('change', () => { body.dataset.theme = themeSelect.value; });
  }

  const confirmCopy = {
    'avatar-reset': [text.resetTitle, text.resetBody],
    deactivation: [text.deactivateTitle, text.deactivateBody],
    deletion: [text.deleteTitle, text.deleteBody],
  };

  document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const [title, message] = confirmCopy[form.dataset.confirm] || [text.confirmTitle, ''];
      const result = await Swal.fire({
        icon: 'warning',
        title,
        text: message,
        showCancelButton: true,
        confirmButtonText: text.confirmButton,
        cancelButtonText: text.confirmCancel,
        confirmButtonColor: '#ed7600',
        cancelButtonColor: '#766e66',
      });
      if (result.isConfirmed) {
        showLoading();
        HTMLFormElement.prototype.submit.call(form);
      }
    });
  });

  document.querySelectorAll('form[data-validate]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      event.preventDefault();

      const requiredFields = [...form.querySelectorAll('input[required],select[required]')];
      if (requiredFields.some((field) => !field.value.trim())) return showError(text.required);

      if (form.dataset.validate === 'profile') {
        const emailField = form.elements.email;
        const nomField = form.elements.nom;
        const prenomField = form.elements.prenom;
        const telField = form.elements.telephone;
        if (!emailField || !nomField) return showError(text.profile);
        const email = emailField.value.trim();
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return showError(text.invalidEmail);
        const nom = nomField.value.trim();
        const prenom = prenomField ? prenomField.value.trim() : '';
        const tel = telField ? telField.value.trim() : '';
        if (nom.length > 100 || prenom.length > 100 || tel.length > 30) return showError(text.profile);
      }

      if (form.dataset.validate === 'avatar') {
        const file = fileInput && fileInput.files && fileInput.files[0];
        if (!file || !['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) return showError(text.image);
        if (file.size > 5 * 1024 * 1024) return showError(text.imageSize);
      }

      if (form.dataset.validate === 'password') {
        const newField = form.elements.nouveau_mot_de_passe;
        const confirmField = form.elements.confirmation_mot_de_passe;
        if (!newField || !confirmField) return showError(text.password);
        const value = newField.value;
        const groups = [/[a-z]/, /[A-Z]/, /[0-9]/, /[^a-zA-Z0-9]/].filter((pattern) => pattern.test(value)).length;
        if (value.length < 10 || groups < 3) return showError(text.password);
        if (value !== confirmField.value) return showError(text.mismatch);
      }

      showLoading();
      HTMLFormElement.prototype.submit.call(form);
    });
  });

  window.addEventListener('beforeunload', () => {
    const preview = document.getElementById('avatar-preview');
    if (preview && preview.dataset.objectUrl) URL.revokeObjectURL(preview.dataset.objectUrl);
  });
})();
</script>
</body>
</html>