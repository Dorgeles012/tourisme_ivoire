<?php
$videoId = max(1, (int)($_GET['id'] ?? 0));
$video   = ClientApi::data($api->request('GET', 'api/videos/' . $videoId));
$comments = ClientApi::data($api->request('GET', 'api/videos/' . $videoId . '/comments'));
$comments = is_array($comments) ? $comments : [];
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

  /* ===== Header ===== */
  .page-header {
    margin-bottom: 0.85rem;
  }
  .back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.7rem;
    font-weight: 600;
    color: #8a7266;
    text-decoration: none;
    margin-bottom: 0.35rem;
    transition: color 0.15s ease;
  }
  .back-link:hover { color: #ea580c; }
  .page-title {
    font-size: 1.15rem;
    font-weight: 900;
    color: #1c1410;
    margin: 0 0 0.2rem;
    letter-spacing: -0.3px;
    line-height: 1.25;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
  }
  .page-subtitle {
    font-size: 0.7rem;
    color: #8a7266;
    margin: 0;
  }

  /* ===== Panel commentaires ===== */
  .comments-panel {
    max-width: 640px;
    margin: 0 auto;
    padding: 0.85rem;
  }

  /* ===== Liste commentaires ===== */
  .comments-list {
    display: flex;
    flex-direction: column;
  }
  .comment-row {
    display: flex;
    gap: 0.55rem;
    margin-bottom: 0.7rem;
    align-items: flex-start;
  }
  .comment-avatar {
    flex-shrink: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(249, 115, 22, 0.15);
    color: #ea580c;
    font-weight: 800;
    font-size: 0.75rem;
  }
  .comment-bubble {
    flex: 1;
    padding: 0.6rem 0.75rem;
    background: #f7f1eb;
    border-radius: 4px 12px 12px 12px;
    min-width: 0;
  }
  .comment-author {
    display: block;
    font-size: 0.7rem;
    font-weight: 700;
    color: #1c1410;
    margin-bottom: 0.15rem;
  }
  .comment-text {
    font-size: 0.78rem;
    color: #4a3f38;
    line-height: 1.45;
    margin: 0;
    overflow-wrap: break-word;
  }

  /* ===== Formulaire commentaire ===== */
  .comment-form {
    display: flex;
    gap: 0.4rem;
    margin-top: 0.85rem;
    padding-top: 0.85rem;
    border-top: 1px solid #f1e4d8;
  }
  .comment-input {
    flex: 1;
    padding: 0.55rem 0.85rem;
    font-family: inherit;
    font-size: 0.78rem;
    background: #f7f1eb;
    border: 1.5px solid transparent;
    border-radius: 999px;
    outline: none;
    transition: background 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
    min-width: 0;
  }
  .comment-input::placeholder { color: #b8a598; }
  .comment-input:focus {
    background: #fff;
    border-color: #f97316;
    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.10);
  }
  .comment-send {
    flex-shrink: 0;
    width: 38px;
    height: 38px;
    border: none;
    border-radius: 50%;
    background: #f97316;
    color: #fff;
    font-size: 0.9rem;
    cursor: pointer;
    transition: background 0.15s ease, transform 0.15s ease;
  }
  .comment-send:hover {
    background: #ea580c;
    transform: scale(1.05);
  }

  .comment-login {
    text-align: center;
    font-size: 0.75rem;
    color: #8a7266;
    margin-top: 0.85rem;
  }
  .comment-login a {
    color: #ea580c;
    font-weight: 700;
    text-decoration: none;
  }
  .comment-login a:hover { text-decoration: underline; }

  /* ===== État vide ===== */
  .empty {
    text-align: center;
    padding: 1rem 0.6rem;
  }
  .empty-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    margin-bottom: 0.5rem;
    background: rgba(249, 115, 22, 0.10);
    color: #ea580c;
    border-radius: 50%;
    font-size: 1.15rem;
  }
  .empty h3 {
    font-size: 0.82rem;
    font-weight: 700;
    color: #1c1410;
    margin: 0 0 0.2rem;
  }
  .empty p {
    font-size: 0.7rem;
    color: #8a7266;
    margin: 0;
    line-height: 1.4;
  }

  /* ===== Responsive ===== */
  @media (max-width: 520px) {
    .page-title { font-size: 1rem; }
    .comments-panel { padding: 0.7rem; }
    .comment-avatar { width: 28px; height: 28px; font-size: 0.7rem; }
    .comment-bubble { padding: 0.5rem 0.65rem; }
    .comment-author { font-size: 0.65rem; }
    .comment-text { font-size: 0.72rem; }
    .comment-input { font-size: 0.72rem; padding: 0.5rem 0.75rem; }
    .comment-send { width: 34px; height: 34px; font-size: 0.8rem; }
  }
  @media (prefers-reduced-motion: reduce) {
    *, .comment-send, .comment-input, .back-link { transition: none !important; }
  }
</style>

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