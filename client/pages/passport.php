<?php
if (!$user) {
    header('Location: ?page=login');
    exit;
}

// ===== Chargement des 3 ressources =====
try {
    $passport = ClientApi::data($api->request('GET', 'api/passport'));
    $badges   = ClientApi::data($api->request('GET', 'api/passport/badges'));
    $visits   = ClientApi::data($api->request('GET', 'api/passport/visits'));
    $passport = is_array($passport) ? $passport : [];
    $badges   = is_array($badges)   ? $badges   : [];
    $visits   = is_array($visits)   ? $visits   : [];
} catch (Throwable $e) {
    echo '<section class="panel empty-panel"><div class="empty">'
       . '<div class="empty-icon">❌</div>'
       . '<h3>Erreur de chargement</h3>'
       . '<p>' . client_h(readableError($e)) . '</p>'
       . '<a class="btn-go" href="?page=passport">Réessayer</a>'
       . '</div></section>';
    return;
}

// ===== Extraction des stats =====
$numero    = (string)($passport['numero'] ?? '');
$niveau    = (int)($passport['niveau'] ?? 1);
$points    = (int)($passport['points_total'] ?? 0);
$visitesNb = (int)($passport['visites'] ?? count($visits));
$badgesNb  = (int)($passport['badges'] ?? count(array_filter($badges, static fn($b) =>
    !empty($b['obtenu_at']) || in_array((string)($b['obtenu'] ?? ''), ['1', 'true'], true)
)));
?>

<!-- ===== Header avec bouton retour ===== -->
<header class="page-header">
  <a class="back-link" href="?page=profile" aria-label="Retour au profil">←</a>
  <h1 class="page-title">Passeport numérique</h1>
</header>

<!-- ===== Hero gradient (équivalent SliverAppBar + FlexibleSpaceBar) ===== -->
<section class="passport-hero">
  <span class="passport-blob blob-1" aria-hidden="true"></span>
  <span class="passport-blob blob-2" aria-hidden="true"></span>

  <div class="passport-hero-content">
    <?php if ($numero !== ''): ?>
      <span class="passport-numero"><?= client_h($numero) ?></span>
    <?php endif; ?>
    <h2 class="passport-level">Niveau <?= $niveau ?></h2>
    <p class="passport-points"><?= number_format($points, 0, ',', ' ') ?> points</p>

    <div class="passport-stats">
      <div class="passport-stat">
        <span class="passport-stat-value"><?= $visitesNb ?></span>
        <span class="passport-stat-label">Visites</span>
      </div>
      <div class="passport-stat">
        <span class="passport-stat-value"><?= $badgesNb ?></span>
        <span class="passport-stat-label">Badges</span>
      </div>
    </div>
  </div>
</section>

<!-- ===== Badges ===== -->
<?php if ($badges): ?>
  <section class="passport-section">
    <header class="section-header">
      <h2 class="section-title">Badges</h2>
      <span class="section-count"><?= count($badges) ?></span>
    </header>

    <div class="badges-grid">
      <?php foreach ($badges as $badge): ?>
        <?php
          $obtained = !empty($badge['obtenu_at'])
            || in_array((string)($badge['obtenu'] ?? ''), ['1', 'true'], true);
          $nom = (string)($badge['nom'] ?? '');
          $pts = (int)($badge['points'] ?? 0);
        ?>
        <article class="badge-card <?= $obtained ? 'is-obtained' : 'is-locked' ?>">
          <span class="badge-icon" aria-hidden="true"><?= $obtained ? '🏅' : '🔒' ?></span>
          <div class="badge-text">
            <span class="badge-name"><?= client_h($nom) ?></span>
            <span class="badge-points"><?= $pts ?> pts</span>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>

