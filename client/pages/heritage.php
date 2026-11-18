<?php
$items = ClientApi::data($api->request('GET', 'api/patrimoines'));
$virtual = ClientApi::data($api->request('GET', 'api/visites-virtuelles'));
?>

<section class="heritage-grid">
  <?php foreach (is_array($items) ? $items : [] as $item): ?>
    <article class="heritage-card">
      <div class="heritage-card-body">
        <span class="heritage-eyebrow">
          <?= client_h($item['localite'] ?? '') ?> · <?= client_h($item['periode'] ?? '') ?>
        </span>
        <h2 class="heritage-title"><?= client_h($item['titre'] ?? '') ?></h2>
        <p class="heritage-content"><?= nl2br(client_h($item['contenu'] ?? '')) ?></p>
      </div>

      <form method="post" class="heritage-action">
        <?= client_csrf_field() ?>
        <input type="hidden" name="action" value="heritage_view">
        <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
        <button class="btn-heritage" type="submit">Marquer comme consulté</button>
      </form>
    </article>
  <?php endforeach; ?>
</section>

<section class="panel virtual-panel">
  <header class="panel-head">
    <h2 class="section-title">Visites virtuelles</h2>
  </header>

  <?php if (is_array($virtual) && $virtual): ?>
    <div class="virtual-list">
      <?php foreach ($virtual as $visit): ?>
        <?php $visitUrl = trim((string)($visit['url'] ?? '')); if (!in_array(strtolower((string)parse_url($visitUrl, PHP_URL_SCHEME)), ['http', 'https'], true)) continue; ?>
        <a class="virtual-row"
           href="<?= client_h($visitUrl) ?>"
           target="_blank"
           rel="noopener noreferrer">
          <div class="virtual-row-content">
            <div class="virtual-row-title"><?= client_h($visit['titre'] ?? '') ?></div>
            <div class="virtual-row-meta">
              <?= client_h($visit['destination'] ?? $visit['patrimoine'] ?? '') ?>
              <?php if (!empty($visit['type_visite'])): ?>
                · <?= client_h($visit['type_visite']) ?>
              <?php endif; ?>
            </div>
          </div>
          <span class="virtual-row-cta">Ouvrir ↗</span>
        </a>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="virtual-empty">Aucune visite virtuelle publiée actuellement.</p>
  <?php endif; ?>
</section>

<style>
  *,
  *::before,
  *::after {
    box-sizing: border-box;
  }

  .heritage-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 0.85rem;
    margin-bottom: 1rem;
    width: 100%;
    max-width: 100%;
    min-width: 0;
  }

  .heritage-card {
    display: flex;
    flex-direction: column;
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 14px;
    padding: 1rem 1rem 0.9rem;
    box-shadow: 0 4px 18px -6px rgba(234, 88, 12, 0.15);
    transition: all 0.25s ease;
    min-width: 0;
    overflow: hidden;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  .heritage-card:hover {
    border-color: #fcd9b5;
    box-shadow: 0 8px 24px -6px rgba(234, 88, 12, 0.25);
    transform: translateY(-2px);
  }

  .heritage-card-body {
    flex: 1;
    min-width: 0;
  }

  .heritage-eyebrow {
    display: inline-block;
    font-size: 0.62rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #f97316;
    margin-bottom: 0.35rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 100%;
  }

  .heritage-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: #1c1410;
    margin: 0 0 0.5rem;
    line-height: 1.25;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
  }

  .heritage-content {
    font-size: 0.75rem;
    color: #8a7266;
    line-height: 1.5;
    margin: 0 0 0.85rem;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 4;
    -webkit-box-orient: vertical;
    overflow-wrap: break-word;
    word-wrap: break-word;
  }

  .heritage-action {
    margin-top: auto;
  }

  .btn-heritage {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    padding: 0.55rem 0.9rem;
    font-size: 0.75rem;
    font-weight: 600;
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
  }

  .virtual-panel {
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 14px;
    padding: 1.1rem 1.1rem 0.9rem;
    box-shadow: 0 4px 18px -6px rgba(234, 88, 12, 0.15);
    width: 100%;
    max-width: 100%;
    min-width: 0;
    overflow: hidden;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  .virtual-panel .panel-head {
    margin-bottom: 0.85rem;
    padding-bottom: 0.65rem;
    border-bottom: 1px solid #f3ebe4;
  }

  .virtual-panel .section-title {
    font-size: 0.92rem;
    font-weight: 700;
    color: #1c1410;
    margin: 0;
  }

  .virtual-list {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
    width: 100%;
    max-width: 100%;
  }

  .virtual-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.7rem;
    padding: 0.7rem 0.85rem;
    background: #fff8f3;
    border: 1px solid transparent;
    border-radius: 10px;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s ease;
    min-width: 0;
  }

  .virtual-row:hover {
    background: #fff;
    border-color: #fcd9b5;
    transform: translateX(2px);
    box-shadow: 0 4px 12px -4px rgba(234, 88, 12, 0.20);
  }

  .virtual-row-content {
    min-width: 0;
    flex: 1;
    overflow: hidden;
  }

  .virtual-row-title {
    font-size: 0.82rem;
    font-weight: 700;
    color: #1c1410;
    margin-bottom: 0.15rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .virtual-row-meta {
    font-size: 0.68rem;
    color: #8a7266;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .virtual-row-cta {
    font-size: 0.7rem;
    font-weight: 700;
    color: #f97316;
    white-space: nowrap;
    flex-shrink: 0;
  }

  .virtual-row:hover .virtual-row-cta {
    color: #ea580c;
  }

  .virtual-empty {
    font-size: 0.78rem;
    color: #8a7266;
    margin: 0;
    line-height: 1.4;
    text-align: center;
    padding: 1rem 0.5rem;
  }

  @media (max-width: 640px) {
    .heritage-grid {
      grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
      gap: 0.7rem;
    }

    .heritage-card { padding: 0.85rem 0.85rem 0.8rem; }
    .heritage-title { font-size: 0.88rem; }
    .heritage-content { font-size: 0.72rem; -webkit-line-clamp: 3; }
    .virtual-panel { padding: 0.95rem 0.85rem 0.8rem; }
    .virtual-row { padding: 0.6rem 0.7rem; }
  }

  @media (max-width: 380px) {
    .heritage-grid { grid-template-columns: 1fr; }
  }
</style>