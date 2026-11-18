<?php
$reservations = ClientApi::data($api->request('GET', 'api/reservations'));
$reservations = is_array($reservations) ? $reservations : [];
$notifs = ClientApi::data($api->request('GET', 'api/notifications?page=1&per_page=5'));
$notifications = is_array($notifs['items'] ?? null) ? $notifs['items'] : [];
$featured = ClientApi::data($api->request('GET', 'api/destinations/featured'));
$featured = is_array($featured) ? $featured : [];
$active = count(array_filter($reservations, static fn($r) => in_array($r['statut'] ?? '', ['demande','confirmee'], true)));
$unread = count(array_filter($notifications, static fn($n) => empty($n['lu_at'])));
?>

<section class="dash-stats">
  <article class="stat-card">
    <div class="stat-icon">▤</div>
    <div class="stat-body">
      <div class="stat-label">Réservations actives</div>
      <div class="stat-value"><?= $active ?></div>
    </div>
  </article>

  <article class="stat-card">
    <div class="stat-icon">♧</div>
    <div class="stat-body">
      <div class="stat-label">Notifications non lues</div>
      <div class="stat-value"><?= $unread ?></div>
    </div>
  </article>
</section>

<section class="panel">
  <header class="panel-head"><h2 class="section-title">Explorer Tourisme Ivoire</h2><a class="text-link" href="?page=explore">Toutes les destinations →</a></header>
  <div class="feature-grid">
    <?php foreach (array_slice($featured, 0, 4) as $destination): ?><article class="feature-card"><?php if (!empty($destination['image_principale'])): ?><img src="<?= client_h($destination['image_principale']) ?>" alt="<?= client_h($destination['nom'] ?? '') ?>" loading="lazy"><?php endif; ?><div class="feature-card-body"><span class="eyebrow"><?= client_h($destination['localite'] ?? '') ?></span><h2><?= client_h($destination['nom'] ?? '') ?></h2><a class="btn" href="?page=destination&amp;id=<?= (int)$destination['id'] ?>">Découvrir</a></div></article><?php endforeach; ?>
  </div>
  <div class="feature-actions"><a class="btn btn-quiet" href="?page=transport">Trouver un transport</a><a class="btn btn-quiet" href="?page=heritage">Patrimoine</a><a class="btn btn-quiet" href="?page=quizzes">Quiz</a><a class="btn btn-quiet" href="?page=videos">Vidéos</a></div>
</section>

<section class="dash-grid">

  <article class="panel">
    <header class="panel-head">
      <h2 class="section-title">Réservations récentes</h2>
      <a class="text-link" href="?page=reservations">Tout voir →</a>
    </header>

    <?php if (!$reservations): ?>
      <div class="empty">
        <div class="empty-icon">✦</div>
        <h3>Votre prochain voyage vous attend</h3>
        <a class="btn" href="?page=help">Découvrir comment réserver</a>
      </div>
    <?php else: ?>
      <div class="list">
        <?php foreach (array_slice($reservations, 0, 3) as $r): ?>
          <a class="list-row" href="?page=reservations&amp;id=<?= (int)$r['id'] ?>">
            <div class="row-content">
              <div class="row-title"><?= client_h($r['libelle_snapshot'] ?? $r['reference'] ?? 'Réservation') ?></div>
              <div class="row-meta"><?= client_format_date($r['created_at'] ?? null) ?></div>
            </div>
            <?= client_status_badge((string)($r['statut'] ?? 'demande')) ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </article>

  <article class="panel">
    <header class="panel-head">
      <h2 class="section-title">Notifications</h2>
      <a class="text-link" href="?page=notifications">Voir tout →</a>
    </header>

    <?php if (!$notifications): ?>
      <div class="empty">
        <div class="empty-icon">♧</div>
        <h3>Tout est calme</h3>
      </div>
    <?php else: ?>
      <div class="list">
        <?php foreach (array_slice($notifications, 0, 3) as $n): ?>
          <div class="list-row">
            <div class="row-content">
              <div class="row-title"><?= client_h($n['titre'] ?? '') ?></div>
            </div>
            <?php if (empty($n['lu_at'])): ?>
              <span class="badge pending">Nouveau</span>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </article>

</section>

