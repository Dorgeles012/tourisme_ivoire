<?php
// ===== Chargement des données =====
$items   = ClientApi::data($api->request('GET', 'api/patrimoines'));
$virtual = ClientApi::data($api->request('GET', 'api/visites-virtuelles'));
$items   = is_array($items) ? $items : [];
$virtual = is_array($virtual) ? $virtual : [];

// ===== Filtre catégorie =====
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

$hasContent = (bool)$heritage || (bool)$virtual;

$isValidUrl = static function (string $url): bool {
    $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
    return in_array($scheme, ['http', 'https'], true);
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

  /* ===== Filtres ===== */
  .heritage-filters {
    display: flex;
    gap: 0.4rem;
    margin: 0 0 0.85rem;
    overflow-x: auto;
    padding: 0.1rem 0.1rem 0.45rem;
    scrollbar-width: thin;
    -webkit-overflow-scrolling: touch;
  }
  .heritage-filters::-webkit-scrollbar { height: 3px; }
  .heritage-filters::-webkit-scrollbar-thumb {
    background: rgba(0,0,0,0.10);
    border-radius: 3px;
  }

  .heritage-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    flex: 0 0 auto;
    padding: 0.4rem 0.8rem;
    border: 1px solid #f1e4d8;
    border-radius: 999px;
    background: #fff;
    color: #1c1410;
    font-size: 0.7rem;
    font-weight: 600;
    text-decoration: none;
    white-space: nowrap;
    transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
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
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #f97316;
    color: #fff;
    font-size: 0.55rem;
    font-weight: 800;
    line-height: 1;
  }

  /* ===== Sections ===== */
  .heritage-section { margin-bottom: 1rem; }
  .virtual-section { margin-bottom: 0.85rem; }

  .section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.6rem;
    padding-bottom: 0.45rem;
    margin-bottom: 0.55rem;
    border-bottom: 1px solid #f3ebe4;
  }
  .section-title {
    font-size: 0.82rem;
    font-weight: 800;
    color: #1c1410;
    margin: 0;
    letter-spacing: -0.2px;
  }
  .section-count {
    font-size: 0.62rem;
    font-weight: 700;
    color: #ea580c;
    background: rgba(249, 115, 22, 0.12);
    padding: 0.12rem 0.5rem;
    border-radius: 999px;
  }

  /* ===== Cartes patrimoine (details/summary) ===== */
  .heritage-list {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
  }
  .heritage-card {
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 10px;
    box-shadow: 0 2px 12px -4px rgba(234, 88, 12, 0.12);
    overflow: hidden;
    transition: box-shadow 0.15s ease, border-color 0.15s ease;
  }
  .heritage-card:hover {
    border-color: #fcd9b5;
    box-shadow: 0 4px 16px -4px rgba(234, 88, 12, 0.18);
  }
  .heritage-details { width: 100%; }

  .heritage-summary {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.7rem 0.8rem;
    cursor: pointer;
    list-style: none;
    user-select: none;
    transition: background 0.15s ease;
  }
  .heritage-summary::-webkit-details-marker { display: none; }
  .heritage-summary:hover { background: #fffaf5; }

  .heritage-leading {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: 8px;
    background: rgba(249, 115, 22, 0.10);
    color: #f97316;
    font-size: 0.9rem;
  }
  .heritage-summary-text {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
  }
  .heritage-title {
    font-size: 0.8rem;
    font-weight: 700;
    color: #1c1410;
    line-height: 1.25;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .heritage-meta {
    font-size: 0.65rem;
    color: #8a7266;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .heritage-chevron {
    flex-shrink: 0;
    font-size: 0.85rem;
    color: #b8a598;
    transition: transform 0.2s ease, color 0.2s ease;
  }
  .heritage-details[open] .heritage-chevron {
    transform: rotate(180deg);
    color: #f97316;
  }

  .heritage-body {
    padding: 0.15rem 0.8rem 0.75rem;
    background: rgba(249, 115, 22, 0.03);
    border-top: 1px solid #f6ece3;
    animation: expandFade 0.18s ease;
  }
  @keyframes expandFade {
    from { opacity: 0; transform: translateY(-2px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  .heritage-content {
    font-size: 0.72rem;
    color: #4a3f38;
    line-height: 1.55;
    margin: 0.4rem 0 0.6rem;
    overflow-wrap: break-word;
  }
  .heritage-action { margin-top: 0.4rem; }

  .btn-heritage {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.3rem;
    width: 100%;
    padding: 0.5rem 0.75rem;
    font-size: 0.68rem;
    font-weight: 700;
    font-family: inherit;
    color: #ea580c;
    background: transparent;
    border: 1.5px solid #f97316;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.15s ease, transform 0.15s ease, box-shadow 0.15s ease;
  }
  .btn-heritage:hover {
    background: rgba(249, 115, 22, 0.08);
    transform: translateY(-1px);
    box-shadow: 0 3px 10px -2px rgba(234, 88, 12, 0.22);
  }

  /* ===== Visites virtuelles ===== */
  .virtual-list {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
  }
  .virtual-row {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.6rem 0.75rem;
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 10px;
    text-decoration: none;
    color: inherit;
    transition: border-color 0.15s ease, transform 0.15s ease, box-shadow 0.15s ease;
    min-width: 0;
    box-shadow: 0 2px 10px -4px rgba(234, 88, 12, 0.1);
  }
  .virtual-row:hover {
    border-color: #fcd9b5;
    transform: translateX(2px);
    box-shadow: 0 4px 14px -4px rgba(234, 88, 12, 0.18);
  }
  .virtual-icon {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: rgba(22, 160, 133, 0.10);
    color: #16a085;
    font-size: 0.95rem;
  }
  .virtual-row-content {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
    overflow: hidden;
  }
  .virtual-row-title {
    font-size: 0.75rem;
    font-weight: 700;
    color: #1c1410;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .virtual-row-meta {
    font-size: 0.62rem;
    color: #8a7266;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .virtual-row-cta {
    flex-shrink: 0;
    font-size: 0.65rem;
    font-weight: 700;
    color: #16a085;
    white-space: nowrap;
    transition: color 0.15s ease;
  }
  .virtual-row:hover .virtual-row-cta { color: #0e7d6b; }

  /* ===== Bandeau info ===== */
  .heritage-info {
    display: flex;
    gap: 0.6rem;
    align-items: flex-start;
    padding: 0.7rem 0.85rem;
    background: rgba(22, 160, 133, 0.07);
    border: 1px solid rgba(22, 160, 133, 0.2);
    border-radius: 10px;
    margin-bottom: 0.85rem;
  }
  .heritage-info-icon {
    flex-shrink: 0;
    font-size: 0.9rem;
    line-height: 1.4;
  }
  .heritage-info p {
    font-size: 0.68rem;
    color: #4a3f38;
    line-height: 1.5;
    margin: 0;
  }

  /* ===== États vides ===== */
  .panel, .empty-panel {
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
    width: 44px;
    height: 44px;
    margin-bottom: 0.5rem;
    background: rgba(249, 115, 22, 0.10);
    color: #ea580c;
    border-radius: 50%;
    font-size: 1.2rem;
  }
  .empty h3 {
    font-size: 0.82rem;
    font-weight: 700;
    color: #1c1410;
    margin: 0 0 0.25rem;
  }
  .empty p {
    font-size: 0.7rem;
    color: #8a7266;
    margin: 0;
    line-height: 1.4;
  }

  /* ===== Responsive ===== */
  @media (max-width: 640px) {
    .heritage-summary { padding: 0.6rem 0.7rem; gap: 0.5rem; }
    .heritage-title { font-size: 0.75rem; }
    .heritage-meta { font-size: 0.6rem; }
    .heritage-body { padding: 0.1rem 0.7rem 0.65rem; }
    .heritage-content { font-size: 0.68rem; }
    .virtual-row { padding: 0.5rem 0.65rem; }
    .virtual-icon { width: 32px; height: 32px; font-size: 0.85rem; }
  }
  @media (prefers-reduced-motion: reduce) {
    *, .heritage-chevron, .virtual-row { transition: none !important; animation: none !important; }
  }
</style>

<?php if (!$hasContent): ?>
  <section class="panel empty-panel">
    <div class="empty">
      <div class="empty-icon">🏛️</div>
      <h3>Aucun contenu publié</h3>
      <p>Aucun contenu culturel ou média virtuel vérifié et publié.</p>
    </div>
  </section>
<?php else: ?>

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

  <aside class="heritage-info" role="note">
    <span class="heritage-info-icon" aria-hidden="true">ℹ️</span>
    <p>Le scan AR et la comparaison avant/après nécessitent des contenus 3D et un fournisseur compatible.</p>
  </aside>

<?php endif; ?>