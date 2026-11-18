<?php
$visits = ClientApi::data($api->request('GET', 'api/passport/visits'));
$reservations = ClientApi::data($api->request('GET', 'api/reservations'));
?>

<section class="hist-grid">

  <article class="panel hist-panel">
    <header class="panel-head">
      <h2 class="section-title">Destinations visitées</h2>
    </header>

    <?php if (!$visits): ?>
      <div class="empty">
        <div class="empty-icon">◷</div>
        <h3>Votre carnet est encore vierge</h3>
        <p>Les visites enregistrées dans votre passeport apparaîtront ici.</p>
      </div>
    <?php else: ?>
      <div class="hist-list">
        <?php foreach ($visits as $v): ?>
          <div class="hist-row">
            <div class="hist-row-content">
              <div class="hist-row-title"><?= client_h($v['nom'] ?? $v['destination'] ?? 'Visite') ?></div>
              <div class="hist-row-meta"><?= client_h(client_format_date($v['visite_le'] ?? null)) ?></div>
            </div>
            <span class="badge good">Visitée</span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </article>

  <article class="panel hist-panel">
    <header class="panel-head">
      <h2 class="section-title">Activité des réservations</h2>
    </header>

    <?php if (!$reservations): ?>
      <div class="empty">
        <div class="empty-icon">▤</div>
        <h3>Aucune activité</h3>
        <p>Les changements de statut de vos réservations seront listés ici.</p>
      </div>
    <?php else: ?>
      <div class="hist-list">
        <?php foreach ($reservations as $r): ?>
          <a class="hist-row hist-row-link" href="?page=reservations&amp;id=<?= (int)$r['id'] ?>">
            <div class="hist-row-content">
              <div class="hist-row-title">
                <?= client_h($r['reference'] ?? '') ?> · <?= client_h($r['libelle_snapshot'] ?? '') ?>
              </div>
              <div class="hist-row-meta"><?= client_h(client_format_date($r['created_at'] ?? null)) ?></div>
            </div>
            <?= client_status_badge((string)($r['statut'] ?? '')) ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </article>

</section>

<style>
  *,
  *::before,
  *::after {
    box-sizing: border-box;
  }

  .hist-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
    width: 100%;
    max-width: 100%;
    min-width: 0;
  }

  .hist-panel {
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

  .hist-panel .panel-head {
    margin-bottom: 0.85rem;
    padding-bottom: 0.65rem;
    border-bottom: 1px solid #f3ebe4;
  }

  .hist-panel .section-title {
    font-size: 0.92rem;
    font-weight: 700;
    color: #1c1410;
    margin: 0;
  }

  .hist-list {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    width: 100%;
    max-width: 100%;
  }

  .hist-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.6rem;
    padding: 0.6rem 0.75rem;
    background: #fff8f3;
    border: 1px solid transparent;
    border-radius: 10px;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s ease;
    min-width: 0;
  }

  .hist-row:hover {
    background: #fff;
    border-color: #f1e4d8;
  }

  .hist-row-link:hover {
    transform: translateX(2px);
    border-color: #fcd9b5;
    box-shadow: 0 4px 12px -4px rgba(234, 88, 12, 0.20);
  }

  .hist-row-content {
    min-width: 0;
    flex: 1;
    overflow: hidden;
  }

  .hist-row-title {
    font-size: 0.78rem;
    font-weight: 600;
    color: #1c1410;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .hist-row-meta {
    font-size: 0.68rem;
    color: #8a7266;
    margin-top: 0.1rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .badge {
    display: inline-flex;
    align-items: center;
    padding: 0.18rem 0.5rem;
    font-size: 0.6rem;
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

  .badge.pending {
    background: rgba(249, 115, 22, 0.10);
    color: #ea580c;
  }

  .badge.muted {
    background: #f3f4f6;
    color: #6b7280;
  }

  .empty {
    text-align: center;
    padding: 1.1rem 0.75rem;
  }

  .empty-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    margin-bottom: 0.55rem;
    background: rgba(249, 115, 22, 0.10);
    color: #ea580c;
    border-radius: 50%;
    font-size: 1.1rem;
  }

  .empty h3 {
    font-size: 0.85rem;
    font-weight: 700;
    color: #1c1410;
    margin: 0 0 0.3rem;
  }

  .empty p {
    font-size: 0.75rem;
    color: #8a7266;
    margin: 0;
    line-height: 1.4;
  }

  @media (max-width: 768px) {
    .hist-grid {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 520px) {
    .hist-panel { padding: 0.95rem 0.85rem 0.8rem; }
    .hist-row { padding: 0.55rem 0.65rem; }
    .hist-row-title { font-size: 0.75rem; }
    .hist-row-meta { font-size: 0.65rem; }
  }
</style>