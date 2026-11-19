<?php
$rows = ClientApi::data($api->request('GET', 'api/favorites'));
$destinations = ClientApi::data($api->request('GET', 'api/destinations?page=1&per_page=50'));
$destinations = is_array($destinations['items'] ?? null) ? $destinations['items'] : [];
$savedIds = array_fill_keys(array_map(static fn($r) => (int)$r['id'], is_array($rows) ? $rows : []), true);
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

  /* ===== Panneaux ===== */
  .fav-panel,
  .fav-discover {
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 12px;
    padding: 0.9rem 0.9rem 0.75rem;
    box-shadow: 0 2px 12px -4px rgba(234, 88, 12, 0.10);
    width: 100%;
    max-width: 100%;
    min-width: 0;
    overflow: hidden;
  }
  .fav-discover { margin-top: 0.85rem; }

  .panel-head {
    margin-bottom: 0.75rem;
    padding-bottom: 0.55rem;
    border-bottom: 1px solid #f3ebe4;
  }
  .section-title {
    font-size: 0.85rem;
    font-weight: 700;
    color: #1c1410;
    margin: 0;
  }

  /* ===== Grille favoris ===== */
  .fav-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 0.6rem;
    width: 100%;
    max-width: 100%;
  }

  .fav-card {
    display: flex;
    flex-direction: column;
    background: #fff8f3;
    border: 1px solid #f1e4d8;
    border-radius: 10px;
    overflow: hidden;
    min-width: 0;
    transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
  }
  .fav-card:hover {
    border-color: #fcd9b5;
    box-shadow: 0 5px 14px -4px rgba(234, 88, 12, 0.18);
    transform: translateY(-2px);
  }

  .fav-card-img {
    width: 100%;
    aspect-ratio: 16 / 10;
    overflow: hidden;
    background: #f1e4d8;
  }
  .fav-card-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.35s ease;
  }
  .fav-card:hover .fav-card-img img { transform: scale(1.04); }

  .fav-card-body {
    padding: 0.6rem 0.7rem 0.4rem;
    flex: 1;
    min-width: 0;
  }
  .fav-card-body h3 {
    font-size: 0.78rem;
    font-weight: 700;
    color: #1c1410;
    margin: 0 0 0.15rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .fav-card-meta {
    font-size: 0.62rem;
    color: #8a7266;
    margin: 0 0 0.35rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .fav-card-desc {
    font-size: 0.65rem;
    color: #8a7266;
    line-height: 1.4;
    margin: 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  .fav-card-action {
    padding: 0 0.7rem 0.6rem;
  }

  /* ===== Bouton retirer ===== */
  .btn-outline {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    padding: 0.4rem 0.7rem;
    font-size: 0.68rem;
    font-weight: 600;
    font-family: inherit;
    color: #ea580c;
    background: transparent;
    border: 1.5px solid #f97316;
    border-radius: 7px;
    cursor: pointer;
    transition: background 0.15s ease;
  }
  .btn-outline:hover {
    background: rgba(249, 115, 22, 0.08);
  }

  /* ===== Liste découvrir ===== */
  .fav-list {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    width: 100%;
    max-width: 100%;
  }

  .fav-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.6rem;
    padding: 0.55rem 0.7rem;
    background: #fff8f3;
    border: 1px solid transparent;
    border-radius: 9px;
    transition: background 0.15s ease, border-color 0.15s ease;
    min-width: 0;
  }
  .fav-row:hover {
    background: #fff;
    border-color: #f1e4d8;
  }

  .fav-row-content {
    min-width: 0;
    flex: 1;
  }
  .fav-row-title {
    font-size: 0.75rem;
    font-weight: 600;
    color: #1c1410;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .fav-row-meta {
    font-size: 0.62rem;
    color: #8a7266;
    margin-top: 0.08rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  /* ===== Bouton ajouter ===== */
  .btn-add {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.35rem 0.75rem;
    font-size: 0.65rem;
    font-weight: 600;
    font-family: inherit;
    color: #fff;
    background: linear-gradient(135deg, #fb923c, #ea580c);
    border: none;
    border-radius: 7px;
    cursor: pointer;
    box-shadow: 0 2px 8px -2px rgba(249, 115, 22, 0.3);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    flex-shrink: 0;
  }
  .btn-add:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px -2px rgba(234, 88, 12, 0.4);
  }

  /* ===== Badge ===== */
  .badge {
    display: inline-flex;
    align-items: center;
    padding: 0.15rem 0.5rem;
    font-size: 0.58rem;
    font-weight: 700;
    border-radius: 999px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    white-space: nowrap;
    flex-shrink: 0;
  }
  .badge.good {
    background: #ecfdf5;
    color: #047857;
  }

  /* ===== État vide ===== */
  .empty {
    text-align: center;
    padding: 1rem 0.6rem;
  }
  .empty-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    margin-bottom: 0.5rem;
    background: rgba(249, 115, 22, 0.10);
    color: #ea580c;
    border-radius: 50%;
    font-size: 1.05rem;
  }
  .empty h3 {
    font-size: 0.8rem;
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
  .fav-empty-text {
    font-size: 0.72rem;
    color: #8a7266;
    margin: 0;
    line-height: 1.4;
  }

  /* ===== Responsive ===== */
  @media (max-width: 520px) {
    .fav-panel,
    .fav-discover {
      padding: 0.8rem 0.75rem 0.65rem;
    }
    .fav-grid {
      grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
      gap: 0.5rem;
    }
    .fav-card-body { padding: 0.55rem 0.6rem 0.35rem; }
    .fav-card-action { padding: 0 0.6rem 0.55rem; }
  }
  @media (max-width: 380px) {
    .fav-grid { grid-template-columns: 1fr; }
  }
  @media (prefers-reduced-motion: reduce) {
    *, .fav-card, .fav-card-img img, .btn-add { transition: none !important; }
  }
</style>

<section class="panel fav-panel">
  <header class="panel-head">
    <h2 class="section-title">Vos destinations enregistrées</h2>
  </header>

  <?php if (!$rows): ?>
    <div class="empty">
      <div class="empty-icon">♡</div>
      <h3>Vos favoris vous attendent</h3>
      <p>Choisissez une destination ci-dessous pour commencer votre liste.</p>
    </div>
  <?php else: ?>
    <div class="fav-grid">
      <?php foreach ($rows as $r): ?>
        <article class="fav-card">
          <?php if (!empty($r['image_principale'])): ?>
            <div class="fav-card-img">
              <img src="<?= client_h($r['image_principale']) ?>"
                   alt="<?= client_h($r['nom'] ?? 'Destination') ?>"
                   loading="lazy">
            </div>
          <?php endif; ?>

          <div class="fav-card-body">
            <h3><?= client_h($r['nom'] ?? '') ?></h3>
            <p class="fav-card-meta">
              <?= client_h($r['localite'] ?? '') ?> · <?= client_h($r['categorie'] ?? '') ?>
            </p>
            <p class="fav-card-desc"><?= client_h(mb_strimwidth((string)($r['description'] ?? ''), 0, 120, '…')) ?></p>
          </div>

          <form method="post" class="fav-card-action">
            <?= client_csrf_field() ?>
            <input type="hidden" name="action" value="favorite_remove">
            <input type="hidden" name="id" value="<?= (int)$r['favori_id'] ?>">
            <button class="btn-outline" type="submit">Retirer</button>
          </form>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<section class="panel fav-discover">
  <header class="panel-head">
    <h2 class="section-title">Découvrir d’autres destinations</h2>
  </header>

  <?php if (!$destinations): ?>
    <p class="fav-empty-text">Aucune destination publiée n’est disponible actuellement.</p>
  <?php else: ?>
    <div class="fav-list">
      <?php foreach ($destinations as $d): ?>
        <div class="fav-row">
          <div class="fav-row-content">
            <div class="fav-row-title"><?= client_h($d['nom'] ?? '') ?></div>
            <div class="fav-row-meta">
              <?= client_h($d['localite'] ?? '') ?> · <?= client_h($d['categorie'] ?? '') ?>
            </div>
          </div>

          <?php if (isset($savedIds[(int)$d['id']])): ?>
            <span class="badge good">Dans vos favoris</span>
          <?php else: ?>
            <form method="post">
              <?= client_csrf_field() ?>
              <input type="hidden" name="action" value="favorite_add">
              <input type="hidden" name="destination_id" value="<?= (int)$d['id'] ?>">
              <button class="btn-add" type="submit">Ajouter</button>
            </form>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>