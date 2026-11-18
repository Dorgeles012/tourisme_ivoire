<?php
// ===== Chargement des données =====
$items   = ClientApi::data($api->request('GET', 'api/patrimoines'));
$virtual = ClientApi::data($api->request('GET', 'api/visites-virtuelles'));
$items   = is_array($items) ? $items : [];
$virtual = is_array($virtual) ? $virtual : [];

// ===== Filtre catégorie (équivalent _filter) =====
$heritageCategories = ['Tout', 'Culturel', 'Historique', 'Naturel', 'Religieux'];
$selectedCategory   = (string)($_GET['categorie'] ?? 'Tout');
if (!in_array($selectedCategory, $heritageCategories, true)) {
    $selectedCategory = 'Tout';
}

$heritage = $selectedCategory === 'Tout'
    ? $items
    : array_values(array_filter($items, static fn($item) =>
        mb_strtolower(trim((string)($item['categorie'] ?? '')))
        === mb_strtolower($selectedCategory)
    ));

// ===== Aucun contenu du tout → EmptyView global =====
$hasContent = (bool)$heritage || (bool)$virtual;

// ===== Helper : valide une URL de visite virtuelle =====
$isValidUrl = static function (string $url): bool {
    $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
    return in_array($scheme, ['http', 'https'], true);
};
?>

<?php if (!$hasContent): ?>
  <!-- ===== EmptyView global ===== -->
  <section class="panel empty-panel">
    <div class="empty">
      <div class="empty-icon">🏛️</div>
      <h3>Aucun contenu publié</h3>
      <p>Aucun contenu culturel ou média virtuel vérifié et publié.</p>
    </div>
  </section>
<?php else: ?>

  <!-- ===== Filtres catégories ===== -->
  <nav class="heritage-filters" aria-label="Filtrer le patrimoine">
    <?php foreach ($heritageCategories as $category): ?>
      <?php $isActive = $category === $selectedCategory; ?>
      <a class="heritage-chip <?= $isActive ? 'is-selected' : '' ?>"
         href="?page=heritage&amp;categorie=<?= rawurlencode($category) ?>"
         <?= $isActive ? 'aria-current="true"' : '' ?>>
        <?php if ($isActive): ?><span class="chip-check" aria-hidden="true">✓</span><?php endif; ?>
        <?= client_h($category) ?>
      </a>
    <?php endforeach; ?>
  </nav>

  <!-- ===== Culture et patrimoine ===== -->
  <?php if ($heritage): ?>
    <section class="heritage-section">
      <header class="section-header">
        <h2 class="section-title">Culture et patrimoine</h2>
        <span class="section-count"><?= count($heritage) ?></span>
      </header>

      <div class="heritage-list">
        <?php foreach ($heritage as $item): ?>
          <article class="heritage-card">
            <details class="heritage-details">
              <summary class="heritage-summary">
                <span class="heritage-leading" aria-hidden="true">🏛️</span>
                <span class="heritage-summary-text">
                  <span class="heritage-title"><?= client_h($item['titre'] ?? '') ?></span>
                  <span class="heritage-meta">
                    <?= client_h($item['localite'] ?? 'Côte d\'Ivoire') ?>
                    <?php if (!empty($item['periode'])): ?>
                      • <?= client_h($item['periode']) ?>
                    <?php endif; ?>
                  </span>
                </span>
                <span class="heritage-chevron" aria-hidden="true">▾</span>
              </summary>

              <div class="heritage-body">
                <p class="heritage-content"><?= nl2br(client_h($item['contenu'] ?? '')) ?></p>

                <?php if (!empty($user)): ?>
                  <form method="post" class="heritage-action">
                    <?= client_csrf_field() ?>
                    <input type="hidden" name="action" value="heritage_view">
                    <input type="hidden" name="id" value="<?= (int)($item['id'] ?? 0) ?>">
                    <button class="btn-heritage" type="submit">
                      ✓ Marquer comme consulté
                    </button>
                  </form>
                <?php endif; ?>
              </div>
            </details>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <!-- ===== Expériences virtuelles ===== -->
  <?php if ($virtual): ?>
    <section class="heritage-section virtual-section">
      <header class="section-header">
        <h2 class="section-title">Expériences virtuelles</h2>
        <span class="section-count"><?= count($virtual) ?></span>
      </header>

      <div class="virtual-list">
        <?php foreach ($virtual as $visit): ?>
          <?php
            $visitUrl = trim((string)($visit['url'] ?? ''));
            if (!$isValidUrl($visitUrl)) continue;
          ?>
          <a class="virtual-row"
             href="<?= client_h($visitUrl) ?>"
             target="_blank"
             rel="noopener noreferrer">
            <span class="virtual-icon" aria-hidden="true">🌐</span>
            <span class="virtual-row-content">
              <span class="virtual-row-title"><?= client_h($visit['titre'] ?? '') ?></span>
              <span class="virtual-row-meta">
                <?php if (!empty($visit['type_visite'])): ?>
                  <?= client_h($visit['type_visite']) ?>
                <?php endif; ?>
                <?php if (!empty($visit['patrimoine']) || !empty($visit['destination'])): ?>
                  • <?= client_h($visit['patrimoine'] ?? $visit['destination']) ?>
                <?php endif; ?>
              </span>
            </span>
            <span class="virtual-row-cta">Ouvrir ↗</span>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <!-- ===== Bandeau info AR/3D ===== -->
  <aside class="heritage-info" role="note">
    <span class="heritage-info-icon" aria-hidden="true">ℹ️</span>
    <p>Le scan AR et la comparaison avant/après nécessitent des contenus 3D et un fournisseur compatible.</p>
  </aside>

