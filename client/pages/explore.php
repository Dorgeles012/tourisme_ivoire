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
if ($query !== '')       $args['q'] = $query;
if ($categorySlug !== '') $args['categorie'] = $categorySlug;
if ($districtId !== null)   $args['district_id'] = $districtId;
if ($regionId !== null)     $args['region_id'] = $regionId;
if ($departmentId !== null) $args['departement_id'] = $departmentId;

$payload = ClientApi::data($api->request('GET', 'api/destinations?' . http_build_query($args)));
$items   = is_array($payload['items'] ?? null) ? $payload['items'] : [];
$total   = (int)($payload['total'] ?? count($items));

// ===== Chargement des référentiels =====
$categories = ClientApi::data($api->request('GET', 'api/categories'));
$categories = is_array($categories) ? $categories : [];

$districts = ClientApi::data($api->request('GET', 'api/districts'));
$districts = is_array($districts) ? $districts : [];

// Type du district sélectionné (pour savoir si autonome)
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

// ===== Label géographique (équivalent _geoLabel) =====
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
    // Nettoyage des valeurs vides/null
    foreach ($params as $k => $v) {
        if ($v === null || $v === '') unset($params[$k]);
    }
    return '?' . http_build_query($params);
};
?>

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

  <!-- Chip géographique (équivalent _geoLabel) -->
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

<!-- ===== FILTRES CATÉGORIES (chips horizontales) ===== -->
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

<!-- ===== PANNEAU GÉOGRAPHIQUE (équivalent _RegionBottomSheet en cascade) ===== -->
<div class="geo-overlay" id="geoOverlay" hidden>
  <div class="geo-panel" role="dialog" aria-modal="true" aria-labelledby="geoPanelTitle">
    <div class="geo-panel-head">
      <h2 id="geoPanelTitle">Filtrer par localisation</h2>
      <button type="button" class="geo-close" data-close-geo aria-label="Fermer">✕</button>
    </div>

    <div class="geo-panel-body">
      <!-- Étape 1 : District -->
      <div class="geo-step">
        <label class="geo-step-label">District</label>
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

      <!-- Étape 2 : Région -->
      <div class="geo-step" id="geoRegionStep" <?= $districtId === null ? 'hidden' : '' ?>>
        <label class="geo-step-label">Région</label>
        <select id="geoRegion" class="geo-select">
          <option value="">Toutes les régions</option>
          <?php foreach ($regions as $r): ?>
            <option value="<?= (int)$r['id'] ?>" <?= $regionId === (int)$r['id'] ? 'selected' : '' ?>>
              <?= client_h($r['nom'] ?? '') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Étape 3 : Département -->
      <div class="geo-step" id="geoDeptStep" <?= !$departments ? 'hidden' : '' ?>>
        <label class="geo-step-label">Département</label>
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

