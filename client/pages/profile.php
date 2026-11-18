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
?>

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
        <div class="profile-feature-icon" style="--accent:#c0392b;">
          <span aria-hidden="true">❤️</span>
        </div>
        <div class="profile-feature-text">
          <h2 class="profile-feature-title">Favoris</h2>
          <p class="profile-feature-subtitle">Connectez-vous pour consulter vos lieux enregistrés</p>
        </div>
        <span class="profile-chevron" aria-hidden="true">›</span>
      </div>
    </a>
    <a class="profile-feature-card" href="?page=login">
      <div class="profile-feature-head">
        <div class="profile-feature-icon" style="--accent:#f97316;">
          <span aria-hidden="true">🛂</span>
        </div>
        <div class="profile-feature-text">
          <h2 class="profile-feature-title">Passeport numérique</h2>
          <p class="profile-feature-subtitle">Visites, progression et récompenses</p>
        </div>
        <span class="profile-chevron" aria-hidden="true">›</span>
      </div>
    </a>
    <a class="profile-feature-card" href="?page=settings">
      <div class="profile-feature-head">
        <div class="profile-feature-icon" style="--accent:#546e7a;">
          <span aria-hidden="true">⚙️</span>
        </div>
        <div class="profile-feature-text">
          <h2 class="profile-feature-title">Paramètres</h2>
          <p class="profile-feature-subtitle">Apparence et accessibilité</p>
        </div>
        <span class="profile-chevron" aria-hidden="true">›</span>
      </div>
    </a>
  </div>

  <?php if ($errorMessage): ?>
    <p class="profile-error-note">
      <span aria-hidden="true">ℹ️</span> <?= client_h($errorMessage) ?>
    </p>
  <?php endif; ?>

<?php else: ?>
  <!-- ===== Vue authentifiée ===== -->
  <?php
    $prenom   = trim((string)($profile['prenom'] ?? ''));
    $nom      = trim((string)($profile['nom'] ?? ''));
    $fullName = trim($prenom . ' ' . $nom) ?: 'Mon profil';
    $email    = (string)($profile['email'] ?? '');
    $visites  = (int)($profile['visites'] ?? 0);
    $favoris  = (int)($profile['favoris'] ?? 0);
    $points   = (int)($profile['points'] ?? 0);
    $ini      = $initials($profile);
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

      <a class="profile-auth-edit" href="?page=profile_edit" title="Modifier le profil" aria-label="Modifier le profil">
        ✏️
      </a>
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
        onsubmit="return confirm('Voulez-vous vous déconnecter ?');">
    <?= client_csrf_field() ?>
    <input type="hidden" name="action" value="logout">
    <button type="submit" class="profile-logout">
      <span aria-hidden="true">🚪</span> Se déconnecter
    </button>
  </form>
<?php endif; ?>

