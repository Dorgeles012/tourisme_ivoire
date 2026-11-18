<?php
$videoId = max(1, (int)($_GET['id'] ?? 0));
$video   = ClientApi::data($api->request('GET', 'api/videos/' . $videoId));
$comments = ClientApi::data($api->request('GET', 'api/videos/' . $videoId . '/comments'));
$comments = is_array($comments) ? $comments : [];
?>

<header class="page-header">
  <a class="back-link" href="?page=videos">← Retour au feed</a>
  <h1 class="page-title"><?= client_h($video['titre'] ?? 'Vidéo') ?></h1>
  <p class="page-subtitle">
    <?= client_h($video['auteur'] ?? '') ?>
    <?php if (!empty($video['destination'])): ?>
      • <?= client_h($video['destination']) ?>
    <?php endif; ?>
  </p>
</header>

<section class="comments-panel">
  <?php if (!$comments): ?>
    <div class="empty">
      <div class="empty-icon">💬</div>
      <h3>Aucun commentaire</h3>
      <p>Soyez le premier à commenter !</p>
    </div>
  <?php else: ?>
    <div class="comments-list">
      <?php foreach ($comments as $c): ?>
        <div class="comment-row">
          <span class="comment-avatar" aria-hidden="true">
            <?= client_h(mb_strtoupper(mb_substr((string)($c['auteur'] ?? '?'), 0, 1))) ?>
          </span>
          <div class="comment-bubble">
            <strong class="comment-author"><?= client_h($c['auteur'] ?? '') ?></strong>
            <p class="comment-text"><?= nl2br(client_h($c['contenu'] ?? '')) ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($user)): ?>
    <form method="post" class="comment-form">
      <?= client_csrf_field() ?>
      <input type="hidden" name="action" value="video_comment">
      <input type="hidden" name="id" value="<?= $videoId ?>">
      <input type="text" name="contenu" maxlength="2000"
             placeholder="Écrire un commentaire…" required
             class="comment-input">
      <button type="submit" class="comment-send" title="Envoyer">➤</button>
    </form>
  <?php else: ?>
    <p class="comment-login">
      <a href="?page=login">Connectez-vous</a> pour commenter.
    </p>
  <?php endif; ?>
</section>

<style>
  .comments-panel {
    max-width: 720px;
    margin: 0 auto;
    padding: 1rem;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }
  .comment-row {
    display: flex;
    gap: 0.65rem;
    margin-bottom: 0.85rem;
    align-items: flex-start;
  }
  .comment-avatar {
    flex-shrink: 0;
    width: 36px; height: 36px;
    display: flex; align-items: center; justify-content: center;
    border-radius: 50%;
    background: rgba(249,115,22,0.15);
    color: #ea580c;
    font-weight: 800;
    font-size: 0.85rem;
  }
  .comment-bubble {
    flex: 1;
    padding: 0.7rem 0.85rem;
    background: #f7f1eb;
    border-radius: 4px 14px 14px 14px;
    min-width: 0;
  }
  .comment-author {
    display: block;
    font-size: 0.78rem;
    font-weight: 700;
    color: #1c1410;
    margin-bottom: 0.2rem;
  }
  .comment-text {
    font-size: 0.85rem;
    color: #4a3f38;
    line-height: 1.45;
    margin: 0;
    overflow-wrap: break-word;
  }
  .comment-form {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #f1e4d8;
  }
  .comment-input {
    flex: 1;
    padding: 0.7rem 1rem;
    font-family: inherit;
    font-size: 0.85rem;
    background: #f7f1eb;
    border: 1.5px solid transparent;
    border-radius: 999px;
    outline: none;
    transition: all 0.2s ease;
  }
  .comment-input:focus {
    background: #fff;
    border-color: #f97316;
    box-shadow: 0 0 0 3px rgba(249,115,22,0.10);
  }
  .comment-send {
    flex-shrink: 0;
    width: 44px; height: 44px;
    border: none;
    border-radius: 50%;
    background: #f97316;
    color: #fff;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
  }
  .comment-send:hover { background: #ea580c; transform: scale(1.05); }
  .comment-login {
    text-align: center;
    font-size: 0.85rem;
    color: #8a7266;
    margin-top: 1rem;
  }
</style>