<!-- ===== Historique des visites ===== -->
<section class="passport-section">
  <header class="section-header">
    <h2 class="section-title">Historique des visites</h2>
    <?php if ($visits): ?>
      <span class="section-count"><?= count($visits) ?></span>
    <?php endif; ?>
  </header>

  <?php if (!$visits): ?>
    <div class="empty">
      <div class="empty-icon">◷</div>
      <h3>Aucune visite enregistrée</h3>
      <p>Ouvrez une destination et sélectionnez « J'ai visité ce lieu ».</p>
    </div>
  <?php else: ?>
    <div class="visits-list">
      <?php foreach ($visits as $visit): ?>
        <?php $destId = (int)($visit['destination_id'] ?? 0); ?>
        <a class="visit-row" href="?page=destination&amp;id=<?= $destId ?>">
          <span class="visit-icon" aria-hidden="true">📍</span>
          <div class="visit-row-content">
            <div class="visit-row-title"><?= client_h($visit['nom'] ?? '') ?></div>
            <div class="visit-row-meta"><?= client_h($visit['localite'] ?? '') ?></div>
          </div>
          <div class="visit-row-date"><?= client_h($visit['visite_le'] ?? '') ?></div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<style>
  *, *::before, *::after { box-sizing: border-box; }

  /* ===== Header ===== */
  .page-header {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    margin-bottom: 1rem;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  .back-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px; height: 38px;
    border-radius: 50%;
    background: #f7f1eb;
    color: #5a4a40;
    text-decoration: none;
    font-size: 1rem;
    transition: all 0.2s ease;
    flex-shrink: 0;
  }

  .back-link:hover { background: #fff8f3; color: #ea580c; }

  .page-title {
    font-size: 1.4rem;
    font-weight: 900;
    color: #1c1410;
    margin: 0;
    letter-spacing: -0.4px;
  }

  /* ===== Hero gradient ===== */
  .passport-hero {
    position: relative;
    margin-bottom: 1.5rem;
    padding: 2rem 1.5rem 1.75rem;
    border-radius: 20px;
    background: linear-gradient(135deg, #f97316 0%, #ea580c 60%, #c2410c 100%);
    color: #fff;
    overflow: hidden;
    isolation: isolate;
    box-shadow: 0 12px 32px -10px rgba(234, 88, 12, 0.4);
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  .passport-blob {
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.07);
    pointer-events: none;
  }

  .blob-1 { width: 180px; height: 180px; top: -40px; right: -40px; }
  .blob-2 { width: 140px; height: 140px; bottom: -30px; left: -20px; background: rgba(255, 255, 255, 0.05); }

  .passport-hero-content { position: relative; z-index: 1; }

  .passport-numero {
    display: block;
    font-size: 0.7rem;
    letter-spacing: 2px;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.65);
    margin-bottom: 0.4rem;
  }

  .passport-level {
    font-size: 1.85rem;
    font-weight: 900;
    margin: 0 0 0.25rem;
    letter-spacing: -0.5px;
    color: #fff;
  }

  .passport-points {
    font-size: 1rem;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.85);
    margin: 0 0 1rem;
  }

  .passport-stats {
    display: flex;
    gap: 2rem;
    margin-top: 0.5rem;
  }

  .passport-stat { display: flex; flex-direction: column; }

  .passport-stat-value {
    font-size: 1.35rem;
    font-weight: 900;
    color: #fff;
    line-height: 1.1;
  }

  .passport-stat-label {
    font-size: 0.7rem;
    color: rgba(255, 255, 255, 0.7);
    margin-top: 0.15rem;
  }

  /* ===== Sections ===== */
  .passport-section {
    margin-bottom: 1.5rem;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  .section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding-bottom: 0.55rem;
    margin-bottom: 0.75rem;
    border-bottom: 1px solid #f3ebe4;
  }

  .section-title {
    font-size: 0.95rem;
    font-weight: 800;
    color: #1c1410;
    margin: 0;
    letter-spacing: -0.2px;
  }

  .section-count {
    font-size: 0.68rem;
    font-weight: 700;
    color: #ea580c;
    background: rgba(249, 115, 22, 0.12);
    padding: 0.15rem 0.55rem;
    border-radius: 999px;
  }

  /* ===== Badges ===== */
  .badges-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 0.65rem;
  }

  .badge-card {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.75rem 0.85rem;
    border-radius: 12px;
    border: 1px solid;
    transition: all 0.2s ease;
    min-width: 0;
  }

  .badge-card.is-obtained {
    background: rgba(212, 175, 55, 0.08);
    border-color: rgba(212, 175, 55, 0.4);
    box-shadow: 0 3px 12px -4px rgba(212, 175, 55, 0.25);
  }

  .badge-card.is-locked {
    background: rgba(241, 228, 216, 0.5);
    border-color: #f1e4d8;
  }

  .badge-icon {
    flex-shrink: 0;
    font-size: 1.3rem;
    line-height: 1;
  }

  .badge-text {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
  }

  .badge-name {
    font-size: 0.8rem;
    font-weight: 700;
    color: #1c1410;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .badge-card.is-locked .badge-name {
    color: #8a7266;
    font-weight: 600;
  }

  .badge-points {
    font-size: 0.7rem;
    font-weight: 600;
    color: #d4af37;
  }

  .badge-card.is-locked .badge-points { color: #b8a598; }

  /* ===== Visites ===== */
  .visits-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .visit-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.7rem 0.85rem;
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 12px;
    text-decoration: none;
    color: inherit;
    box-shadow: 0 3px 14px -6px rgba(234, 88, 12, 0.12);
    transition: all 0.2s ease;
    min-width: 0;
  }

  .visit-row:hover {
    border-color: #fcd9b5;
    transform: translateX(2px);
    box-shadow: 0 6px 18px -6px rgba(234, 88, 12, 0.22);
  }

  .visit-icon {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px; height: 40px;
    background: rgba(249, 115, 22, 0.1);
    color: #ea580c;
    border-radius: 10px;
    font-size: 1.05rem;
  }

  .visit-row-content {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
  }

  .visit-row-title {
    font-size: 0.85rem;
    font-weight: 700;
    color: #1c1410;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .visit-row-meta {
    font-size: 0.7rem;
    color: #8a7266;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .visit-row-date {
    font-size: 0.7rem;
    font-weight: 600;
    color: #ea580c;
    white-space: nowrap;
    flex-shrink: 0;
  }

  /* ===== États vides ===== */
  .panel, .empty-panel {
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 14px;
    padding: 1.5rem 1rem;
    box-shadow: 0 4px 18px -6px rgba(234, 88, 12, 0.15);
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  .empty { text-align: center; padding: 1.1rem 0.75rem; }

  .empty-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    margin-bottom: 0.65rem;
    background: rgba(249, 115, 22, 0.10);
    color: #ea580c;
    border-radius: 50%;
    font-size: 1.25rem;
  }

  .empty h3 { font-size: 0.9rem; font-weight: 700; color: #1c1410; margin: 0 0 0.3rem; }
  .empty p  { font-size: 0.78rem; color: #8a7266; margin: 0; line-height: 1.4; }

  .btn-go {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.6rem 1.2rem;
    font-size: 0.8rem;
    font-weight: 700;
    color: #fff;
    background: linear-gradient(135deg, #fb923c, #ea580c);
    border-radius: 10px;
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(249, 115, 22, 0.3);
    transition: all 0.2s ease;
    margin-top: 0.5rem;
  }

  .btn-go:hover { transform: translateY(-1px); }

  /* ===== Responsive ===== */
  @media (max-width: 640px) {
    .page-title { font-size: 1.2rem; }
    .passport-hero { padding: 1.5rem 1.15rem 1.25rem; }
    .passport-level { font-size: 1.55rem; }
    .passport-stats { gap: 1.5rem; }
    .badges-grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); }
  }

  @media (max-width: 380px) {
    .passport-stats { gap: 1rem; }
    .visit-row { padding: 0.6rem 0.75rem; }
    .visit-row-title { font-size: 0.8rem; }
  }

  @media (prefers-reduced-motion: reduce) {
    *, .visit-row, .badge-card, .back-link { transition: none !important; }
  }
</style>