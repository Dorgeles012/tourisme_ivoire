<?php
// ===== Récupération des données =====
$payload = ClientApi::data($api->request('GET', 'api/destinations?page=1&per_page=6'));
$featured = is_array($payload['items'] ?? null) ? $payload['items'] : [];

// ===== Features =====
$features = [
    ['icon' => '📰', 'title' => 'Actualités', 'subtitle' => 'Les nouveautés touristiques', 'color' => '#2E86AB', 'href' => '?page=actualites'],
    ['icon' => '🏷️', 'title' => 'Promotions', 'subtitle' => 'Offres du moment', 'color' => '#16A085', 'href' => '?page=promotions'],
    ['icon' => '🏨', 'title' => 'Hébergements', 'subtitle' => 'Où séjourner', 'color' => '#27AE60', 'href' => '?page=hebergements'],
    ['icon' => '🥾', 'title' => 'Activités', 'subtitle' => 'Expériences à vivre', 'color' => '#7F8C8D', 'href' => '?page=activites'],
    ['icon' => '🚌', 'title' => 'Transport',  'subtitle' => 'Rechercher et réserver',        'color' => '#2E86AB', 'href' => '?page=transport'],
    ['icon' => '🏛️', 'title' => 'Patrimoine', 'subtitle' => 'Visites et contenus culturels', 'color' => '#16A085', 'href' => '?page=heritage'],
    ['icon' => '🗺️', 'title' => 'Carte',      'subtitle' => 'Explorer les lieux sur la carte','color' => '#27AE60', 'href' => '?page=map'],
    ['icon' => '🛂', 'title' => 'Passeport',  'subtitle' => 'Votre carnet de voyage',         'color' => '#f97316', 'href' => '?page=passport'],
    ['icon' => '⚙️', 'title' => 'Paramètres', 'subtitle' => 'Thème et accessibilité',         'color' => '#7F8C8D', 'href' => '?page=settings'],
];

// ===== Images du hero =====
$heroImages = [
    'lib/images/Stade.jfif',
    'lib/images/basilique.jpg',
    'lib/images/Cascade.jfif',
];
?>

