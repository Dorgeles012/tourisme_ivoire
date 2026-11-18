<?php
$payload = ClientApi::data($api->request('GET', 'api/notifications?page=1'));
$rows = is_array($payload['items'] ?? null) ? $payload['items'] : [];
$hasUnread = (bool)array_filter($rows, static fn($n) => empty($n['lu_at']));
?>

<section class="panel notif-panel">
  <header class="panel-head">
    <h2 class="section-title">Toutes vos notifications</h2>
    <?php if ($hasUnread): ?>
      <form method="post" data-confirm="Tout marquer comme lu ? Toutes vos notifications non lues seront marquées comme lues.">
        <?= client_csrf_field() ?>
        <input type="hidden" name="action" value="notification_read_all">
        <button class="btn-read-all" type="submit">Tout marquer comme lu</button>
      </form>
    <?php endif; ?>
  </header>

  <?php if (!$rows): ?>
    <div class="empty">
      <div class="empty-icon">♧</div>
      <h3>Aucune notification pour le moment</h3>
      <p>Les confirmations et informations importantes apparaîtront ici.</p>
    </div>
  <?php else: ?>
    <div class="notif-list">
      <?php foreach ($rows as $n): ?>
        <?php $unread = empty($n['lu_at']); ?>
        <article class="notif-card <?= $unread ? 'is-unread' : '' ?>">
          <div class="notif-body">
            <div class="notif-title-row">
              <h3 class="notif-title"><?= client_h($n['titre'] ?? '') ?></h3>
              <?php if ($unread): ?>
                <span class="badge pending">Non lue</span>
              <?php endif; ?>
            </div>
            <p class="notif-content"><?= nl2br(client_h($n['contenu'] ?? '')) ?></p>
            <div class="notif-meta"><?= client_h(client_format_date($n['created_at'] ?? null)) ?></div>
          </div>

          <?php if ($unread): ?>
            <form method="post" class="notif-action">
              <?= client_csrf_field() ?>
              <input type="hidden" name="action" value="notification_read">
              <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
              <button class="btn-read" type="submit">Marquer comme lue</button>
            </form>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<style>
  *,
  *::before,
  *::after {
    box-sizing: border-box;
  }

  .notif-panel {
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

  .notif-panel .panel-head {
    margin-bottom: 0.9rem;
    padding-bottom: 0.65rem;
    border-bottom: 1px solid #f3ebe4;
  }

  .notif-panel .panel-head form { margin: 0; }

  .btn-read-all {
    padding: 0.4rem 0.7rem;
    border: 1px solid #f97316;
    border-radius: 8px;
    background: transparent;
    color: #ea580c;
    font: inherit;
    font-size: 0.7rem;
    font-weight: 600;
    cursor: pointer;
  }

  .notif-panel .section-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: #1c1410;
    margin: 0;
  }

  .notif-list {
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
    width: 100%;
    max-width: 100%;
  }

  .notif-card {
    padding: 0.8rem 0.9rem;
    background: #fff8f3;
    border: 1px solid #f1e4d8;
    border-radius: 12px;
    transition: all 0.2s ease;
    min-width: 0;
    overflow: hidden;
  }

  .notif-card:hover {
    background: #fff;
    border-color: #fcd9b5;
    box-shadow: 0 4px 12px -4px rgba(234, 88, 12, 0.20);
  }

  .notif-card.is-unread {
    background: #fff;
    border-left: 3px solid #f97316;
  }

  .notif-body {
    min-width: 0;
  }

  .notif-title-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    margin-bottom: 0.3rem;
    flex-wrap: wrap;
  }

  .notif-title {
    font-size: 0.82rem;
    font-weight: 700;
    color: #1c1410;
    margin: 0;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .notif-content {
    font-size: 0.75rem;
    color: #8a7266;
    line-height: 1.45;
    margin: 0 0 0.4rem;
    word-wrap: break-word;
    overflow-wrap: break-word;
  }

  .notif-meta {
    font-size: 0.65rem;
    color: #8a7266;
    letter-spacing: 0.02em;
  }

  .notif-action {
    margin-top: 0.6rem;
    padding-top: 0.55rem;
    border-top: 1px dashed #f1e4d8;
  }

  .btn-read {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.4rem 0.85rem;
    font-size: 0.7rem;
    font-weight: 600;
    font-family: inherit;
    color: #ea580c;
    background: transparent;
    border: 1.5px solid #f97316;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .btn-read:hover {
    background: rgba(249, 115, 22, 0.08);
    transform: translateY(-1px);
  }

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

  .badge.pending {
    background: rgba(249, 115, 22, 0.12);
    color: #ea580c;
  }

  .empty {
    text-align: center;
    padding: 1.2rem 0.75rem;
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
    .notif-panel { padding: 0.95rem 0.85rem 0.8rem; }
    .notif-card { padding: 0.7rem 0.8rem; }
    .notif-title { font-size: 0.78rem; }
    .notif-content { font-size: 0.72rem; }
    .btn-read { font-size: 0.68rem; padding: 0.35rem 0.75rem; }
  }
</style>