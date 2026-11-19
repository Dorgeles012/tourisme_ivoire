<?php
// ===== Chargement du feed =====
$feed   = ClientApi::data($api->request('GET', 'api/videos?page=1&per_page=12'));
$videos = is_array($feed['items'] ?? null) ? $feed['items'] : [];

// Helper URL média absolue
$mediaUrl = static function (string $path): string {
    if ($path === '') return '';
    if (preg_match('#^https?://#i', $path)) return $path;
    return rtrim((string)($api->baseUrl ?? ''), '/') . '/' . ltrim($path, '/');
};
?>

<style>
  /* ===== Reset global (1 seule fois) ===== */
  html, body {
    overflow-x: hidden;
    max-width: 100vw;
    margin: 0;
    padding: 0;
  }
  *, *::before, *::after { box-sizing: border-box; }

  body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    font-size: 13px;
    line-height: 1.5;
    color: #1c1410;
    background: #000;
  }

  /* ===== Topbar fixe ===== */
  .video-topbar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 50;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.7rem 0.85rem;
    background: linear-gradient(to bottom, rgba(0, 0, 0, 0.55), rgba(0, 0, 0, 0));
    pointer-events: none;
  }
  .video-topbar-title {
    color: #fff;
    font-size: 0.92rem;
    font-weight: 800;
    letter-spacing: -0.2px;
    margin: 0;
    text-shadow: 0 1px 4px rgba(0, 0, 0, 0.5);
    pointer-events: auto;
  }
  .video-topbar-cta {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.55);
    color: #fff;
    text-decoration: none;
    font-size: 1rem;
    transition: background 0.15s ease;
    pointer-events: auto;
  }
  .video-topbar-cta:hover { background: rgba(249, 115, 22, 0.85); }

  /* ===== Feed scroll-snap ===== */
  .video-feed {
    position: relative;
    height: 100dvh;
    overflow-y: scroll;
    overflow-x: hidden;
    scroll-snap-type: y mandatory;
    scroll-behavior: smooth;
    background: #000;
    margin: 0;
    padding: 0;
    -webkit-overflow-scrolling: touch;
  }
  .video-feed::-webkit-scrollbar { display: none; }

  .video-slide {
    position: relative;
    width: 100%;
    height: 100dvh;
    scroll-snap-align: start;
    scroll-snap-stop: always;
    overflow: hidden;
    background: #000;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .video-media {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #000;
  }

  .video-player,
  .video-poster {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    background: #000;
  }

  .video-error {
    display: flex;
    flex-direction: column;
    align-items: center;
    color: rgba(255, 255, 255, 0.5);
  }
  .video-error span { font-size: 2.5rem; margin-bottom: 0.4rem; }
  .video-error p { margin: 0; font-size: 0.78rem; }

  /* Overlay lecture */
  .video-play-overlay {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0.9);
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.5);
    color: #fff;
    display: none;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    padding-left: 3px;
    pointer-events: none;
    transition: opacity 0.18s ease, transform 0.18s ease;
  }
  .video-slide.is-paused .video-play-overlay {
    display: flex;
    transform: translate(-50%, -50%) scale(1);
  }

  /* Dégradé bas */
  .video-gradient {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    height: 280px;
    background: linear-gradient(to bottom, transparent, rgba(0, 0, 0, 0.87));
    pointer-events: none;
  }

  /* Infos bas-gauche */
  .video-info {
    position: absolute;
    left: 14px;
    right: 66px;
    bottom: 26px;
    z-index: 2;
    color: #fff;
    text-shadow: 0 1px 6px rgba(0, 0, 0, 0.6);
  }
  .video-info-title {
    font-size: 0.92rem;
    font-weight: 800;
    margin: 0 0 0.25rem;
    line-height: 1.25;
    color: #fff;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
  .video-info-meta {
    font-size: 0.7rem;
    color: rgba(255, 255, 255, 0.7);
    margin: 0 0 0.2rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .video-info-desc {
    font-size: 0.72rem;
    color: rgba(255, 255, 255, 0.85);
    margin: 0.2rem 0 0.5rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.4;
  }
  .video-info-comments {
    display: inline-block;
    color: #f97316;
    font-weight: 700;
    font-size: 0.72rem;
    text-decoration: none;
    transition: color 0.15s ease;
  }
  .video-info-comments:hover { color: #ea580c; }

  /* Actions droite */
  .video-actions {
    position: absolute;
    right: 10px;
    bottom: 42px;
    z-index: 2;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.1rem;
  }
  .video-action-form { margin: 0; }
  .video-action-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    border: none;
    background: transparent;
    border-radius: 50%;
    cursor: pointer;
    transition: transform 0.18s ease;
    text-decoration: none;
    padding: 0;
  }
  .video-action-btn:hover { transform: scale(1.1); }
  .video-action-btn:active { transform: scale(0.9); }

  .video-action-icon {
    font-size: 1.55rem;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.5));
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
  }
  .video-action-btn.is-liked .video-action-icon {
    animation: heartPop 0.4s ease;
  }
  @keyframes heartPop {
    0%   { transform: scale(1); }
    40%  { transform: scale(1.35); }
    100% { transform: scale(1); }
  }

  .video-action-count {
    color: #fff;
    font-size: 0.65rem;
    font-weight: 700;
    margin-bottom: 0.4rem;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.6);
  }

  /* ===== État vide ===== */
  .video-empty {
    position: relative;
    min-height: 100dvh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #1a1410;
    padding: 1.5rem;
  }
  .video-empty-inner {
    text-align: center;
    max-width: 340px;
  }
  .video-empty-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: rgba(249, 115, 22, 0.15);
    font-size: 2rem;
    margin-bottom: 1rem;
  }
  .video-empty-inner h2 {
    color: #fff;
    font-size: 1.1rem;
    font-weight: 800;
    margin: 0 0 0.5rem;
  }
  .video-empty-inner > p {
    color: rgba(255, 255, 255, 0.65);
    font-size: 0.78rem;
    margin: 0 0 1.5rem;
    line-height: 1.5;
  }
  .video-empty-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    min-width: 200px;
    padding: 0.7rem 1.25rem;
    background: #f97316;
    color: #fff;
    font-size: 0.78rem;
    font-weight: 700;
    border-radius: 10px;
    text-decoration: none;
    transition: background 0.15s ease, transform 0.15s ease, box-shadow 0.15s ease;
    box-shadow: 0 3px 12px -2px rgba(249, 115, 22, 0.35);
  }
  .video-empty-btn:hover {
    background: #ea580c;
    transform: translateY(-1px);
    box-shadow: 0 5px 16px -2px rgba(234, 88, 12, 0.45);
  }
  .video-empty-note {
    color: rgba(255, 255, 255, 0.4) !important;
    font-size: 0.65rem !important;
    margin-top: 1.25rem !important;
  }

  /* ===== Responsive ===== */
  @media (max-width: 520px) {
    .video-info { left: 12px; right: 60px; bottom: 22px; }
    .video-info-title { font-size: 0.85rem; }
    .video-info-meta { font-size: 0.65rem; }
    .video-info-desc { font-size: 0.68rem; }
    .video-action-btn { width: 40px; height: 40px; }
    .video-action-icon { font-size: 1.4rem; }
    .video-action-count { font-size: 0.6rem; }
    .video-empty-icon { width: 60px; height: 60px; font-size: 1.7rem; }
    .video-empty-inner h2 { font-size: 1rem; }
  }
  @media (prefers-reduced-motion: reduce) {
    *, .video-action-icon { transition: none !important; animation: none !important; }
    .video-feed { scroll-behavior: auto; }
  }
