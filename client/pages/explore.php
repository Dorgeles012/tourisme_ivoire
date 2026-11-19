<?php
// ===== Lecture et validation des filtres =====
$readFilterId = static function (string $key): ?int {
    $raw = $_GET[$key] ?? null;
    if ($raw === null || $raw === '') return null;
    $value = filter_var($raw, FILTER_VALIDATE_INT);
    if ($value === false || $value < 1) {
        throw new ClientApiException(422, 'Filtre géographique invalide.');
    }
    return $value;
};

$query        = trim((string)($_GET['q'] ?? ''));
$categorySlug = trim((string)($_GET['categorie'] ?? ''));
$districtId   = $readFilterId('district_id');
$regionId     = $readFilterId('region_id');
$departmentId = $readFilterId('departement_id');
$pageNum      = max(1, (int)($_GET['p'] ?? 1));

// ===== Construction de la requête destinations =====
$args = ['page' => $pageNum, 'per_page' => 12];
if ($query !== '')         $args['q'] = $query;
if ($categorySlug !== '')  $args['categorie'] = $categorySlug;
if ($districtId !== null)  $args['district_id'] = $districtId;
if ($regionId !== null)    $args['region_id'] = $regionId;
if ($departmentId !== null) $args['departement_id'] = $departmentId;

$payload = ClientApi::data($api->request('GET', 'api/destinations?' . http_build_query($args)));
$items   = is_array($payload['items'] ?? null) ? $payload['items'] : [];
$total   = (int)($payload['total'] ?? count($items));

// ===== Chargement des référentiels =====
$categories = ClientApi::data($api->request('GET', 'api/categories'));
$categories = is_array($categories) ? $categories : [];

$districts = ClientApi::data($api->request('GET', 'api/districts'));
$districts = is_array($districts) ? $districts : [];

// Type du district sélectionné
$districtType = '';
foreach ($districts as $district) {
    if ((int)($district['id'] ?? 0) === $districtId) {
        $districtType = (string)($district['type'] ?? '');
    }
}

// Régions dépendantes du district
$regions = ClientApi::data($api->request(
    'GET',
    'api/regions' . ($districtId === null ? '' : '?' . http_build_query(['district_id' => $districtId]))
));
$regions = is_array($regions) ? $regions : [];

// Départements dépendants de la région OU du district autonome
$departmentFilter = $regionId !== null
    ? ['region_id' => $regionId]
    : ($districtId !== null && $districtType === 'autonome' ? ['district_id' => $districtId] : null);
$departments = $departmentFilter === null
    ? []
    : ClientApi::data($api->request('GET', 'api/departements?' . http_build_query($departmentFilter)));
$departments = is_array($departments) ? $departments : [];

// ===== Label géographique =====
$districtName = '';
foreach ($districts as $d) {
    if ((int)($d['id'] ?? 0) === $districtId) { $districtName = (string)($d['nom'] ?? ''); break; }
}
$regionName = '';
foreach ($regions as $r) {
    if ((int)($r['id'] ?? 0) === $regionId) { $regionName = (string)($r['nom'] ?? ''); break; }
}
$departmentName = '';
foreach ($departments as $d) {
    if ((int)($d['id'] ?? 0) === $departmentId) { $departmentName = (string)($d['nom'] ?? ''); break; }
}

$hasGeoFilter = $regionId !== null || $districtId !== null || $departmentId !== null;
$geoLabel     = $departmentName ?: ($regionName ?: ($districtName ?: 'Toute la Côte d\'Ivoire'));

// ===== Helpers de construction d'URL =====
$buildUrl = static function (array $overrides = []) use (
    $query, $categorySlug, $districtId, $regionId, $departmentId
): string {
    $params = ['page' => 'explore'];
    if ($query !== '')          $params['q'] = $query;
    if ($categorySlug !== '')   $params['categorie'] = $categorySlug;
    if ($districtId !== null)   $params['district_id'] = $districtId;
    if ($regionId !== null)     $params['region_id'] = $regionId;
    if ($departmentId !== null) $params['departement_id'] = $departmentId;
    $params = array_merge($params, $overrides);
    foreach ($params as $k => $v) {
        if ($v === null || $v === '') unset($params[$k]);
    }
    return '?' . http_build_query($params);
};
?>

