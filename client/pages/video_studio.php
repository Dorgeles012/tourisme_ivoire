<?php if (empty($user)) {
    header('Location: ?page=login');
    exit;
} ?>

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

  /* ===== Formulaire principal ===== */
  .studio-form {
    max-width: 580px;
    margin: 0 auto;
  }

  /* ===== Bandeau hero ===== */
  .studio-hero {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    padding: 0.9rem;
    background: linear-gradient(135deg, rgba(249, 115, 22, 0.10), rgba(22, 160, 133, 0.08));
    border-radius: 12px;
    margin-bottom: 1.15rem;
  }
  .studio-hero-icon {
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(249, 115, 22, 0.15);
    border-radius: 50%;
    font-size: 1.25rem;
  }
  .studio-hero-text strong {
    display: block;
    font-size: 0.85rem;
    font-weight: 800;
    color: #1c1410;
  }
  .studio-hero-text span {
    font-size: 0.68rem;
    color: #8a7266;
  }

  /* ===== Champs ===== */
  .studio-field { margin-bottom: 0.85rem; }
  .studio-field label {
    display: block;
    font-size: 0.7rem;
    font-weight: 700;
    color: #4a3f38;
    margin-bottom: 0.3rem;
    letter-spacing: 0.02em;
  }
  .studio-field input,
  .studio-field textarea {
    width: 100%;
    padding: 0.6rem 0.75rem;
    font-family: inherit;
    font-size: 0.78rem;
    color: #1c1410;
    background: #fff8f3;
    border: 1.5px solid #f1e4d8;
    border-radius: 9px;
    outline: none;
    transition: border-color 0.15s ease, background 0.15s ease, box-shadow 0.15s ease;
    resize: vertical;
  }
  .studio-field input::placeholder,
  .studio-field textarea::placeholder { color: #b8a598; }
  .studio-field input:focus,
  .studio-field textarea:focus {
    border-color: #f97316;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.10);
  }

  /* ===== Upload fichier ===== */
  .studio-file {
    padding: 0.75rem 0.85rem;
    background: #f7f1eb;
    border: 1.5px dashed #e8d9cc;
    border-radius: 9px;
    margin-bottom: 0.4rem;
    transition: background 0.15s ease, border-color 0.15s ease, border-style 0.15s ease;
  }
  .studio-file.has-file {
    background: rgba(22, 160, 133, 0.08);
    border-color: rgba(22, 160, 133, 0.5);
    border-style: solid;
  }
  .studio-file-row {
    display: flex;
    align-items: center;
    gap: 0.6rem;
  }
  .studio-file-icon { font-size: 1.1rem; flex-shrink: 0; }
  .studio-file-label {
    flex: 1;
    font-size: 0.78rem;
    color: #8a7266;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .studio-file.has-file .studio-file-label {
    color: #0e7d6b;
    font-weight: 700;
  }
  .studio-file-btn {
    flex-shrink: 0;
    padding: 0.35rem 0.7rem;
    font-size: 0.7rem;
    font-weight: 700;
    color: #ea580c;
    background: #fff;
    border: 1.5px solid #f97316;
    border-radius: 7px;
    cursor: pointer;
    transition: background 0.15s ease;
  }
  .studio-file-btn:hover { background: rgba(249, 115, 22, 0.08); }

  .studio-hint {
    font-size: 0.62rem;
    color: #8a7266;
    margin: 0 0 0.85rem;
    line-height: 1.4;
  }

  /* ===== Checkbox ===== */
  .studio-checkbox {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    padding: 0.7rem 0.85rem;
    background: #fff;
    border: 1.5px solid #f1e4d8;
    border-radius: 9px;
    margin-bottom: 1.15rem;
    font-size: 0.72rem;
    color: #1c1410;
    cursor: pointer;
    line-height: 1.4;
  }
  .studio-checkbox input {
    margin-top: 0.15rem;
    width: 16px;
    height: 16px;
    accent-color: #f97316;
    flex-shrink: 0;
    cursor: pointer;
  }

  /* ===== Submit ===== */
  .studio-submit {
    width: 100%;
    padding: 0.8rem 0.9rem;
    font-family: inherit;
    font-size: 0.78rem;
    font-weight: 700;
    color: #fff;
    background: linear-gradient(135deg, #fb923c, #ea580c);
    border: none;
    border-radius: 10px;
    cursor: pointer;
    box-shadow: 0 3px 12px -2px rgba(249, 115, 22, 0.35);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
  }
  .studio-submit:hover {
    transform: translateY(-1px);
    box-shadow: 0 5px 16px -2px rgba(234, 88, 12, 0.45);
  }
  .studio-submit:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
  }

  /* ===== Responsive ===== */
  @media (max-width: 520px) {
    .studio-hero { padding: 0.75rem; gap: 0.55rem; }
    .studio-hero-icon { width: 34px; height: 34px; font-size: 1rem; }
    .studio-hero-text strong { font-size: 0.78rem; }
    .studio-hero-text span { font-size: 0.62rem; }
    .studio-field input, .studio-field textarea { font-size: 0.72rem; padding: 0.55rem 0.65rem; }
    .studio-file { padding: 0.65rem 0.7rem; }
    .studio-file-label { font-size: 0.72rem; }
    .studio-submit { font-size: 0.72rem; padding: 0.7rem 0.8rem; }
  }
  @media (prefers-reduced-motion: reduce) {
    *, .studio-submit, .studio-field input, .studio-field textarea { transition: none !important; }
  }
</style>

<form method="post" enctype="multipart/form-data" class="studio-form" id="studioForm">
  <?= client_csrf_field() ?>
  <input type="hidden" name="action" value="video_publish">

  <!-- Bandeau gradient -->
  <div class="studio-hero">
    <div class="studio-hero-icon" aria-hidden="true">🎬</div>
    <div class="studio-hero-text">
      <strong>Partager votre expérience</strong>
      <span>Vidéo modérée avant publication</span>
    </div>
  </div>

  <div class="studio-field">
    <label for="videoTitle">Titre de la vidéo</label>
    <input id="videoTitle" name="titre" type="text" maxlength="180" required
           placeholder="Ex : Les chutes de Man en saison des pluies">
  </div>

  <div class="studio-field">
    <label for="videoDesc">Description</label>
    <textarea id="videoDesc" name="description" rows="4" maxlength="2000"
              placeholder="Racontez votre expérience…"></textarea>
  </div>

  <div class="studio-file" id="studioFile">
    <div class="studio-file-row">
      <span class="studio-file-icon" aria-hidden="true">📼</span>
      <span class="studio-file-label" id="studioFileName">
        Choisir une vidéo (MP4, WebM ou MOV)
      </span>
      <label for="videoFile" class="studio-file-btn">Parcourir</label>
      <input id="videoFile" name="video" type="file"
             accept="video/mp4,video/webm,video/quicktime" required hidden>
    </div>
  </div>
  <p class="studio-hint">Taille maximale : 100 Mo. La vidéo sera modérée avant publication.</p>

  <label class="studio-checkbox">
    <input type="checkbox" name="rights" value="1" required>
    <span>Je confirme détenir les droits de diffusion de cette vidéo.</span>
  </label>

  <button type="submit" class="studio-submit">
     Envoyer pour modération
  </button>
</form>

