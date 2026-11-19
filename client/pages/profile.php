<?php
// ===== Récupération du profil =====
$profile = null;
$errorMessage = null;
if ($user) {
    try {
        $profile = ClientApi::data($api->request('GET', 'api/auth/me'));
        $profile = is_array($profile) ? $profile : [];
    } catch (Throwable $e) {
        $errorMessage = readableError($e);
        $profile = null;
    }
}

// ===== Helpers =====
$initials = static function (array $p): string {
    $first = trim((string)($p['prenom'] ?? ''));
    $last  = trim((string)($p['nom'] ?? ''));
    $src   = $first !== '' ? $first : $last;
    if ($src === '') return '?';
    return mb_strtoupper(mb_substr($src, 0, mb_strlen($src) >= 2 ? 2 : 1));
};

$avatarUrl = '';
if (!empty($profile['avatar_url'])) {
    $raw = (string)$profile['avatar_url'];
    $avatarUrl = preg_match('#^https?://#i', $raw)
        ? $raw
        : rtrim((string)($api->baseUrl ?? ''), '/') . '/' . ltrim($raw, '/');
}

// ===== Traitement POST (avatar + update) =====
$flash     = null;
$openModal = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    client_csrf_verify();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'upload_avatar' && !empty($_FILES['avatar']['tmp_name'])) {
        try {
            $api->request('POST', 'api/auth/avatar', [], [
                'avatar' => new CURLFile(
                    $_FILES['avatar']['tmp_name'],
                    $_FILES['avatar']['type'],
                    $_FILES['avatar']['name']
                ),
            ]);
            $flash     = ['type' => 'success', 'text' => 'Photo de profil mise à jour.'];
            $profile   = ClientApi::data($api->request('GET', 'api/auth/me'));
            $avatarUrl = !empty($profile['avatar_url']) ? (string)$profile['avatar_url'] : '';
            $openModal = true;
        } catch (Throwable $e) {
            $flash     = ['type' => 'error', 'text' => readableError($e)];
            $openModal = true;
        }
    }

    if ($action === 'update_profile') {
        try {
            $api->request('PATCH', 'api/auth/me', [
                'nom'       => trim((string)($_POST['nom'] ?? '')),
                'prenom'    => trim((string)($_POST['prenom'] ?? '')),
                'telephone' => trim((string)($_POST['telephone'] ?? '')),
            ]);
            $flash     = ['type' => 'success', 'text' => 'Profil mis à jour avec succès.'];
            $profile   = ClientApi::data($api->request('GET', 'api/auth/me'));
            $openModal = true;
        } catch (Throwable $e) {
            $flash     = ['type' => 'error', 'text' => readableError($e)];
            $openModal = true;
        }
    }
}

$prenom    = (string)($profile['prenom'] ?? '');
$nom       = (string)($profile['nom'] ?? '');
$telephone = (string)($profile['telephone'] ?? '');
$ini       = $profile ? $initials($profile) : '?';
?>