<style>
  /* ===== Reset global (1 seule fois) ===== */
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

  /* ===== Barre supérieure ===== */
  .explore-topbar {
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 12px;
    padding: 0.75rem 0.85rem;
    box-shadow: 0 2px 10px -4px rgba(234, 88, 12, 0.12);
    margin-bottom: 0.7rem;
  }
  .search-row { display: flex; width: 100%; }
  .search-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
    width: 100%;
    background: #fff8f3;
    border: 1.5px solid #f1e4d8;
    border-radius: 999px;
    padding: 0 0.8rem;
    transition: border-color 0.15s ease, background 0.15s ease, box-shadow 0.15s ease;
  }
  .search-input-wrap:focus-within {
    border-color: #f97316;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.10);
  }
  .search-icon { font-size: 0.8rem; margin-right: 0.4rem; opacity: 0.6; }
  .search-input-wrap input {
    flex: 1;
    border: none;
    background: transparent;
    outline: none;
    padding: 0.65rem 0;
    font-size: 0.78rem;
    font-family: inherit;
    color: #1c1410;
    min-width: 0;
  }
  .search-input-wrap input::placeholder { color: #d8bfa8; }

  /* ===== Chip géographique ===== */
  .geo-row {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    margin-top: 0.55rem;
  }
  .geo-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    flex: 1;
    min-width: 0;
    height: 36px;
    padding: 0 0.8rem;
    background: #f7f1eb;
    border: 1px solid #e8d9cc;
    border-radius: 999px;
    color: #5a4a40;
    font-size: 0.72rem;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
    font-family: inherit;
  }
  .geo-chip.is-active {
    background: rgba(249, 115, 22, 0.10);
    border-color: rgba(249, 115, 22, 0.55);
    border-width: 1.5px;
    color: #f97316;
  }
  .geo-chip:hover { border-color: #fcd9b5; }
  .geo-chip-icon { font-size: 0.85rem; flex-shrink: 0; }
  .geo-chip-label {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    flex: 1;
    text-align: left;
  }
  .geo-chip-caret { opacity: 0.65; flex-shrink: 0; font-size: 0.75rem; }

  .geo-clear {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    background: #f7f1eb;
    border: 1px solid #e8d9cc;
    border-radius: 50%;
    color: #8a7266;
    text-decoration: none;
    font-size: 0.72rem;
    transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
  }
  .geo-clear:hover { background: #fff; color: #ea580c; border-color: #fcd9b5; }

  /* ===== Chips catégories ===== */
  .categories-row {
    margin: 0 0 0.65rem;
    overflow: hidden;
  }
  .categories-scroll {
    display: flex;
    gap: 0.4rem;
    overflow-x: auto;
    padding: 0.1rem 0.1rem 0.45rem;
    scrollbar-width: thin;
    -webkit-overflow-scrolling: touch;
  }
  .categories-scroll::-webkit-scrollbar { height: 3px; }
  .categories-scroll::-webkit-scrollbar-thumb {
    background: rgba(0,0,0,0.10);
    border-radius: 3px;
  }
  .chip {
    display: inline-flex;
    align-items: center;
    padding: 0.4rem 0.8rem;
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 999px;
    font-size: 0.7rem;
    font-weight: 600;
    color: #1c1410;
    text-decoration: none;
    white-space: nowrap;
    transition: border-color 0.15s ease, background 0.15s ease, color 0.15s ease;
    font-family: inherit;
  }
  .chip:hover { border-color: #fcd9b5; background: #fff8f3; }
  .chip.is-active {
    background: linear-gradient(135deg, #fb923c, #ea580c);
    color: #fff;
    border-color: transparent;
    box-shadow: 0 3px 10px -2px rgba(249, 115, 22, 0.3);
  }

  /* ===== Compteur ===== */
  .results-count {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.1rem 0 0.55rem;
    font-size: 0.7rem;
    font-weight: 600;
    color: #8a7266;
  }
  .results-count .dot {
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: #f97316;
  }

  /* ===== Grille destinations ===== */
  .explore-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
    gap: 0.7rem;
    width: 100%;
    min-width: 0;
  }
  .explore-card {
    display: flex;
    flex-direction: column;
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 12px;
    overflow: hidden;
    transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
  }
  .explore-card:hover {
    border-color: #fcd9b5;
    box-shadow: 0 6px 18px -6px rgba(234, 88, 12, 0.2);
    transform: translateY(-2px);
  }
  .explore-card-img {
    width: 100%;
    aspect-ratio: 16 / 10;
    background: #f1e4d8;
    overflow: hidden;
  }
  .explore-card-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.35s ease;
  }
  .explore-card:hover .explore-card-img img { transform: scale(1.05); }
  .explore-card-body {
    padding: 0.7rem 0.75rem 0.8rem;
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
  }
  .explore-eyebrow {
    font-size: 0.58rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #f97316;
    margin-bottom: 0.2rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .explore-title {
    font-size: 0.82rem;
    font-weight: 700;
    color: #1c1410;
    margin: 0 0 0.3rem;
    line-height: 1.25;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
  }
  .explore-desc {
    font-size: 0.68rem;
    color: #8a7266;
    line-height: 1.4;
    margin: 0 0 0.65rem;
    flex: 1;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
  }
  .explore-cta {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.4rem 0.75rem;
    font-size: 0.68rem;
    font-weight: 600;
    color: #fff;
    background: linear-gradient(135deg, #fb923c, #ea580c);
    border-radius: 7px;
    text-decoration: none;
    box-shadow: 0 2px 8px -2px rgba(249, 115, 22, 0.3);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    margin-top: auto;
  }
  .explore-cta:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px -2px rgba(234, 88, 12, 0.4);
  }

  /* ===== État vide ===== */
  .explore-empty {
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 12px;
    padding: 1rem 0.8rem;
    box-shadow: 0 2px 12px -4px rgba(234, 88, 12, 0.1);
  }
  .empty { text-align: center; padding: 0.75rem 0.4rem; }
  .empty-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    margin-bottom: 0.5rem;
    background: rgba(249, 115, 22, 0.10);
    color: #ea580c;
    border-radius: 50%;
    font-size: 1.05rem;
  }
  .empty h3 {
    font-size: 0.82rem;
    font-weight: 700;
    color: #1c1410;
    margin: 0 0 0.2rem;
  }
  .empty p {
    font-size: 0.7rem;
    color: #8a7266;
    margin: 0 0 0.75rem;
    line-height: 1.4;
  }
  .btn-reset {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.45rem 0.9rem;
    background: linear-gradient(135deg, #fb923c, #ea580c);
    color: #fff;
    font-size: 0.72rem;
    font-weight: 600;
    border-radius: 8px;
    text-decoration: none;
    box-shadow: 0 3px 10px -2px rgba(249, 115, 22, 0.3);
    transition: transform 0.15s ease;
  }
  .btn-reset:hover { transform: translateY(-1px); }

  /* ===== Panneau géo ===== */
  .geo-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.45);
    z-index: 1000;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    padding: 0;
    animation: fadeIn 0.18s ease;
  }
  .geo-overlay[hidden] { display: none; }
  @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

  .geo-panel {
    width: 100%;
    max-width: 560px;
    background: #fff;
    border-top-left-radius: 16px;
    border-top-right-radius: 16px;
    max-height: 80vh;
    display: flex;
    flex-direction: column;
    animation: slideUp 0.22s ease;
  }
  @keyframes slideUp {
    from { transform: translateY(30px); opacity: 0.6; }
    to   { transform: translateY(0); opacity: 1; }
  }
  .geo-panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.85rem 0.95rem 0.65rem;
    border-bottom: 1px solid #f3ebe4;
  }
  .geo-panel-head h2 {
    font-size: 0.85rem;
    font-weight: 800;
    color: #1c1410;
    margin: 0;
  }
  .geo-close {
    background: #f7f1eb;
    border: none;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 0.75rem;
    color: #5a4a40;
    transition: background 0.15s ease;
  }
  .geo-close:hover { background: #fff8f3; }

  .geo-panel-body {
    padding: 0.85rem 0.95rem;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
  }
  .geo-step { display: flex; flex-direction: column; gap: 0.3rem; }
  .geo-step[hidden] { display: none; }
  .geo-step-label {
    font-size: 0.65rem;
    font-weight: 700;
    color: #5a4a40;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }
  .geo-select {
    padding: 0.6rem 0.8rem;
    font-size: 0.78rem;
    font-family: inherit;
    color: #1c1410;
    background: #fff8f3;
    border: 1.5px solid #f1e4d8;
    border-radius: 9px;
    outline: none;
    transition: border-color 0.15s ease, background 0.15s ease, box-shadow 0.15s ease;
  }
  .geo-select:focus {
    border-color: #f97316;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.10);
  }
  .geo-panel-foot {
    display: flex;
    gap: 0.5rem;
    padding: 0.8rem 0.95rem 1rem;
    border-top: 1px solid #f3ebe4;
  }
  .geo-btn-clear, .geo-btn-apply {
    flex: 1;
    padding: 0.65rem 0.9rem;
    font-size: 0.78rem;
    font-weight: 700;
    font-family: inherit;
    border: none;
    border-radius: 9px;
    cursor: pointer;
    transition: background 0.15s ease, transform 0.15s ease, box-shadow 0.15s ease;
  }
  .geo-btn-clear { background: #f7f1eb; color: #5a4a40; }
  .geo-btn-clear:hover { background: #fff8f3; }
  .geo-btn-apply {
    background: linear-gradient(135deg, #fb923c, #ea580c);
    color: #fff;
    box-shadow: 0 3px 10px -2px rgba(249, 115, 22, 0.3);
  }
  .geo-btn-apply:hover { transform: translateY(-1px); }

  /* ===== Responsive ===== */
  @media (max-width: 640px) {
    .explore-grid {
      grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
      gap: 0.55rem;
    }
    .explore-card-body { padding: 0.6rem 0.65rem 0.7rem; }
    .explore-title { font-size: 0.75rem; }
    .explore-desc { font-size: 0.63rem; -webkit-line-clamp: 2; }
  }
  @media (max-width: 380px) {
    .explore-grid { grid-template-columns: 1fr; }
  }
  @media (prefers-reduced-motion: reduce) {
    *, .explore-card, .chip, .geo-panel, .geo-overlay { animation: none !important; transition: none !important; }
  }
</style>

<!-- ===== BARRE DE RECHERCHE + CHIP GÉOGRAPHIQUE ===== -->
<section class="panel explore-topbar">
  <form method="get" class="explore-form" id="exploreForm" autocomplete="off">
    <input type="hidden" name="page" value="explore">
    <?php if ($districtId !== null): ?>
      <input type="hidden" name="district_id" value="<?= (int)$districtId ?>">
    <?php endif; ?>
    <?php if ($regionId !== null): ?>
      <input type="hidden" name="region_id" value="<?= (int)$regionId ?>">
    <?php endif; ?>
    <?php if ($departmentId !== null): ?>
      <input type="hidden" name="departement_id" value="<?= (int)$departmentId ?>">
    <?php endif; ?>
    <?php if ($categorySlug !== ''): ?>
      <input type="hidden" name="categorie" value="<?= client_h($categorySlug) ?>">
    <?php endif; ?>

    <div class="search-row">
      <div class="search-input-wrap">
        <span class="search-icon" aria-hidden="true">🔍</span>
        <input
          id="exploreSearch"
          type="search"
          name="q"
          value="<?= client_h($query) ?>"
          maxlength="120"
          placeholder="Destination, région, activité…"
          aria-label="Rechercher une destination">
      </div>
    </div>
  </form>

  <div class="geo-row">
    <button
      type="button"
      class="geo-chip <?= $hasGeoFilter ? 'is-active' : '' ?>"
      data-open-geo
      aria-label="Filtrer par zone géographique">
      <span class="geo-chip-icon">🗺️</span>
      <span class="geo-chip-label"><?= client_h($geoLabel) ?></span>
      <span class="geo-chip-caret" aria-hidden="true">▾</span>
    </button>

    <?php if ($hasGeoFilter): ?>
      <a
        href="<?= client_h($buildUrl(['district_id' => null, 'region_id' => null, 'departement_id' => null])) ?>"
        class="geo-clear"
        title="Effacer le filtre géographique"
        aria-label="Effacer le filtre géographique">✕</a>
    <?php endif; ?>
  </div>
</section>

<!-- ===== FILTRES CATÉGORIES ===== -->
<?php if (count($categories) > 0): ?>
<section class="categories-row" aria-label="Filtrer par catégorie">
  <div class="categories-scroll">
    <a
      href="<?= client_h($buildUrl(['categorie' => null, 'p' => 1])) ?>"
      class="chip <?= $categorySlug === '' ? 'is-active' : '' ?>">
      Tout
    </a>
    <?php foreach ($categories as $cat): ?>
      <?php $slug = (string)($cat['slug'] ?? ''); ?>
      <?php if ($slug === '') continue; ?>
      <a
        href="<?= client_h($buildUrl(['categorie' => $slug, 'p' => 1])) ?>"
        class="chip <?= $categorySlug === $slug ? 'is-active' : '' ?>">
        <?= client_h($cat['nom'] ?? '') ?>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- ===== COMPTEUR ===== -->
<?php if ($total > 0): ?>
<div class="results-count">
  <span class="dot" aria-hidden="true"></span>
  <?= $total ?> destination<?= $total > 1 ? 's' : '' ?> trouvée<?= $total > 1 ? 's' : '' ?>
</div>
<?php endif; ?>

<!-- ===== GRILLE DES DESTINATIONS ===== -->
<?php if (!$items): ?>
  <section class="panel explore-empty">
    <div class="empty">
      <div class="empty-icon">✦</div>
      <h3>Aucune destination trouvée</h3>
      <p>Essayez un autre mot-clé ou réinitialisez les filtres.</p>
      <a class="btn-reset" href="?page=explore">Réinitialiser</a>
    </div>
  </section>
<?php else: ?>
  <section class="explore-grid">
    <?php foreach ($items as $item): ?>
      <article class="explore-card">
        <?php if (!empty($item['image_principale'])): ?>
          <div class="explore-card-img">
            <img src="<?= client_h($item['image_principale']) ?>"
                 alt="<?= client_h($item['nom'] ?? '') ?>"
                 loading="lazy">
          </div>
        <?php endif; ?>
        <div class="explore-card-body">
          <span class="explore-eyebrow">
            <?= client_h($item['localite'] ?? '') ?>
            <?php if (!empty($item['categorie'])): ?>
              · <?= client_h($item['categorie']) ?>
            <?php endif; ?>
          </span>
          <h2 class="explore-title"><?= client_h($item['nom'] ?? '') ?></h2>
          <p class="explore-desc">
            <?= client_h(mb_strimwidth((string)($item['description'] ?? ''), 0, 140, '…')) ?>
          </p>
          <a class="explore-cta" href="?page=destination&amp;id=<?= (int)$item['id'] ?>">
            Découvrir →
          </a>
        </div>
      </article>
    <?php endforeach; ?>
  </section>
<?php endif; ?>

<!-- ===== PANNEAU GÉOGRAPHIQUE ===== -->
<div class="geo-overlay" id="geoOverlay" hidden>
  <div class="geo-panel" role="dialog" aria-modal="true" aria-labelledby="geoPanelTitle">
    <div class="geo-panel-head">
      <h2 id="geoPanelTitle">Filtrer par localisation</h2>
      <button type="button" class="geo-close" data-close-geo aria-label="Fermer">✕</button>
    </div>

    <div class="geo-panel-body">
      <div class="geo-step">
        <label class="geo-step-label" for="geoDistrict">District</label>
        <select id="geoDistrict" class="geo-select">
          <option value="">Toute la sélection</option>
          <?php foreach ($districts as $d): ?>
            <option value="<?= (int)$d['id'] ?>"
                    data-type="<?= client_h($d['type'] ?? '') ?>"
                    <?= $districtId === (int)$d['id'] ? 'selected' : '' ?>>
              <?= client_h($d['nom'] ?? '') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="geo-step" id="geoRegionStep" <?= $districtId === null ? 'hidden' : '' ?>>
        <label class="geo-step-label" for="geoRegion">Région</label>
        <select id="geoRegion" class="geo-select">
          <option value="">Toutes les régions</option>
          <?php foreach ($regions as $r): ?>
            <option value="<?= (int)$r['id'] ?>" <?= $regionId === (int)$r['id'] ? 'selected' : '' ?>>
              <?= client_h($r['nom'] ?? '') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="geo-step" id="geoDeptStep" <?= !$departments ? 'hidden' : '' ?>>
        <label class="geo-step-label" for="geoDept">Département</label>
        <select id="geoDept" class="geo-select">
          <option value="">Tous les départements</option>
          <?php foreach ($departments as $d): ?>
            <option value="<?= (int)$d['id'] ?>" <?= $departmentId === (int)$d['id'] ? 'selected' : '' ?>>
              <?= client_h($d['nom'] ?? '') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="geo-panel-foot">
      <button type="button" class="geo-btn-clear" data-clear-geo>Effacer</button>
      <button type="button" class="geo-btn-apply" data-apply-geo>Appliquer</button>
    </div>
  </div>
</div>