<style>
  *, *::before, *::after { box-sizing: border-box; }

  /* ===== HERO ===== */
  .profile-hero {
    position: relative;
    border-radius: 20px;
    overflow: hidden;
    margin-bottom: 1.25rem;
    isolation: isolate;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  .profile-hero-guest {
    min-height: 340px;
    background:
      linear-gradient(180deg, rgba(0,0,0,0.10) 0%, rgba(0,0,0,0.55) 55%, rgba(0,0,0,0.82) 100%),
      linear-gradient(135deg, #f97316 0%, #c2410c 100%);
    display: flex;
    align-items: flex-end;
    padding: 1.75rem 1.5rem;
  }

  .profile-hero-auth {
    background: linear-gradient(135deg, #f97316 0%, #ea580c 60%, #c2410c 100%);
    padding: 1.75rem 1.25rem 3rem;
  }

  .profile-blob {
    position: absolute;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(255,255,255,0.10) 0%, rgba(255,255,255,0) 70%);
    pointer-events: none;
    z-index: 0;
  }

  .profile-hero-guest .blob-1 { width: 220px; height: 220px; top: -60px; right: -60px; }
  .profile-hero-guest .blob-2 { width: 260px; height: 260px; bottom: -80px; left: -40px; background: radial-gradient(circle, rgba(249,115,22,0.18) 0%, rgba(249,115,22,0) 70%); }
  .profile-hero-guest .blob-3 { width: 60px;  height: 60px;  top: 120px; left: 40px; }

  .profile-hero-auth .blob-1 { width: 180px; height: 180px; top: -40px; right: -40px; }
  .profile-hero-auth .blob-2 { width: 160px; height: 160px; bottom: -60px; left: -40px; background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0) 70%); }

  .profile-hero-content { position: relative; z-index: 1; }

  .profile-guest-avatar {
    width: 72px; height: 72px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(255,255,255,0.16);
    border: 2px solid rgba(255,255,255,0.45);
    font-size: 2rem;
    margin-bottom: 1.25rem;
    box-shadow: 0 10px 24px rgba(0,0,0,0.25);
  }

  .profile-hero-title {
    color: #fff;
    font-size: 1.85rem;
    font-weight: 900;
    letter-spacing: -0.8px;
    line-height: 1.1;
    margin: 0 0 0.5rem;
  }

  .profile-hero-subtitle {
    color: rgba(255,255,255,0.82);
    font-size: 0.9rem;
    line-height: 1.4;
    margin: 0;
  }

  /* ===== AUTH HERO ===== */
  .profile-hero-auth-content {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .profile-auth-avatar {
    flex-shrink: 0;
    width: 84px; height: 84px;
    border-radius: 50%;
    overflow: hidden;
    background: linear-gradient(135deg, #f97316, #c0392b);
    border: 2.5px solid rgba(255,255,255,0.6);
    box-shadow: 0 8px 20px rgba(0,0,0,0.22);
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .profile-auth-avatar img { width: 100%; height: 100%; object-fit: cover; }

  .profile-auth-initials {
    color: #fff;
    font-size: 1.75rem;
    font-weight: 900;
    letter-spacing: -0.5px;
  }

  .profile-auth-info {
    flex: 1;
    min-width: 0;
    color: #fff;
  }

  .profile-auth-name {
    font-size: 1.35rem;
    font-weight: 900;
    margin: 0 0 0.3rem;
    letter-spacing: -0.4px;
    color: #fff;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .profile-auth-email {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.82rem;
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
    width: 44px;
    height: 44px;
    background: rgba(255,255,255,0.18);
    color: #fff;
    border-radius: 14px;
    text-decoration: none;
    font-size: 1.1rem;
    transition: all 0.2s ease;
  }

  .profile-auth-edit:hover {
    background: rgba(255,255,255,0.32);
    transform: scale(1.05);
  }

  /* ===== STATS ===== */
  .profile-stats-wrap {
    margin: -2rem 0.75rem 1rem;
    position: relative;
    z-index: 2;
  }

  .profile-stats {
    display: flex;
    align-items: center;
    justify-content: space-evenly;
    padding: 1.25rem 1rem;
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 16px;
    box-shadow: 0 8px 24px -8px rgba(234,88,12,0.22);
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  .profile-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.35rem;
    flex: 1;
  }

  .profile-stat-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px; height: 38px;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(249,115,22,0.18), rgba(249,115,22,0.06));
    font-size: 1rem;
  }

  .profile-stat-value {
    font-size: 1.35rem;
    font-weight: 900;
    color: #f97316;
    letter-spacing: -0.4px;
    line-height: 1;
  }

  .profile-stat-label {
    font-size: 0.7rem;
    font-weight: 600;
    color: #8a7266;
  }

  .profile-divider {
    display: block;
    width: 1px;
    height: 48px;
    background: linear-gradient(to bottom, rgba(0,0,0,0), rgba(0,0,0,0.12), rgba(0,0,0,0));
  }

  /* ===== FEATURE CARDS ===== */
  .profile-cta-wrap {
    margin-bottom: 0.5rem;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  .profile-features-list {
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  .profile-feature-card {
    display: block;
    padding: 1rem;
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 14px;
    text-decoration: none;
    color: inherit;
    box-shadow: 0 4px 18px -6px rgba(234,88,12,0.15);
    transition: all 0.2s ease;
  }

  .profile-feature-card:hover {
    border-color: #fcd9b5;
    transform: translateY(-1px);
    box-shadow: 0 8px 22px -6px rgba(234,88,12,0.22);
  }

  .profile-feature-head {
    display: flex;
    align-items: center;
    gap: 0.85rem;
  }

  .profile-feature-icon {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 46px;
    height: 46px;
    border-radius: 14px;
    background: linear-gradient(135deg,
      color-mix(in srgb, var(--accent) 18%, transparent),
      color-mix(in srgb, var(--accent) 6%, transparent));
    font-size: 1.15rem;
  }

  .profile-feature-text {
    flex: 1;
    min-width: 0;
  }

  .profile-feature-title {
    font-size: 0.9rem;
    font-weight: 800;
    color: #1c1410;
    margin: 0 0 0.2rem;
    letter-spacing: -0.1px;
  }

  .profile-feature-subtitle {
    font-size: 0.75rem;
    color: #8a7266;
    margin: 0;
    line-height: 1.35;
  }

  .profile-chevron {
    color: #b8a598;
    font-size: 1.3rem;
    font-weight: 700;
    flex-shrink: 0;
  }

  .profile-feature-cta {
    padding: 1.15rem 1.1rem;
    background: linear-gradient(135deg, rgba(249,115,22,0.06), rgba(249,115,22,0.02));
    border-color: rgba(249,115,22,0.22);
  }

  .profile-btn-primary {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    margin-top: 0.85rem;
    padding: 0.75rem 1rem;
    font-size: 0.85rem;
    font-weight: 700;
    color: #fff;
    background: linear-gradient(135deg, #fb923c, #ea580c);
    border-radius: 12px;
    text-decoration: none;
    box-shadow: 0 4px 14px rgba(249,115,22,0.35);
    transition: all 0.2s ease;
  }

  .profile-btn-primary:hover { transform: translateY(-1px); }

  /* ===== LOGOUT ===== */
  .profile-logout-wrap {
    margin: 1rem 0 2rem;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  .profile-logout {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    width: 100%;
    padding: 0.95rem 1rem;
    font-family: inherit;
    font-size: 0.88rem;
    font-weight: 700;
    color: #c0392b;
    background: #fff;
    border: 1.5px solid rgba(192,57,43,0.4);
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .profile-logout:hover {
    background: rgba(192,57,43,0.06);
    border-color: rgba(192,57,43,0.6);
  }

  .profile-error-note {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    font-size: 0.72rem;
    color: #8a7266;
    text-align: center;
    margin: 0.5rem 0 1.5rem;
    padding: 0 1rem;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  /* ===== RESPONSIVE ===== */
  @media (max-width: 640px) {
    .profile-hero-title { font-size: 1.55rem; }
    .profile-hero-guest { min-height: 280px; padding: 1.25rem 1.15rem; }
    .profile-auth-avatar { width: 72px; height: 72px; }
    .profile-auth-name { font-size: 1.15rem; }
    .profile-stats { padding: 1rem 0.75rem; }
    .profile-stat-value { font-size: 1.15rem; }
  }

  @media (prefers-reduced-motion: reduce) {
    *, .profile-feature-card, .profile-auth-edit, .profile-btn-primary, .profile-logout { transition: none !important; }
  }
</style>