<style>
  *, *::before, *::after { box-sizing: border-box; }

  /* ===== Barre supérieure ===== */
  .explore-topbar {
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 14px;
    padding: 0.9rem 1rem;
    box-shadow: 0 4px 18px -6px rgba(234, 88, 12, 0.15);
    margin-bottom: 0.85rem;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
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
    padding: 0 0.9rem;
    transition: border-color 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
  }

  .search-input-wrap:focus-within {
    border-color: #f97316;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.10);
  }

  .search-icon { font-size: 0.9rem; margin-right: 0.5rem; opacity: 0.6; }

  .search-input-wrap input {
    flex: 1;
    border: none;
    background: transparent;
    outline: none;
    padding: 0.75rem 0;
    font-size: 0.88rem;
    font-family: inherit;
    color: #1c1410;
  }

  .search-input-wrap input::placeholder { color: #d8bfa8; }

  .geo-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.65rem;
  }

  .geo-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    flex: 1;
    min-width: 0;
    height: 42px;
    padding: 0 0.9rem;
    background: #f7f1eb;
    border: 1px solid #e8d9cc;
    border-radius: 999px;
    color: #5a4a40;
    font-size: 0.82rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
    font-family: inherit;
  }

  .geo-chip.is-active {
    background: rgba(249, 115, 22, 0.10);
    border-color: rgba(249, 115, 22, 0.55);
    border-width: 1.5px;
    color: #f97316;
  }

  .geo-chip:hover { border-color: #fcd9b5; }
  .geo-chip-icon { font-size: 1rem; flex-shrink: 0; }

  .geo-chip-label {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    flex: 1;
    text-align: left;
  }

  .geo-chip-caret { opacity: 0.65; flex-shrink: 0; }

  .geo-clear {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    background: #f7f1eb;
    border: 1px solid #e8d9cc;
    border-radius: 50%;
    color: #8a7266;
    text-decoration: none;
    font-size: 0.85rem;
    transition: all 0.2s ease;
  }

  .geo-clear:hover { background: #fff; color: #ea580c; border-color: #fcd9b5; }

  /* ===== Chips catégories ===== */
  .categories-row {
    margin: 0 0 0.75rem;
    overflow: hidden;
  }

  .categories-scroll {
    display: flex;
    gap: 0.5rem;
    overflow-x: auto;
    padding: 0.15rem 0.15rem 0.55rem;
    scrollbar-width: thin;
  }

  .categories-scroll::-webkit-scrollbar { height: 4px; }
  .categories-scroll::-webkit-scrollbar-thumb {
    background: rgba(0,0,0,0.12);
    border-radius: 4px;
  }

  .chip {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 0.95rem;
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 600;
    color: #1c1410;
    text-decoration: none;
    white-space: nowrap;
    transition: all 0.2s ease;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  .chip:hover { border-color: #fcd9b5; background: #fff8f3; }

  .chip.is-active {
    background: linear-gradient(135deg, #fb923c, #ea580c);
    color: #fff;
    border-color: transparent;
    box-shadow: 0 4px 12px rgba(249, 115, 22, 0.30);
  }

  /* ===== Compteur ===== */
  .results-count {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.15rem 0 0.65rem;
    font-size: 0.78rem;
    font-weight: 600;
    color: #8a7266;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  .results-count .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #f97316;
  }

  /* ===== Grille destinations ===== */
  .explore-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 0.85rem;
    width: 100%;
    min-width: 0;
  }

  .explore-card {
    display: flex;
    flex-direction: column;
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 14px;
    overflow: hidden;
    transition: all 0.25s ease;
  }

  .explore-card:hover {
    border-color: #fcd9b5;
    box-shadow: 0 8px 22px -8px rgba(234, 88, 12, 0.25);
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
    transition: transform 0.4s ease;
  }

  .explore-card:hover .explore-card-img img { transform: scale(1.05); }

  .explore-card-body {
    padding: 0.85rem 0.9rem 0.95rem;
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
  }

  .explore-eyebrow {
    font-size: 0.62rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #f97316;
    margin-bottom: 0.3rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .explore-title {
    font-size: 0.92rem;
    font-weight: 700;
    color: #1c1410;
    margin: 0 0 0.35rem;
    line-height: 1.25;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
  }

  .explore-desc {
    font-size: 0.72rem;
    color: #8a7266;
    line-height: 1.45;
    margin: 0 0 0.8rem;
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
    padding: 0.5rem 0.9rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: #fff;
    background: linear-gradient(135deg, #fb923c, #ea580c);
    border-radius: 8px;
    text-decoration: none;
    box-shadow: 0 3px 10px rgba(249, 115, 22, 0.30);
    transition: all 0.2s ease;
    margin-top: auto;
  }

  .explore-cta:hover {
    transform: translateY(-1px);
    box-shadow: 0 5px 14px rgba(234, 88, 12, 0.40);
  }

  /* ===== État vide ===== */
  .explore-empty {
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 14px;
    padding: 1.2rem 1rem;
    box-shadow: 0 4px 18px -6px rgba(234, 88, 12, 0.15);
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  .empty { text-align: center; padding: 1rem 0.5rem; }

  .empty-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    margin-bottom: 0.6rem;
    background: rgba(249, 115, 22, 0.10);
    color: #ea580c;
    border-radius: 50%;
    font-size: 1.2rem;
  }

  .empty h3 {
    font-size: 0.9rem;
    font-weight: 700;
    color: #1c1410;
    margin: 0 0 0.3rem;
  }

  .empty p {
    font-size: 0.78rem;
    color: #8a7266;
    margin: 0 0 0.9rem;
    line-height: 1.4;
  }

  .btn-reset {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.5rem 1rem;
    background: linear-gradient(135deg, #fb923c, #ea580c);
    color: #fff;
    font-size: 0.78rem;
    font-weight: 600;
    border-radius: 8px;
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(249, 115, 22, 0.30);
    transition: all 0.2s ease;
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
    animation: fadeIn 0.2s ease;
  }

  .geo-overlay[hidden] { display: none; }

  @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

  .geo-panel {
    width: 100%;
    max-width: 640px;
    background: #fff;
    border-top-left-radius: 20px;
    border-top-right-radius: 20px;
    max-height: 80vh;
    display: flex;
    flex-direction: column;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    animation: slideUp 0.25s ease;
  }

  @keyframes slideUp {
    from { transform: translateY(30px); opacity: 0.6; }
    to   { transform: translateY(0); opacity: 1; }
  }

  .geo-panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.1rem 0.75rem;
    border-bottom: 1px solid #f3ebe4;
  }

  .geo-panel-head h2 {
    font-size: 1rem;
    font-weight: 800;
    color: #1c1410;
    margin: 0;
  }

  .geo-close {
    background: #f7f1eb;
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 0.9rem;
    color: #5a4a40;
  }

  .geo-close:hover { background: #fff8f3; }

  .geo-panel-body {
    padding: 1rem 1.1rem;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .geo-step { display: flex; flex-direction: column; gap: 0.35rem; }
  .geo-step[hidden] { display: none; }

  .geo-step-label {
    font-size: 0.72rem;
    font-weight: 700;
    color: #5a4a40;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }

  .geo-select {
    padding: 0.7rem 0.9rem;
    font-size: 0.85rem;
    font-family: inherit;
    color: #1c1410;
    background: #fff8f3;
    border: 1.5px solid #f1e4d8;
    border-radius: 10px;
    outline: none;
    transition: all 0.2s ease;
  }

  .geo-select:focus {
    border-color: #f97316;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.10);
  }

  .geo-panel-foot {
    display: flex;
    gap: 0.6rem;
    padding: 0.9rem 1.1rem 1.1rem;
    border-top: 1px solid #f3ebe4;
  }

  .geo-btn-clear, .geo-btn-apply {
    flex: 1;
    padding: 0.75rem 1rem;
    font-size: 0.85rem;
    font-weight: 700;
    font-family: inherit;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .geo-btn-clear {
    background: #f7f1eb;
    color: #5a4a40;
  }

  .geo-btn-clear:hover { background: #fff8f3; }

  .geo-btn-apply {
    background: linear-gradient(135deg, #fb923c, #ea580c);
    color: #fff;
    box-shadow: 0 4px 12px rgba(249, 115, 22, 0.30);
  }

  .geo-btn-apply:hover { transform: translateY(-1px); }

  /* ===== Responsive ===== */
  @media (max-width: 640px) {
    .explore-grid {
      grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
      gap: 0.7rem;
    }
    .explore-card-body { padding: 0.7rem 0.75rem 0.8rem; }
    .explore-title { font-size: 0.85rem; }
    .explore-desc { font-size: 0.68rem; -webkit-line-clamp: 2; }
  }

  @media (max-width: 380px) {
    .explore-grid { grid-template-columns: 1fr; }
  }

  @media (prefers-reduced-motion: reduce) {
    * { animation: none !important; transition: none !important; }
  }
</style>

<script>
(function () {
  // ===== Debounce sur la recherche (équivalent onChanged: _query) =====
  const form   = document.getElementById('exploreForm');
  const input  = document.getElementById('exploreSearch');
  if (form && input) {
    let timer = null;
    input.addEventListener('input', function () {
      clearTimeout(timer);
      timer = setTimeout(function () {
        if (typeof form.requestSubmit === 'function') form.requestSubmit();
        else form.submit();
      }, 350);
    });
  }

  // ===== Ouverture / fermeture du panneau géo =====
  const overlay   = document.getElementById('geoOverlay');
  const openBtn   = document.querySelector('[data-open-geo]');
  const closeBtn  = document.querySelector('[data-close-geo]');
  const applyBtn  = document.querySelector('[data-apply-geo]');
  const clearBtn  = document.querySelector('[data-clear-geo]');
  const district  = document.getElementById('geoDistrict');
  const regionSel = document.getElementById('geoRegion');
  const regionStep= document.getElementById('geoRegionStep');
  const deptSel   = document.getElementById('geoDept');
  const deptStep  = document.getElementById('geoDeptStep');

  if (openBtn && overlay) {
    openBtn.addEventListener('click', function () { overlay.hidden = false; });
  }
  if (closeBtn && overlay) {
    closeBtn.addEventListener('click', function () { overlay.hidden = true; });
  }
  if (overlay) {
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) overlay.hidden = true;
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') overlay.hidden = true;
    });
  }

  // ===== Affichage conditionnel région/département =====
  if (district && regionStep && deptStep) {
    district.addEventListener('change', function () {
      const isAutonome = district.selectedOptions[0]
        && district.selectedOptions[0].dataset.type === 'autonome';
      const hasDistrict = district.value !== '';

      if (regionStep) regionStep.hidden = !hasDistrict || isAutonome;
      if (deptStep)   deptStep.hidden   = !hasDistrict;

      if (regionSel) regionSel.value = '';
      if (deptSel)   deptSel.value   = '';
    });
  }

  // ===== Application / suppression des filtres =====
  if (applyBtn) {
    applyBtn.addEventListener('click', function () {
      const url = new URL(window.location.href);
      const map = {
        district_id:    district ? district.value : '',
        region_id:      regionSel ? regionSel.value : '',
        departement_id: deptSel ? deptSel.value : '',
      };
      for (const key in map) {
        if (map[key]) url.searchParams.set(key, map[key]);
        else url.searchParams.delete(key);
      }
      url.searchParams.delete('p');
      window.location.href = url.toString();
    });
  }

  if (clearBtn) {
    clearBtn.addEventListener('click', function () {
      const url = new URL(window.location.href);
      url.searchParams.delete('district_id');
      url.searchParams.delete('region_id');
      url.searchParams.delete('departement_id');
      url.searchParams.delete('p');
      window.location.href = url.toString();
    });
  }