<style>
  /* ===== Reset global (1 seule fois) ===== */
  html, body {
    overflow-x: hidden;
    max-width: 100vw;
  }
  *, *::before, *::after { box-sizing: border-box; }

  :root {
    --dash-primary: #f97316;
    --dash-primary-hover: #ea580c;
    --dash-primary-soft: rgba(249, 115, 22, 0.10);
    --dash-bg: #ffffff;
    --dash-surface: #fff8f3;
    --dash-border: #f1e4d8;
    --dash-text: #1c1410;
    --dash-muted: #8a7266;
    --dash-radius: 12px;
    --dash-radius-xl: 16px;
    --dash-shadow: 0 2px 10px -4px rgba(234, 88, 12, 0.12);
    --dash-font: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  body {
    font-family: var(--dash-font);
    font-size: 13px;
    line-height: 1.5;
    color: var(--dash-text);
  }

  /* ===== HERO BANNER ===== */
  .hero-banner {
    position: relative;
    border-radius: var(--dash-radius-xl);
    overflow: hidden;
    min-height: 210px;
    margin-bottom: 1rem;
    box-shadow: 0 6px 20px -8px rgba(234, 88, 12, 0.3);
    isolation: isolate;
  }
  .hero-slides {
    position: absolute;
    inset: 0;
  }
  .hero-slide {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center;
    opacity: 0;
    transition: opacity 0.7s ease-in-out;
  }
  .hero-slide.active { opacity: 1; }

  .hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg,
      rgba(0, 0, 0, 0.72) 0%,
      rgba(0, 0, 0, 0.08) 100%);
    z-index: 1;
  }

  .hero-decorations {
    position: absolute;
    inset: 0;
    z-index: 2;
    pointer-events: none;
    overflow: hidden;
  }
  .hero-circle {
    position: absolute;
    border-radius: 50%;
    background: radial-gradient(circle,
      rgba(255, 255, 255, 0.10) 0%,
      rgba(255, 255, 255, 0) 70%);
  }
  .circle-1 { width: 150px; height: 150px; top: -35px; right: -35px; }
  .circle-2 { width: 100px; height: 100px; bottom: -25px; left: 25px; }
  .circle-3 { width: 50px;  height: 50px;  top: 55px;  left: 55px; }

  .hero-content {
    position: relative;
    z-index: 3;
    padding: 1.15rem;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    min-height: 210px;
    height: 100%;
  }
  .hero-badge {
    display: inline-flex;
    align-self: flex-start;
    padding: 0.2rem 0.55rem;
    background: var(--dash-primary);
    color: #fff;
    font-size: 0.58rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    border-radius: 999px;
    margin-bottom: 0.5rem;
  }
  .hero-title {
    color: #fff;
    font-size: 1.4rem;
    font-weight: 900;
    line-height: 1.12;
    letter-spacing: -0.5px;
    margin: 0 0 0.3rem;
  }
  .hero-subtitle {
    color: rgba(255, 255, 255, 0.85);
    font-size: 0.75rem;
    margin: 0 0 0.75rem;
  }
  .hero-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    align-self: flex-start;
    padding: 0.55rem 1rem;
    background: #fff;
    color: var(--dash-primary);
    font-size: 0.72rem;
    font-weight: 800;
    text-decoration: none;
    border-radius: 999px;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.12);
  }
  .hero-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 5px 14px rgba(0, 0, 0, 0.18);
  }

  /* ===== PANEL ===== */
  .panel {
    background: var(--dash-bg);
    border: 1px solid var(--dash-border);
    border-radius: var(--dash-radius);
    padding: 0.75rem 0.75rem 0.6rem;
    box-shadow: var(--dash-shadow);
    min-width: 0;
    max-width: 100%;
    margin-bottom: 1rem;
  }
  .panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.6rem;
    margin-bottom: 0.55rem;
    padding-bottom: 0.45rem;
    border-bottom: 1px solid #f3ebe4;
  }
  .section-title {
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--dash-text);
    margin: 0;
  }
  .text-link {
    font-size: 0.65rem;
    font-weight: 600;
    color: var(--dash-primary);
    text-decoration: none;
    transition: color 0.15s ease;
    white-space: nowrap;
  }
  .text-link:hover { color: var(--dash-primary-hover); }

  /* ===== FEATURE SHOWCASE ===== */
  .feature-showcase {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 0.5rem;
  }
  .feature-card-modern {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.6rem 0.7rem;
    background: var(--dash-bg);
    border: 1px solid var(--dash-border);
    border-radius: var(--dash-radius);
    text-decoration: none;
    color: inherit;
    transition: transform 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
    box-shadow: 0 1px 4px -2px rgba(0, 0, 0, 0.04);
    min-width: 0;
  }
  .feature-card-modern:hover {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--feature-color) 30%, var(--dash-border));
    box-shadow: 0 4px 12px -4px color-mix(in srgb, var(--feature-color) 22%, transparent);
  }
  .feature-icon-wrapper {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg,
      color-mix(in srgb, var(--feature-color) 20%, transparent),
      color-mix(in srgb, var(--feature-color) 6%, transparent));
    border-radius: 10px;
  }
  .feature-icon { font-size: 0.95rem; line-height: 1; }
  .feature-text {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
  }
  .feature-title {
    font-size: 0.75rem;
    font-weight: 800;
    color: var(--dash-text);
    letter-spacing: -0.1px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .feature-subtitle {
    font-size: 0.63rem;
    color: var(--dash-muted);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .feature-chevron {
    flex-shrink: 0;
    font-size: 1.1rem;
    font-weight: 300;
    color: rgba(28, 20, 16, 0.3);
    line-height: 1;
  }

  /* ===== GRILLE DESTINATIONS ===== */
  .feature-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
    gap: 0.6rem;
  }
  .feature-card {
    background: var(--dash-bg);
    border: 1px solid var(--dash-border);
    border-radius: var(--dash-radius);
    overflow: hidden;
    transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
  }
  .feature-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px -6px rgba(234, 88, 12, 0.18);
    border-color: #fcd9b5;
  }
  .feature-card img {
    width: 100%;
    height: 110px;
    object-fit: cover;
    display: block;
  }
  .feature-card-body { padding: 0.6rem 0.65rem; }
  .eyebrow {
    font-size: 0.58rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--dash-primary-hover);
    margin-bottom: 0.15rem;
    display: block;
  }
  .feature-card-body h2 {
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--dash-text);
    margin: 0 0 0.45rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.38rem 0.75rem;
    background: linear-gradient(135deg, #fb923c, #ea580c);
    color: #fff;
    font-size: 0.65rem;
    font-weight: 600;
    border: none;
    border-radius: 7px;
    text-decoration: none;
    cursor: pointer;
    box-shadow: 0 3px 10px rgba(249, 115, 22, 0.3);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
  }
  .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 5px 14px rgba(234, 88, 12, 0.4);
  }

  /* ===== ÉTAT VIDE ===== */
  .empty { text-align: center; padding: 0.75rem 0.4rem; }
  .empty-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    margin-bottom: 0.4rem;
    background: var(--dash-primary-soft);
    color: var(--dash-primary-hover);
    border-radius: 50%;
    font-size: 0.9rem;
  }
  .empty h3 {
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--dash-text);
    margin: 0 0 0.4rem;
  }

  /* ===== RESPONSIVE ===== */
  @media (max-width: 768px) {
    .feature-showcase { grid-template-columns: 1fr; }
    .hero-title { font-size: 1.25rem; }
    .hero-banner { min-height: 190px; }
    .hero-content { min-height: 190px; padding: 0.9rem; }
  }
  @media (max-width: 520px) {
    .hero-title { font-size: 1.15rem; }
    .feature-grid { grid-template-columns: 1fr 1fr; }
    .feature-card img { height: 95px; }
    .panel { padding: 0.65rem 0.65rem 0.55rem; }
  }
  @media (prefers-reduced-motion: reduce) {
    .hero-slide, .hero-btn, .feature-card-modern, .feature-card {
      transition: none !important;
    }
  }
