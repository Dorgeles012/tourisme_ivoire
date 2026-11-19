<?php
$payload = ClientApi::data($api->request('GET', 'api/notifications?page=1'));
$rows = is_array($payload['items'] ?? null) ? $payload['items'] : [];
$hasUnread = (bool)array_filter($rows, static fn($n) => empty($n['lu_at']));
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

  /* ===== Panneau ===== */
  .notif-panel {
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
  .notif-panel .panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.6rem;
    margin-bottom: 0.75rem;
    padding-bottom: 0.55rem;
    border-bottom: 1px solid #f3ebe4;
  }
  .notif-panel .panel-head form { margin: 0; }
  .section-title {
    font-size: 0.85rem;
    font-weight: 700;
    color: #1c1410;
    margin: 0;
  }

  /* ===== Bouton tout marquer ===== */
  .btn-read-all {
    padding: 0.35rem 0.65rem;
    border: 1px solid #f97316;
    border-radius: 7px;
    background: transparent;
    color: #ea580c;
    font: inherit;
    font-size: 0.65rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s ease;
    white-space: nowrap;
  }
  .btn-read-all:hover { background: rgba(249, 115, 22, 0.08); }

  /* ===== Liste ===== */
  .notif-list {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
    width: 100%;
    max-width: 100%;
  }
  .notif-card {
    padding: 0.7rem 0.8rem;
    background: #fff8f3;
    border: 1px solid #f1e4d8;
    border-radius: 10px;
    transition: background 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
    min-width: 0;
    overflow: hidden;
  }
  .notif-card:hover {
    background: #fff;
    border-color: #fcd9b5;
    box-shadow: 0 4px 12px -4px rgba(234, 88, 12, 0.15);
  }
  .notif-card.is-unread {
    background: #fff;
    border-left: 3px solid #f97316;
  }

  .notif-body { min-width: 0; }
  .notif-title-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.4rem;
    margin-bottom: 0.25rem;
    flex-wrap: wrap;
  }
  .notif-title {
    font-size: 0.75rem;
    font-weight: 700;
    color: #1c1410;
    margin: 0;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .notif-content {
    font-size: 0.68rem;
    color: #8a7266;
    line-height: 1.45;
    margin: 0 0 0.35rem;
    word-wrap: break-word;
    overflow-wrap: break-word;
  }
  .notif-meta {
    font-size: 0.6rem;
    color: #8a7266;
    letter-spacing: 0.02em;
  }

  .notif-action {
    margin-top: 0.5rem;
    padding-top: 0.45rem;
    border-top: 1px dashed #f1e4d8;
  }
  .btn-read {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.35rem 0.7rem;
    font-size: 0.65rem;
    font-weight: 600;
    font-family: inherit;
    color: #ea580c;
    background: transparent;
    border: 1.5px solid #f97316;
    border-radius: 7px;
    cursor: pointer;
    transition: background 0.15s ease, transform 0.15s ease;
  }
  .btn-read:hover {
    background: rgba(249, 115, 22, 0.08);
    transform: translateY(-1px);
  }

  /* ===== Badge ===== */
  .badge {
    display: inline-flex;
    align-items: center;
    padding: 0.12rem 0.45rem;
    font-size: 0.55rem;
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

  /* ===== État vide ===== */
  .empty { text-align: center; padding: 1rem 0.6rem; }
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

  /* ===== Responsive ===== */
  @media (max-width: 520px) {
    .notif-panel { padding: 0.8rem 0.75rem 0.65rem; }
    .notif-card { padding: 0.6rem 0.7rem; }
    .notif-title { font-size: 0.72rem; }
    .notif-content { font-size: 0.65rem; }
    .btn-read { font-size: 0.62rem; padding: 0.3rem 0.6rem; }
    .btn-read-all { font-size: 0.6rem; padding: 0.3rem 0.55rem; }
  }
  @media (prefers-reduced-motion: reduce) {
    *, .notif-card, .btn-read, .btn-read-all { transition: none !important; }
  }
</style>

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
        <?php
          $targetQuery = [];
          parse_str((string)(parse_url((string)($n['cible_url'] ?? ''), PHP_URL_QUERY) ?? ''), $targetQuery);
          $targetPage = (string)($targetQuery['page'] ?? '');
          $targetSlug = (string)($targetQuery['slug'] ?? '');
          $contentHref = in_array($targetPage, ['actualites','promotions','hebergements','activites'], true)
              && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $targetSlug)
              ? '?page=' . rawurlencode($targetPage) . '&slug=' . rawurlencode($targetSlug)
              : null;
        ?>
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
            <?php if (($n['type'] ?? '') === 'nouveau_contenu' && $contentHref !== null): ?>
              <div class="notif-action"><a class="btn-read" href="<?= client_h($contentHref) ?>">Voir le contenu</a></div>
            <?php endif; ?>
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