<style>
  :root {
    --dash-primary: #f97316;
    --dash-primary-hover: #ea580c;
    --dash-primary-soft: rgba(249, 115, 22, 0.10);
    --dash-bg: #ffffff;
    --dash-surface: #fff8f3;
    --dash-border: #f1e4d8;
    --dash-text: #1c1410;
    --dash-muted: #8a7266;
    --dash-radius: 14px;
    --dash-shadow: 0 4px 18px -6px rgba(234, 88, 12, 0.15);
    --dash-font: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  *,
  *::before,
  *::after {
    box-sizing: border-box;
  }

  .dash-stats {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 240px));
    gap: 0.75rem;
    margin-bottom: 1.25rem;
    font-family: var(--dash-font);
    width: 100%;
    max-width: 100%;
  }

  .stat-card {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    background: var(--dash-bg);
    border: 1px solid var(--dash-border);
    border-radius: var(--dash-radius);
    padding: 0.75rem 0.85rem;
    box-shadow: var(--dash-shadow);
    transition: all 0.25s ease;
    min-width: 0;
    max-width: 100%;
  }

  .stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px -6px rgba(234, 88, 12, 0.25);
    border-color: #fcd9b5;
  }

  .stat-icon {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    background: var(--dash-primary-soft);
    color: var(--dash-primary-hover);
    border-radius: 9px;
    font-size: 1rem;
    font-weight: 700;
  }

  .stat-body {
    flex: 1;
    min-width: 0;
  }

  .stat-label {
    font-size: 0.65rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--dash-muted);
    margin-bottom: 0.1rem;
  }

  .stat-value {
    font-size: 1.2rem;
    font-weight: 800;
    line-height: 1.1;
    color: var(--dash-text);
  }

  .dash-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 340px));
    gap: 0.85rem;
    font-family: var(--dash-font);
    width: 100%;
    max-width: 100%;
  }

  .panel {
    background: var(--dash-bg);
    border: 1px solid var(--dash-border);
    border-radius: var(--dash-radius);
    padding: 0.9rem 0.9rem 0.75rem;
    box-shadow: var(--dash-shadow);
    min-width: 0;
    max-width: 100%;
  }

  .panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.65rem;
    padding-bottom: 0.55rem;
    border-bottom: 1px solid #f3ebe4;
  }

  .section-title {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--dash-text);
    margin: 0;
  }

  .text-link {
    font-size: 0.68rem;
    font-weight: 600;
    color: var(--dash-primary);
    text-decoration: none;
    transition: color 0.2s ease;
    white-space: nowrap;
  }

  .text-link:hover {
    color: var(--dash-primary-hover);
  }

  .list {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    min-width: 0;
  }

  .list-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.6rem;
    padding: 0.55rem 0.65rem;
    background: var(--dash-surface);
    border: 1px solid transparent;
    border-radius: 9px;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s ease;
    min-width: 0;
    max-width: 100%;
  }

  .list-row:hover {
    background: #fff;
    border-color: var(--dash-border);
    transform: translateX(2px);
  }

  .row-content {
    min-width: 0;
    flex: 1;
    overflow: hidden;
  }

  .row-title {
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--dash-text);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .row-meta {
    font-size: 0.66rem;
    color: var(--dash-muted);
    margin-top: 0.08rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .badge {
    display: inline-flex;
    align-items: center;
    padding: 0.15rem 0.45rem;
    font-size: 0.58rem;
    font-weight: 700;
    border-radius: 999px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    white-space: nowrap;
    flex-shrink: 0;
  }

  .badge.pending {
    background: var(--dash-primary-soft);
    color: var(--dash-primary-hover);
  }

  .empty {
    text-align: center;
    padding: 0.9rem 0.6rem;
  }

  .empty-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    margin-bottom: 0.5rem;
    background: var(--dash-primary-soft);
    color: var(--dash-primary-hover);
    border-radius: 50%;
    font-size: 1rem;
  }

  .empty h3 {
    font-size: 0.8rem;
    font-weight: 700;
    color: var(--dash-text);
    margin: 0 0 0.5rem;
  }

  .empty .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.45rem 0.9rem;
    background: linear-gradient(135deg, #fb923c, #ea580c);
    color: #fff;
    font-size: 0.72rem;
    font-weight: 600;
    border: none;
    border-radius: 8px;
    text-decoration: none;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(249, 115, 22, 0.35);
    transition: all 0.2s ease;
  }

  .empty .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(234, 88, 12, 0.45);
  }

  @media (max-width: 768px) {
    .dash-grid {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 520px) {
    .dash-stats {
      grid-template-columns: 1fr;
      gap: 0.55rem;
    }

    .stat-card {
      padding: 0.7rem 0.8rem;
    }

    .stat-value {
      font-size: 1.1rem;
    }

    .panel {
      padding: 0.8rem 0.8rem 0.65rem;
    }
  }
</style>