</style>

<?php if (!$videos): ?>
  <!-- ===== État vide ===== -->
  <section class="video-empty">
    <div class="video-empty-inner">
      <div class="video-empty-icon" aria-hidden="true">🎬</div>
      <h2>Aucune vidéo publiée</h2>
      <p>Les nouvelles vidéos sont vérifiées avant publication.</p>
      <?php if (!empty($user)): ?>
        <a class="video-empty-btn" href="?page=video_studio">
          ⬆ Publier une vidéo
        </a>
      <?php else: ?>
        <a class="video-empty-btn" href="?page=login">
          Se connecter pour publier
        </a>
      <?php endif; ?>
      <p class="video-empty-note">
        Les directs nécessitent un fournisseur de streaming configuré.
      </p>
    </div>
  </section>
<?php else: ?>
  <!-- ===== Feed vertical scroll-snap ===== -->
  <div class="video-feed" id="videoFeed">
    <?php foreach ($videos as $i => $video): ?>
      <?php
        $vid      = (int)($video['id'] ?? 0);
        $title    = (string)($video['titre'] ?? '');
        $author   = (string)($video['auteur'] ?? '');
        $dest     = (string)($video['destination'] ?? '');
        $desc     = (string)($video['description'] ?? '');
        $likes    = (int)($video['likes'] ?? 0);
        $comments = (int)($video['commentaires'] ?? 0);
        $liked    = !empty($video['liked']);
        $media    = $mediaUrl((string)($video['media_url'] ?? ''));
        $poster   = $mediaUrl((string)($video['miniature_url'] ?? ''));
      ?>
      <article class="video-slide" data-video-id="<?= $vid ?>">
        <!-- Média -->
        <div class="video-media">
          <?php if ($media !== ''): ?>
            <video
              class="video-player"
              src="<?= client_h($media) ?>"
              <?= $poster !== '' ? 'poster="' . client_h($poster) . '"' : '' ?>
              muted
              loop
              playsinline
              preload="metadata"></video>
          <?php elseif ($poster !== ''): ?>
            <img class="video-poster" src="<?= client_h($poster) ?>" alt="<?= client_h($title) ?>">
          <?php else: ?>
            <div class="video-error">
              <span aria-hidden="true">📵</span>
              <p>Lecture impossible</p>
            </div>
          <?php endif; ?>

          <!-- Overlay de lecture (pause) -->
          <div class="video-play-overlay" aria-hidden="true">▶</div>
        </div>

        <!-- Dégradé bas -->
        <div class="video-gradient" aria-hidden="true"></div>

        <!-- Infos en bas à gauche -->
        <div class="video-info">
          <h2 class="video-info-title"><?= client_h($title) ?></h2>
          <?php if ($author !== '' || $dest !== ''): ?>
            <p class="video-info-meta">
              <?= client_h($author) ?>
              <?php if ($dest !== ''): ?> • <?= client_h($dest) ?><?php endif; ?>
            </p>
          <?php endif; ?>
          <?php if ($desc !== ''): ?>
            <p class="video-info-desc"><?= client_h($desc) ?></p>
          <?php endif; ?>
          <a class="video-info-comments" href="?page=video&amp;id=<?= $vid ?>">
            Voir les commentaires →
          </a>
        </div>

        <!-- Actions à droite -->
        <div class="video-actions">
          <?php if (!empty($user)): ?>
            <form method="post" class="video-action-form">
              <?= client_csrf_field() ?>
              <input type="hidden" name="action" value="video_like">
              <input type="hidden" name="id" value="<?= $vid ?>">
              <button
                type="submit"
                class="video-action-btn <?= $liked ? 'is-liked' : '' ?>"
                title="<?= $liked ? 'Retirer le like' : 'Aimer' ?>"
                aria-label="<?= $liked ? 'Retirer le like' : 'Aimer' ?>">
                <span class="video-action-icon"><?= $liked ? '❤️' : '🤍' ?></span>
              </button>
            </form>
          <?php else: ?>
            <a class="video-action-btn" href="?page=login" title="Connectez-vous pour aimer">
              <span class="video-action-icon">🤍</span>
            </a>
          <?php endif; ?>
          <span class="video-action-count"><?= $likes ?></span>

          <a class="video-action-btn" href="?page=video&amp;id=<?= $vid ?>" title="Commentaires">
            <span class="video-action-icon">💬</span>
          </a>
          <span class="video-action-count"><?= $comments ?></span>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