<?php endif; ?>

<style>
  *, *::before, *::after { box-sizing: border-box; }

  /* ===== En-tête de page ===== */
  .page-header {
    margin-bottom: 0.85rem;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  .page-title {
    font-size: 1.4rem;
    font-weight: 900;
    letter-spacing: -0.6px;
    color: #1c1410;
    margin: 0 0 0.25rem;
  }

  .page-subtitle {
    font-size: 0.8rem;
    color: #8a7266;
    margin: 0;
  }

  /* ===== Filtres ===== */
  .heritage-filters {
    display: flex;
    gap: 0.5rem;
    margin: 0 0 1.15rem;
    overflow-x: auto;
    padding: 0.15rem 0.1rem 0.55rem;
    scrollbar-width: thin;
  }

  .heritage-filters::-webkit-scrollbar { height: 4px; }
  .heritage-filters::-webkit-scrollbar-thumb {
    background: rgba(0,0,0,0.12);
    border-radius: 4px;
  }

  .heritage-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    flex: 0 0 auto;
    padding: 0.5rem 0.95rem;
    border: 1px solid #f1e4d8;
    border-radius: 999px;
    background: #fff;
    color: #1c1410;
    font-size: 0.78rem;
    font-weight: 600;
    text-decoration: none;
    white-space: nowrap;
    transition: all 0.2s ease;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  .heritage-chip:hover {
    border-color: #fcd9b5;
    background: #fff8f3;
  }

  .heritage-chip.is-selected {
    border-color: #f97316;
    background: rgba(249, 115, 22, 0.12);
    color: #ea580c;
    font-weight: 700;
  }

  .chip-check {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: #f97316;
    color: #fff;
    font-size: 0.6rem;
    font-weight: 800;
    line-height: 1;
  }

  /* ===== Section header ===== */
  .heritage-section {
    margin-bottom: 1.25rem;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  .virtual-section { margin-bottom: 1rem; }

  .section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding-bottom: 0.55rem;
    margin-bottom: 0.65rem;
    border-bottom: 1px solid #f3ebe4;
  }

  .section-title {
    font-size: 0.92rem;
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

  /* ===== Cartes patrimoine (ExpansionTile équivalent) ===== */
  .heritage-list {
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
  }

  .heritage-card {
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 12px;
    box-shadow: 0 4px 18px -6px rgba(234, 88, 12, 0.15);
    overflow: hidden;
    transition: box-shadow 0.2s ease, border-color 0.2s ease;
  }

  .heritage-card:hover {
    border-color: #fcd9b5;
    box-shadow: 0 6px 22px -6px rgba(234, 88, 12, 0.20);
  }

  .heritage-details { width: 100%; }

  .heritage-summary {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.85rem 0.95rem;
    cursor: pointer;
    list-style: none;
    user-select: none;
    transition: background 0.2s ease;
  }

  .heritage-summary::-webkit-details-marker { display: none; }
  .heritage-summary:hover { background: #fffaf5; }

  .heritage-leading {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: rgba(249, 115, 22, 0.10);
    color: #f97316;
    font-size: 1.05rem;
  }

  .heritage-summary-text {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
  }

  .heritage-title {
    font-size: 0.92rem;
    font-weight: 700;
    color: #1c1410;
    line-height: 1.25;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .heritage-meta {
    font-size: 0.72rem;
    color: #8a7266;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .heritage-chevron {
    flex-shrink: 0;
    font-size: 1rem;
    color: #b8a598;
    transition: transform 0.25s ease;
  }

  .heritage-details[open] .heritage-chevron {
    transform: rotate(180deg);
    color: #f97316;
  }

  .heritage-body {
    padding: 0.25rem 0.95rem 0.95rem;
    background: rgba(249, 115, 22, 0.03);
    border-top: 1px solid #f6ece3;
    animation: expandFade 0.2s ease;
  }

  @keyframes expandFade {
    from { opacity: 0; transform: translateY(-3px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  .heritage-content {
    font-size: 0.82rem;
    color: #4a3f38;
    line-height: 1.6;
    margin: 0.5rem 0 0.75rem;
    overflow-wrap: break-word;
  }

  .heritage-action { margin-top: 0.5rem; }

  .btn-heritage {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    width: 100%;
    padding: 0.6rem 0.9rem;
    font-size: 0.76rem;
    font-weight: 700;
    font-family: inherit;
    color: #ea580c;
    background: transparent;
    border: 1.5px solid #f97316;
    border-radius: 9px;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .btn-heritage:hover {
    background: rgba(249, 115, 22, 0.08);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px -4px rgba(234, 88, 12, 0.25);
  }

  /* ===== Visites virtuelles ===== */
  .virtual-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .virtual-row {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    padding: 0.7rem 0.85rem;
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 12px;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s ease;
    min-width: 0;
    box-shadow: 0 3px 14px -6px rgba(234, 88, 12, 0.12);
  }

  .virtual-row:hover {
    border-color: #fcd9b5;
    transform: translateX(2px);
    box-shadow: 0 6px 18px -6px rgba(234, 88, 12, 0.22);
  }

  .virtual-icon {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    border-radius: 10px;
    background: rgba(22, 160, 133, 0.10);
    color: #16a085;
    font-size: 1.1rem;
  }

  .virtual-row-content {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    overflow: hidden;
  }

  .virtual-row-title {
    font-size: 0.85rem;
    font-weight: 700;
    color: #1c1410;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .virtual-row-meta {
    font-size: 0.7rem;
    color: #8a7266;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .virtual-row-cta {
    flex-shrink: 0;
    font-size: 0.72rem;
    font-weight: 700;
    color: #16a085;
    white-space: nowrap;
    transition: color 0.2s ease;
  }

  .virtual-row:hover .virtual-row-cta { color: #0e7d6b; }

  /* ===== Bandeau info ===== */
  .heritage-info {
    display: flex;
    gap: 0.7rem;
    align-items: flex-start;
    padding: 0.85rem 1rem;
    background: rgba(22, 160, 133, 0.07);
    border: 1px solid rgba(22, 160, 133, 0.2);
    border-radius: 12px;
    margin-bottom: 1rem;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  .heritage-info-icon {
    flex-shrink: 0;
    font-size: 1rem;
    line-height: 1.4;
  }

  .heritage-info p {
    font-size: 0.76rem;
    color: #4a3f38;
    line-height: 1.5;
    margin: 0;
  }

  /* ===== États vides ===== */
  .panel, .empty-panel {
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 14px;
    padding: 1.2rem 1rem;
    box-shadow: 0 4px 18px -6px rgba(234, 88, 12, 0.15);
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  .empty {
    text-align: center;
    padding: 1rem 0.5rem;
  }

  .empty-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 52px;
    height: 52px;
    margin-bottom: 0.65rem;
    background: rgba(249, 115, 22, 0.10);
    color: #ea580c;
    border-radius: 50%;
    font-size: 1.4rem;
  }

  .empty h3 {
    font-size: 0.95rem;
    font-weight: 700;
    color: #1c1410;
    margin: 0 0 0.3rem;
  }

  .empty p {
    font-size: 0.78rem;
    color: #8a7266;
    margin: 0;
    line-height: 1.4;
  }

  /* ===== Responsive ===== */
  @media (max-width: 640px) {
    .page-title { font-size: 1.2rem; }
    .heritage-summary { padding: 0.75rem 0.8rem; gap: 0.6rem; }
    .heritage-title { font-size: 0.86rem; }
    .heritage-meta { font-size: 0.68rem; }
    .heritage-body { padding: 0.2rem 0.8rem 0.85rem; }
    .heritage-content { font-size: 0.78rem; }
    .virtual-row { padding: 0.6rem 0.7rem; }
    .virtual-icon { width: 38px; height: 38px; font-size: 1rem; }
  }

  @media (prefers-reduced-motion: reduce) {
    *, .heritage-chevron, .virtual-row { transition: none !important; animation: none !important; }
  }
</style>