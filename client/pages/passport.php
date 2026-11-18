<section class="panel visits-panel">
  <header class="panel-head">
    <h2 class="section-title">Visites enregistrées</h2>
  </header>

  <?php if (is_array($visits) && $visits): ?>
    <div class="visits-list">
      <?php foreach ($visits as $visit): ?>
        <a class="visit-row" href="?page=destination&amp;id=<?= (int)$visit['destination_id'] ?>">
          <div class="visit-row-content">
            <div class="visit-row-title"><?= client_h($visit['nom'] ?? '') ?></div>
            <div class="visit-row-meta"><?= client_h($visit['localite'] ?? '') ?></div>
          </div>
          <div class="visit-row-date"><?= client_h($visit['visite_le'] ?? '') ?></div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="empty">
      <div class="empty-icon">◷</div>
      <h3>Aucune visite enregistrée</h3>
      <p>Ouvrez une destination et sélectionnez « J’ai visité ce lieu ».</p>
    </div>
  <?php endif; ?>
</section>

<style>
  *,
  *::before,
  *::after {
    box-sizing: border-box;
  }

  .visits-panel {
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

  .visits-panel .panel-head {
    margin-bottom: 0.85rem;
    padding-bottom: 0.65rem;
    border-bottom: 1px solid #f3ebe4;
  }

  .visits-panel .section-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: #1c1410;
    margin: 0;
  }

  .visits-list {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    width: 100%;
    max-width: 100%;
  }

  .visit-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.7rem;
    padding: 0.65rem 0.8rem;
    background: #fff8f3;
    border: 1px solid transparent;
    border-radius: 10px;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s ease;
    min-width: 0;
  }

  .visit-row:hover {
    background: #fff;
    border-color: #fcd9b5;
    transform: translateX(2px);
    box-shadow: 0 4px 12px -4px rgba(234, 88, 12, 0.20);
  }

  .visit-row-content {
    min-width: 0;
    flex: 1;
    overflow: hidden;
  }

  .visit-row-title {
    font-size: 0.82rem;
    font-weight: 600;
    color: #1c1410;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .visit-row-meta {
    font-size: 0.68rem;
    color: #8a7266;
    margin-top: 0.1rem;
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

  .empty {
    text-align: center;
    padding: 1.1rem 0.75rem;
  }

  .empty-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    margin-bottom: 0.6rem;
    background: rgba(249, 115, 22, 0.10);
    color: #ea580c;
    border-radius: 50%;
    font-size: 1.15rem;
  }

  .empty h3 {
    font-size: 0.88rem;
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

  @media (max-width: 520px) {
    .visits-panel { padding: 0.95rem 0.85rem 0.8rem; }
    .visit-row { padding: 0.55rem 0.7rem; }
    .visit-row-title { font-size: 0.78rem; }
    .visit-row-meta { font-size: 0.65rem; }
    .visit-row-date { font-size: 0.66rem; }
  }
</style>