<style>
  /* ===== Reset global ===== */
  html, body {
    overflow-x: hidden;
    max-width: 100vw;
  }
  *, *::before, *::after { box-sizing: border-box; }

  body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    font-size: 13px;
    line-height: 1.5;
    color: #1c1410;
  }

  /* ===== VERROUILLAGE TOTAL DU SCROLL ARRIÈRE ===== */
  html.modal-open,
  body.modal-open {
    overflow: hidden !important;
    overscroll-behavior: none;
    touch-action: none;
  }
  body.modal-open {
    position: fixed;
    top: var(--scroll-y, 0);
    left: 0;
    right: 0;
    width: 100%;
    height: 100vh;
  }

  /* ===== Flash ===== */
  .flash {
    padding: 0.6rem 0.85rem;
    border-radius: 8px;
    font-size: 0.72rem;
    font-weight: 600;
    margin-bottom: 0.85rem;
  }
  .flash-success {
    background: rgba(46, 125, 50, 0.10);
    border: 1px solid rgba(46, 125, 50, 0.3);
    color: #2E7D32;
  }
  .flash-error {
    background: rgba(192, 57, 43, 0.08);
    border: 1px solid rgba(192, 57, 43, 0.25);
    color: #c0392b;
  }

  /* ===== HERO ===== */
  .profile-hero {
    position: relative;
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 1rem;
    isolation: isolate;
  }
  .profile-hero-guest {
    min-height: 270px;
    background:
      linear-gradient(180deg, rgba(0,0,0,0.10) 0%, rgba(0,0,0,0.55) 55%, rgba(0,0,0,0.82) 100%),
      linear-gradient(135deg, #f97316 0%, #c2410c 100%);
    display: flex;
    align-items: flex-end;
    padding: 1.35rem 1.15rem;
  }
  .profile-hero-auth {
    background: linear-gradient(135deg, #000000 0%, #986652 100%);
    padding: 1.35rem 1rem 2.5rem;
  }
  .profile-blob {
    position: absolute;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(255,255,255,0.10) 0%, rgba(255,255,255,0) 70%);
    pointer-events: none;
    z-index: 0;
  }
  .profile-hero-guest .blob-1 { width: 170px; height: 170px; top: -45px; right: -45px; }
  .profile-hero-guest .blob-2 { width: 200px; height: 200px; bottom: -60px; left: -30px; background: radial-gradient(circle, rgba(249,115,22,0.18) 0%, rgba(249,115,22,0) 70%); }
  .profile-hero-guest .blob-3 { width: 50px;  height: 50px;  top: 95px; left: 30px; }
  .profile-hero-auth .blob-1 { width: 140px; height: 140px; top: -30px; right: -30px; }
  .profile-hero-auth .blob-2 { width: 130px; height: 130px; bottom: -45px; left: -30px; background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0) 70%); }

  .profile-hero-content { position: relative; z-index: 1; }
  .profile-guest-avatar {
    width: 60px; height: 60px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(255,255,255,0.16);
    border: 2px solid rgba(255,255,255,0.45);
    font-size: 1.6rem;
    margin-bottom: 1rem;
    box-shadow: 0 8px 20px rgba(0,0,0,0.22);
  }
  .profile-hero-title {
    color: #fff;
    font-size: 1.55rem;
    font-weight: 900;
    letter-spacing: -0.7px;
    line-height: 1.1;
    margin: 0 0 0.4rem;
  }
  .profile-hero-subtitle {
    color: rgba(255,255,255,0.82);
    font-size: 0.78rem;
    line-height: 1.4;
    margin: 0;
  }

  /* ===== AUTH HERO ===== */
  .profile-hero-auth-content {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 0.85rem;
  }
  .profile-auth-avatar {
    flex-shrink: 0;
    width: 68px; height: 68px;
    border-radius: 50%;
    overflow: hidden;
    background: linear-gradient(135deg, #f97316, #c0392b);
    border: 2px solid rgba(255,255,255,0.6);
    box-shadow: 0 6px 16px rgba(0,0,0,0.22);
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .profile-auth-avatar img { width: 100%; height: 100%; object-fit: cover; }
  .profile-auth-initials {
    color: #fff;
    font-size: 1.45rem;
    font-weight: 900;
    letter-spacing: -0.4px;
  }
  .profile-auth-info { flex: 1; min-width: 0; color: #fff; }
  .profile-auth-name {
    font-size: 1.15rem;
    font-weight: 900;
    margin: 0 0 0.25rem;
    letter-spacing: -0.3px;
    color: #fff;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .profile-auth-email {
    display: flex;
    align-items: center;
    gap: 0.3rem;
    font-size: 0.72rem;
    color: rgba(255,255,255,0.85);
    margin: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .profile-auth-edit {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    background: rgba(255,255,255,0.18);
    color: #fff;
    border-radius: 12px;
    text-decoration: none;
    font-size: 1rem;
    font-family: inherit;
    border: 0;
    padding: 0;
    cursor: pointer;
    transition: background 0.15s ease, transform 0.15s ease;
  }
  .profile-auth-edit:hover {
    background: rgba(255,255,255,0.32);
    transform: scale(1.05);
  }

  /* ===== STATS ===== */
  .profile-stats-wrap {
    margin: -1.75rem 0.6rem 0.85rem;
    position: relative;
    z-index: 2;
  }
  .profile-stats {
    display: flex;
    align-items: center;
    justify-content: space-evenly;
    padding: 1rem 0.8rem;
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 14px;
    box-shadow: 0 6px 20px -8px rgba(234, 88, 12, 0.2);
  }
  .profile-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.3rem;
    flex: 1;
  }
  .profile-stat-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(249, 115, 22, 0.18), rgba(249, 115, 22, 0.06));
    font-size: 0.85rem;
  }
  .profile-stat-value {
    font-size: 1.15rem;
    font-weight: 900;
    color: #f97316;
    letter-spacing: -0.3px;
    line-height: 1;
  }
  .profile-stat-label {
    font-size: 0.62rem;
    font-weight: 600;
    color: #8a7266;
  }
  .profile-divider {
    display: block;
    width: 1px;
    height: 40px;
    background: linear-gradient(to bottom, rgba(0,0,0,0), rgba(0,0,0,0.10), rgba(0,0,0,0));
  }

  /* ===== FEATURE CARDS ===== */
  .profile-cta-wrap { margin-bottom: 0.4rem; }
  .profile-features-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }
  .profile-feature-card {
    display: block;
    padding: 0.85rem;
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 12px;
    text-decoration: none;
    color: inherit;
    box-shadow: 0 2px 12px -4px rgba(234, 88, 12, 0.1);
    transition: border-color 0.15s ease, transform 0.15s ease, box-shadow 0.15s ease;
  }
  .profile-feature-card:hover {
    border-color: #fcd9b5;
    transform: translateY(-1px);
    box-shadow: 0 5px 16px -4px rgba(234, 88, 12, 0.18);
  }
  .profile-feature-head {
    display: flex;
    align-items: center;
    gap: 0.7rem;
  }
  .profile-feature-icon {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: linear-gradient(135deg,
      color-mix(in srgb, var(--accent) 18%, transparent),
      color-mix(in srgb, var(--accent) 6%, transparent));
    font-size: 0.95rem;
  }
  .profile-feature-text { flex: 1; min-width: 0; }
  .profile-feature-title {
    font-size: 0.8rem;
    font-weight: 800;
    color: #1c1410;
    margin: 0 0 0.15rem;
    letter-spacing: -0.1px;
  }
  .profile-feature-subtitle {
    font-size: 0.68rem;
    color: #8a7266;
    margin: 0;
    line-height: 1.35;
  }
  .profile-chevron {
    color: #b8a598;
    font-size: 1.1rem;
    font-weight: 700;
    flex-shrink: 0;
  }
  .profile-feature-cta {
    padding: 1rem 0.95rem;
    background: linear-gradient(135deg, rgba(249, 115, 22, 0.06), rgba(249, 115, 22, 0.02));
    border-color: rgba(249, 115, 22, 0.22);
  }
  .profile-btn-primary {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    margin-top: 0.75rem;
    padding: 0.65rem 0.9rem;
    font-size: 0.78rem;
    font-weight: 700;
    color: #fff;
    background: linear-gradient(135deg, #fb923c, #ea580c);
    border-radius: 10px;
    text-decoration: none;
    box-shadow: 0 3px 12px -3px rgba(249, 115, 22, 0.35);
    transition: transform 0.15s ease;
  }
  .profile-btn-primary:hover { transform: translateY(-1px); }

  /* ===== LOGOUT ===== */
  .profile-logout-wrap {
    margin: 0.85rem 0 1.75rem;
  }
  .profile-logout {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    width: 100%;
    padding: 0.75rem 0.9rem;
    font-family: inherit;
    font-size: 0.78rem;
    font-weight: 700;
    color: #c0392b;
    background: #fff;
    border: 1.5px solid rgba(192, 57, 43, 0.4);
    border-radius: 10px;
    cursor: pointer;
    transition: background 0.15s ease, border-color 0.15s ease;
  }
  .profile-logout:hover {
    background: rgba(192, 57, 43, 0.06);
    border-color: rgba(192, 57, 43, 0.6);
  }

  .profile-error-note {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    font-size: 0.65rem;
    color: #8a7266;
    text-align: center;
    margin: 0.4rem 0 1.25rem;
    padding: 0 0.85rem;
  }

  /* ===== MODAL ===== */
  .profile-edit-dialog {
    width: min(480px, calc(100vw - 1.5rem));
    max-height: min(90vh, 720px);
    padding: 0;
    border: 0;
    border-radius: 16px;
    color: #1c1410;
    background: #fff;
    box-shadow: 0 24px 70px rgba(0,0,0,0.28);
    overflow-y: auto;
    scrollbar-width: none;
    -ms-overflow-style: none;
    overscroll-behavior: contain;
    font-family: inherit;
    position: fixed;
    inset: 0;
    margin: auto;
  }
  .profile-edit-dialog::-webkit-scrollbar {
    display: none;
  }
  .profile-edit-dialog::backdrop {
    background: rgba(17, 24, 39, 0.58);
    backdrop-filter: blur(3px);
  }
  .profile-edit-dialog[open] {
    animation: dialogIn 0.2s ease;
  }
  @keyframes dialogIn {
    from { opacity: 0; transform: translateY(8px) scale(0.98); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
  }
  .profile-edit-modal-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.15rem 1.25rem;
    border-bottom: 1px solid #f1e4d8;
  }
  .profile-edit-modal-header h2 {
    margin: 0;
    font-size: 1rem;
    font-weight: 800;
  }
  .profile-edit-modal-header p {
    margin: 0.2rem 0 0;
    color: #806d63;
    font-size: 0.7rem;
  }
  .profile-edit-modal-close {
    flex: 0 0 auto;
    width: 30px;
    height: 30px;
    border: 0;
    border-radius: 50%;
    background: #f7f1eb;
    color: #5a4a40;
    font: inherit;
    font-size: 1.1rem;
    cursor: pointer;
    transition: background 0.15s ease;
  }
  .profile-edit-modal-close:hover { background: #fcd9b5; }

  .profile-edit-avatar-form {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.95rem 1.25rem;
    border-bottom: 1px solid #f1e4d8;
    background: rgba(249, 115, 22, 0.03);
  }
  .profile-edit-modal-avatar {
    display: grid;
    place-items: center;
    flex: 0 0 44px;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    overflow: hidden;
    background: linear-gradient(135deg, #f97316, #c0392b);
    color: #fff;
    font-weight: 800;
    font-size: 0.85rem;
  }
  .profile-edit-modal-avatar img { width: 100%; height: 100%; object-fit: cover; }
  .profile-edit-avatar-controls {
    display: grid;
    flex: 1;
    gap: 0.2rem;
    min-width: 0;
  }
  .profile-edit-avatar-controls label {
    font-size: 0.68rem;
    font-weight: 700;
    color: #4a3f38;
  }
  .profile-edit-avatar-controls small {
    color: #806d63;
    font-size: 0.6rem;
  }
  .profile-edit-avatar-controls input {
    min-width: 0;
    font: inherit;
    font-size: 0.65rem;
  }
  .profile-edit-avatar-submit {
    flex: 0 0 auto;
    padding: 0.45rem 0.7rem;
    border: 0;
    border-radius: 8px;
    background: linear-gradient(135deg, #fb923c, #ea580c);
    color: #fff;
    font: inherit;
    font-size: 0.68rem;
    font-weight: 700;
    cursor: pointer;
    transition: transform 0.15s ease;
  }
  .profile-edit-avatar-submit:hover { transform: translateY(-1px); }

  .profile-edit-form {
    display: grid;
    gap: 0.8rem;
    padding: 1.15rem 1.25rem 1.25rem;
  }
  .profile-edit-field {
    display: grid;
    gap: 0.3rem;
  }
  .profile-edit-field label {
    font-size: 0.68rem;
    font-weight: 700;
    color: #4a3f38;
  }
  .profile-edit-field input {
    width: 100%;
    margin: 0;
    padding: 0.65rem 0.75rem;
    border: 1.5px solid #e8d9cc;
    border-radius: 9px;
    background: #fff;
    color: #1c1410;
    font: inherit;
    font-size: 0.78rem;
    outline: none;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
  }
  .profile-edit-field input:focus {
    border-color: #f97316;
    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.10);
  }
  .profile-edit-submit {
    justify-self: stretch;
    margin-top: 0.35rem;
    padding: 0.75rem 1rem;
    border: 0;
    border-radius: 9px;
    background: linear-gradient(135deg, #fb923c, #ea580c);
    color: #fff;
    font: inherit;
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    box-shadow: 0 4px 12px -3px rgba(234, 88, 12, 0.4);
  }
  .profile-edit-submit:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px -3px rgba(234, 88, 12, 0.5);
  }

  /* ===== Responsive ===== */
  @media (max-width: 640px) {
    .profile-hero-title { font-size: 1.3rem; }
    .profile-hero-guest { min-height: 230px; padding: 1rem 0.95rem; }
    .profile-auth-avatar { width: 58px; height: 58px; }
    .profile-auth-name { font-size: 1rem; }
    .profile-auth-initials { font-size: 1.2rem; }
    .profile-stats { padding: 0.85rem 0.65rem; }
    .profile-stat-value { font-size: 1rem; }
  }
  @media (max-width: 520px) {
    .profile-edit-modal-header,
    .profile-edit-avatar-form,
    .profile-edit-form {
      padding-left: 0.9rem;
      padding-right: 0.9rem;
    }
    .profile-edit-avatar-form {
      align-items: flex-start;
      flex-wrap: wrap;
    }
  }
  @media (prefers-reduced-motion: reduce) {
    *, .profile-feature-card, .profile-auth-edit, .profile-btn-primary,
    .profile-logout, .profile-edit-dialog, .profile-edit-submit {
      transition: none !important;
      animation: none !important;
    }
  }
</style>

<?php if ($flash): ?>
  <div class="flash flash-<?= client_h($flash['type']) ?>" role="alert">
    <?= client_h($flash['text']) ?>
  </div>
<?php endif; ?>

<?php if (!$profile): ?>
  <!-- ===== Vue non authentifiée ===== -->
  <section class="profile-hero profile-hero-guest">
    <span class="profile-blob blob-1" aria-hidden="true"></span>
    <span class="profile-blob blob-2" aria-hidden="true"></span>
    <span class="profile-blob blob-3" aria-hidden="true"></span>

    <div class="profile-hero-content">
      <div class="profile-guest-avatar" aria-hidden="true">👤</div>
      <h1 class="profile-hero-title">Mon espace voyageur</h1>
      <p class="profile-hero-subtitle">Connectez-vous pour accéder à votre profil</p>
    </div>
  </section>

  <div class="profile-cta-wrap">
    <article class="profile-feature-card profile-feature-cta">
      <div class="profile-feature-head">
        <div class="profile-feature-icon" style="--accent:#f97316;">
          <span aria-hidden="true">🧭</span>
        </div>
        <div class="profile-feature-text">
          <h2 class="profile-feature-title">Prêt à explorer ?</h2>
          <p class="profile-feature-subtitle">Retrouvez vos favoris, votre passeport et votre progression.</p>
        </div>
      </div>
      <a class="profile-btn-primary" href="?page=login">
        <span aria-hidden="true">🔑</span> Connexion / Inscription
      </a>
    </article>
  </div>

  <div class="profile-features-list">
    <a class="profile-feature-card" href="?page=login">
      <div class="profile-feature-head">
        <div class="profile-feature-icon" style="--accent:#c0392b;"><span aria-hidden="true">❤️</span></div>
        <div class="profile-feature-text">
          <h2 class="profile-feature-title">Favoris</h2>
          <p class="profile-feature-subtitle">Connectez-vous pour consulter vos lieux enregistrés</p>
        </div>
        <span class="profile-chevron" aria-hidden="true">›</span>
      </div>
    </a>
    <a class="profile-feature-card" href="?page=login">
      <div class="profile-feature-head">
        <div class="profile-feature-icon" style="--accent:#f97316;"><span aria-hidden="true">🛂</span></div>
        <div class="profile-feature-text">
          <h2 class="profile-feature-title">Passeport numérique</h2>
          <p class="profile-feature-subtitle">Visites, progression et récompenses</p>
        </div>
        <span class="profile-chevron" aria-hidden="true">›</span>
      </div>
    </a>
    <a class="profile-feature-card" href="?page=settings">
      <div class="profile-feature-head">
        <div class="profile-feature-icon" style="--accent:#546e7a;"><span aria-hidden="true">⚙️</span></div>
        <div class="profile-feature-text">
          <h2 class="profile-feature-title">Paramètres</h2>
          <p class="profile-feature-subtitle">Apparence et accessibilité</p>
        </div>
        <span class="profile-chevron" aria-hidden="true">›</span>
      </div>
    </a>
  </div>

  <?php if ($errorMessage): ?>
    <p class="profile-error-note" role="alert">
      <span aria-hidden="true">ℹ️</span> <?= client_h($errorMessage) ?>
    </p>
  <?php endif; ?>

<?php else: ?>
  <!-- ===== Vue authentifiée ===== -->
  <?php
    $fullName = trim($prenom . ' ' . $nom) ?: 'Mon profil';
    $email    = (string)($profile['email'] ?? '');
    $visites  = (int)($profile['visites'] ?? 0);
    $favoris  = (int)($profile['favoris'] ?? 0);
    $points   = (int)($profile['points'] ?? 0);
  ?>

  <section class="profile-hero profile-hero-auth">
    <span class="profile-blob blob-1" aria-hidden="true"></span>
    <span class="profile-blob blob-2" aria-hidden="true"></span>

    <div class="profile-hero-auth-content">
      <div class="profile-auth-avatar">
        <?php if ($avatarUrl !== ''): ?>
          <img src="<?= client_h($avatarUrl) ?>" alt="Photo de profil">
        <?php else: ?>
          <span class="profile-auth-initials"><?= client_h($ini) ?></span>
        <?php endif; ?>
      </div>

      <div class="profile-auth-info">
        <h1 class="profile-auth-name"><?= client_h($fullName) ?></h1>
        <p class="profile-auth-email">
          <span aria-hidden="true">✉️</span> <?= client_h($email) ?>
        </p>
      </div>

      <button class="profile-auth-edit" type="button" title="Modifier le profil"
              aria-label="Modifier le profil" aria-haspopup="dialog"
              aria-controls="profileEditDialog" data-profile-edit-open>
        ✏️
      </button>
    </div>
  </section>

  <div class="profile-stats-wrap">
    <div class="profile-stats">
      <div class="profile-stat">
        <div class="profile-stat-icon" aria-hidden="true">📍</div>
        <span class="profile-stat-value"><?= $visites ?></span>
        <span class="profile-stat-label">Visites</span>
      </div>
      <span class="profile-divider" aria-hidden="true"></span>
      <div class="profile-stat">
        <div class="profile-stat-icon" aria-hidden="true">❤️</div>
        <span class="profile-stat-value"><?= $favoris ?></span>
        <span class="profile-stat-label">Favoris</span>
      </div>
      <span class="profile-divider" aria-hidden="true"></span>
      <div class="profile-stat">
        <div class="profile-stat-icon" aria-hidden="true">⭐</div>
        <span class="profile-stat-value"><?= $points ?></span>
        <span class="profile-stat-label">Points</span>
      </div>
    </div>
  </div>

  <div class="profile-features-list">
    <a class="profile-feature-card" href="?page=passport">
      <div class="profile-feature-head">
        <div class="profile-feature-icon" style="--accent:#f97316;"><span aria-hidden="true">🛂</span></div>
        <div class="profile-feature-text">
          <h2 class="profile-feature-title">Passeport numérique</h2>
          <p class="profile-feature-subtitle">Visites, progression et récompenses</p>
        </div>
        <span class="profile-chevron" aria-hidden="true">›</span>
      </div>
    </a>
    <a class="profile-feature-card" href="?page=favorites">
      <div class="profile-feature-head">
        <div class="profile-feature-icon" style="--accent:#c0392b;"><span aria-hidden="true">❤️</span></div>
        <div class="profile-feature-text">
          <h2 class="profile-feature-title">Mes favoris</h2>
          <p class="profile-feature-subtitle">Retrouvez vos destinations enregistrées</p>
        </div>
        <span class="profile-chevron" aria-hidden="true">›</span>
      </div>
    </a>
    <a class="profile-feature-card" href="?page=notifications">
      <div class="profile-feature-head">
        <div class="profile-feature-icon" style="--accent:#2196f3;"><span aria-hidden="true">🔔</span></div>
        <div class="profile-feature-text">
          <h2 class="profile-feature-title">Notifications</h2>
          <p class="profile-feature-subtitle">Consultez et marquez les notifications comme lues</p>
        </div>
        <span class="profile-chevron" aria-hidden="true">›</span>
      </div>
    </a>
    <a class="profile-feature-card" href="?page=reservations">
      <div class="profile-feature-head">
        <div class="profile-feature-icon" style="--accent:#00897b;"><span aria-hidden="true">🧾</span></div>
        <div class="profile-feature-text">
          <h2 class="profile-feature-title">Réservations</h2>
          <p class="profile-feature-subtitle">Suivez les demandes envoyées</p>
        </div>
        <span class="profile-chevron" aria-hidden="true">›</span>
      </div>
    </a>
    <a class="profile-feature-card" href="?page=settings">
      <div class="profile-feature-head">
        <div class="profile-feature-icon" style="--accent:#546e7a;"><span aria-hidden="true">⚙️</span></div>
        <div class="profile-feature-text">
          <h2 class="profile-feature-title">Paramètres &amp; accessibilité</h2>
          <p class="profile-feature-subtitle">Thème et préférences</p>
        </div>
        <span class="profile-chevron" aria-hidden="true">›</span>
      </div>
    </a>
  </div>

  <form method="post" class="profile-logout-wrap" action="?page=logout"
        data-confirm="Voulez-vous vous déconnecter ?">
    <?= client_csrf_field() ?>
    <input type="hidden" name="action" value="logout">
    <button type="submit" class="profile-logout">
      <span aria-hidden="true">🚪</span> Se déconnecter
    </button>
  </form>

  <!-- ===== MODAL D'ÉDITION ===== -->
  <dialog class="profile-edit-dialog"
          id="profileEditDialog"
          data-profile-edit-dialog
          <?= $openModal ? 'open' : '' ?>
          aria-labelledby="profileEditTitle">

    <header class="profile-edit-modal-header">
      <div>
        <h2 id="profileEditTitle">Modifier mon profil</h2>
        <p>Actualisez vos informations personnelles.</p>
      </div>
      <button class="profile-edit-modal-close" type="button" aria-label="Fermer"
              data-profile-edit-close>×</button>
    </header>

    <form class="profile-edit-avatar-form" method="post" action="?page=profile"
          enctype="multipart/form-data">
      <?= client_csrf_field() ?>
      <input type="hidden" name="action" value="upload_avatar">
      <span class="profile-edit-modal-avatar">
        <?php if ($avatarUrl !== ''): ?>
          <img src="<?= client_h($avatarUrl) ?>" alt="">
        <?php else: ?>
          <?= client_h($ini) ?>
        <?php endif; ?>
      </span>
      <div class="profile-edit-avatar-controls">
        <label for="profileAvatarInput">Photo de profil</label>
        <input id="profileAvatarInput" type="file" name="avatar" accept="image/*" required>
        <small>Image de 5 Mo maximum.</small>
      </div>
      <button class="profile-edit-avatar-submit" type="submit">Changer</button>
    </form>

    <form class="profile-edit-form" method="post" action="?page=profile">
      <?= client_csrf_field() ?>
      <input type="hidden" name="action" value="update_profile">
      <div class="profile-edit-field">
        <label for="profileEditNom">Nom</label>
        <input id="profileEditNom" name="nom" type="text" maxlength="100"
               value="<?= client_h($nom) ?>">
      </div>
      <div class="profile-edit-field">
        <label for="profileEditPrenom">Prénom</label>
        <input id="profileEditPrenom" name="prenom" type="text" maxlength="100"
               value="<?= client_h($prenom) ?>">
      </div>
      <div class="profile-edit-field">
        <label for="profileEditTelephone">Téléphone</label>
        <input id="profileEditTelephone" name="telephone" type="tel" maxlength="30"
               value="<?= client_h($telephone) ?>"
               placeholder="+225 XX XX XX XX XX">
      </div>
      <button class="profile-edit-submit" type="submit">Enregistrer les modifications</button>
    </form>
  </dialog>
<?php endif; ?>

<script>
(function () {
  const dialog = document.getElementById('profileEditDialog');
  if (!dialog) return;

  const openBtns = document.querySelectorAll('[data-profile-edit-open]');
  const closeBtn = dialog.querySelector('[data-profile-edit-close]');

  let savedScrollY = 0;
  let isLocked = false;

  // ===== BLOQUE MOLETTE / TOUCH / CLAVIER EN ARRIÈRE =====
  function preventScroll(e) {
    if (!isLocked) return;
    if (dialog.contains(e.target)) return;
    e.preventDefault();
    e.stopPropagation();
    return false;
  }

  window.addEventListener('wheel', preventScroll, { passive: false, capture: true });
  window.addEventListener('mousewheel', preventScroll, { passive: false, capture: true });
  window.addEventListener('touchmove', preventScroll, { passive: false, capture: true });

  window.addEventListener('keydown', function (e) {
    if (!isLocked) return;
    if (dialog.contains(e.target)) return;
    const keys = ['ArrowUp', 'ArrowDown', 'PageUp', 'PageDown', 'Home', 'End', ' '];
    if (keys.includes(e.key)) {
      e.preventDefault();
      e.stopPropagation();
    }
  }, { capture: true });

  // ===== VERROUILLER =====
  function lockScroll() {
    if (isLocked) return;
    isLocked = true;
    savedScrollY = window.scrollY || window.pageYOffset || 0;
    document.body.style.setProperty('--scroll-y', savedScrollY + 'px');
    document.body.classList.add('modal-open');
    document.documentElement.classList.add('modal-open');
  }

  // ===== DÉVERROUILLER =====
  function unlockScroll() {
    if (!isLocked) return;
    isLocked = false;
    document.body.classList.remove('modal-open');
    document.documentElement.classList.remove('modal-open');
    document.body.style.removeProperty('--scroll-y');
    window.scrollTo(0, savedScrollY);
  }

  // ===== OUVRIR =====
  openBtns.forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      lockScroll();
      if (typeof dialog.showModal === 'function') {
        dialog.showModal();
      } else {
        dialog.setAttribute('open', '');
      }
    });
  });

  // ===== FERMER via × =====
  if (closeBtn) {
    closeBtn.addEventListener('click', function () {
      if (typeof dialog.close === 'function') dialog.close();
      else dialog.removeAttribute('open');
    });
  }

  // ===== FERMER via backdrop =====
  dialog.addEventListener('click', function (e) {
    if (e.target === dialog) {
      if (typeof dialog.close === 'function') dialog.close();
      else dialog.removeAttribute('open');
    }
  });

  // ===== DÉVERROUILLER À LA FERMETURE =====
  dialog.addEventListener('close', unlockScroll);
  dialog.addEventListener('cancel', unlockScroll);

  // Fallback si pas de <dialog> natif
  if (typeof dialog.showModal !== 'function') {
    const observer = new MutationObserver(function () {
      if (!dialog.hasAttribute('open')) unlockScroll();
    });
    observer.observe(dialog, { attributes: true, attributeFilter: ['open'] });
  }

  // ===== RÉOUVERTURE AUTO APRÈS POST =====
  <?php if ($openModal): ?>
  requestAnimationFrame(function () {
    lockScroll();
    if (typeof dialog.showModal === 'function' && !dialog.open) {
      dialog.showModal();
    }
  });
  <?php endif; ?>
})();
</script>