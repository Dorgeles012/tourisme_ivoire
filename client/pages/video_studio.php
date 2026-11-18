<?php if (empty($user)) {
    header('Location: ?page=login');
    exit;
} ?>


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
    📤 Envoyer pour modération
  </button>
</form>

<style>
  .studio-form {
    max-width: 640px;
    margin: 0 auto;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  .studio-hero {
    display: flex;
    align-items: center;
    gap: 0.9rem;
    padding: 1.1rem;
    background: linear-gradient(135deg, rgba(249,115,22,0.10), rgba(22,160,133,0.08));
    border-radius: 14px;
    margin-bottom: 1.5rem;
  }

  .studio-hero-icon {
    flex-shrink: 0;
    width: 48px; height: 48px;
    display: flex; align-items: center; justify-content: center;
    background: rgba(249,115,22,0.15);
    border-radius: 50%;
    font-size: 1.5rem;
  }

  .studio-hero-text strong {
    display: block;
    font-size: 1rem; font-weight: 800;
    color: #1c1410;
  }
  .studio-hero-text span {
    font-size: 0.78rem;
    color: #8a7266;
  }

  .studio-field { margin-bottom: 1rem; }

  .studio-field label {
    display: block;
    font-size: 0.78rem;
    font-weight: 600;
    color: #1c1410;
    margin-bottom: 0.35rem;
  }

  .studio-field input,
  .studio-field textarea {
    width: 100%;
    padding: 0.7rem 0.85rem;
    font-family: inherit;
    font-size: 0.85rem;
    color: #1c1410;
    background: #fff8f3;
    border: 1.5px solid #f1e4d8;
    border-radius: 10px;
    outline: none;
    transition: all 0.2s ease;
    resize: vertical;
  }

  .studio-field input:focus,
  .studio-field textarea:focus {
    border-color: #f97316;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.10);
  }

  .studio-file {
    padding: 0.85rem 1rem;
    background: #f7f1eb;
    border: 1.5px dashed #e8d9cc;
    border-radius: 10px;
    margin-bottom: 0.4rem;
    transition: all 0.2s ease;
  }

  .studio-file.has-file {
    background: rgba(22,160,133,0.08);
    border-color: rgba(22,160,133,0.5);
    border-style: solid;
  }

  .studio-file-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }

  .studio-file-icon { font-size: 1.35rem; }

  .studio-file-label {
    flex: 1;
    font-size: 0.85rem;
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
    padding: 0.45rem 0.85rem;
    font-size: 0.78rem;
    font-weight: 700;
    color: #ea580c;
    background: #fff;
    border: 1.5px solid #f97316;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .studio-file-btn:hover { background: rgba(249,115,22,0.08); }

  .studio-hint {
    font-size: 0.7rem;
    color: #8a7266;
    margin: 0 0 1rem;
    line-height: 1.4;
  }

  .studio-checkbox {
    display: flex;
    align-items: flex-start;
    gap: 0.6rem;
    padding: 0.8rem 1rem;
    background: #fff;
    border: 1.5px solid #f1e4d8;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    font-size: 0.82rem;
    color: #1c1410;
    cursor: pointer;
    line-height: 1.4;
  }

  .studio-checkbox input {
    margin-top: 0.15rem;
    accent-color: #f97316;
    flex-shrink: 0;
  }

  .studio-submit {
    width: 100%;
    padding: 0.95rem 1rem;
    font-family: inherit;
    font-size: 0.9rem;
    font-weight: 700;
    color: #fff;
    background: linear-gradient(135deg, #fb923c, #ea580c);
    border: none;
    border-radius: 12px;
    cursor: pointer;
    box-shadow: 0 4px 16px rgba(249, 115, 22, 0.35);
    transition: all 0.2s ease;
  }

  .studio-submit:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(234, 88, 12, 0.45);
  }

  .studio-submit:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
  }
</style>

<script>
(function () {
  const fileInput = document.getElementById('videoFile');
  const fileWrap  = document.getElementById('studioFile');
  const fileLabel = document.getElementById('studioFileName');
  const form      = document.getElementById('studioForm');

  if (fileInput) {
    fileInput.addEventListener('change', function () {
      const file = fileInput.files && fileInput.files[0];
      if (!file) return;
      const mo = (file.size / (1024 * 1024)).toFixed(1);
      fileLabel.textContent = file.name + ' · ' + mo + ' Mo';
      fileWrap.classList.add('has-file');
    });
  }

  if (form) {
    form.addEventListener('submit', function (e) {
      const submitBtn = form.querySelector('.studio-submit');
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = '⏳ Envoi en cours…';
      }
    });
  }
})();
</script>