</style>

<!-- ===== HERO ===== -->
<section class="hero-banner" id="heroBanner">
  <div class="hero-slides">
    <?php foreach ($heroImages as $idx => $img): ?>
      <div class="hero-slide <?= $idx === 0 ? 'active' : '' ?>"
           style="background-image: url('<?= client_h($img) ?>')"></div>
    <?php endforeach; ?>
  </div>
  <div class="hero-overlay"></div>
  <div class="hero-decorations">
    <span class="hero-circle circle-1"></span>
    <span class="hero-circle circle-2"></span>
    <span class="hero-circle circle-3"></span>
  </div>
  <div class="hero-content">
    <span class="hero-badge">À LA UNE</span>
    <h1 class="hero-title">Découvrez la<br>Côte d'Ivoire</h1>
    <p class="hero-subtitle">Destinations, cultures et aventures</p>
    <a class="hero-btn" href="?page=explore">
      Explorer maintenant <span aria-hidden="true">→</span>
    </a>
  </div>
</section>

<!-- ===== QUE VOULEZ-VOUS FAIRE ? ===== -->
<section class="panel">
  <header class="panel-head">
    <h2 class="section-title">Que voulez-vous faire ?</h2>
  </header>
  <div class="feature-showcase">
    <?php foreach ($features as $f): ?>
      <a class="feature-card-modern" href="<?= client_h($f['href']) ?>"
         style="--feature-color: <?= client_h($f['color']) ?>">
        <div class="feature-icon-wrapper">
          <span class="feature-icon"><?= $f['icon'] ?></span>
        </div>
        <div class="feature-text">
          <span class="feature-title"><?= client_h($f['title']) ?></span>
          <span class="feature-subtitle"><?= client_h($f['subtitle']) ?></span>
        </div>
        <span class="feature-chevron">›</span>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<!-- ===== DESTINATIONS ===== -->
<section class="panel">
  <header class="panel-head">
    <h2 class="section-title">Destinations à découvrir</h2>
    <a class="text-link" href="?page=explore">Tout voir →</a>
  </header>
  <?php if ($featured): ?>
    <div class="feature-grid">
      <?php foreach ($featured as $destination): ?>
        <article class="feature-card">
          <?php if (!empty($destination['image_principale'])): ?>
            <img src="<?= client_h($destination['image_principale']) ?>"
                 alt="<?= client_h($destination['nom'] ?? '') ?>" loading="lazy">
          <?php endif; ?>
          <div class="feature-card-body">
            <span class="eyebrow"><?= client_h($destination['localite'] ?? '') ?></span>
            <h2><?= client_h($destination['nom'] ?? '') ?></h2>
            <a class="btn" href="?page=destination&amp;id=<?= (int)$destination['id'] ?>">Découvrir</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="empty">
      <div class="empty-icon">🌄</div>
      <h3>Aucune destination publiée pour le moment.</h3>
    </div>
  <?php endif; ?>
</section>

