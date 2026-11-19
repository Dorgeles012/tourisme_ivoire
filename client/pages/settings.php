<?php
$settings = is_array($GLOBALS['client_settings'] ?? null) ? $GLOBALS['client_settings'] : [];
$access = is_array($settings['accessibilite_json'] ?? null) ? $settings['accessibilite_json'] : [];
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

  /* ===== Panels ===== */
  .panel {
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 12px;
    padding: 0.9rem 0.9rem 0.75rem;
    box-shadow: 0 2px 12px -4px rgba(234, 88, 12, 0.10);
    margin-bottom: 0.85rem;
  }
  .panel:last-child { margin-bottom: 0; }

  .section-title {
    font-size: 0.85rem;
    font-weight: 800;
    color: #1c1410;
    margin: 0 0 0.7rem;
    padding-bottom: 0.45rem;
    border-bottom: 1px solid #f3ebe4;
    letter-spacing: -0.2px;
  }

  /* ===== Grille 2 colonnes ===== */
  .grid.two-col {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.85rem;
    margin-bottom: 0.85rem;
  }
  @media (min-width: 720px) {
    .grid.two-col {
      grid-template-columns: 1fr 1fr;
    }
  }

  /* ===== Formulaire ===== */
  .form-grid {
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
  }
  .field {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
  }
  .field label {
    font-size: 0.7rem;
    font-weight: 700;
    color: #4a3f38;
    letter-spacing: 0.02em;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    cursor: pointer;
  }
  .field select {
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
  }
  .field select:focus {
    border-color: #f97316;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.10);
  }

  .field input[type="checkbox"] {
    width: 16px;
    height: 16px;
    accent-color: #f97316;
    cursor: pointer;
    flex-shrink: 0;
  }

  .form-actions {
    margin-top: 0.35rem;
    padding-top: 0.65rem;
    border-top: 1px dashed #f1e4d8;
  }
  .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.6rem 1.1rem;
    font-family: inherit;
    font-size: 0.75rem;
    font-weight: 700;
    color: #fff;
    background: linear-gradient(135deg, #fb923c, #ea580c);
    border: none;
    border-radius: 9px;
    text-decoration: none;
    cursor: pointer;
    box-shadow: 0 3px 10px -2px rgba(249, 115, 22, 0.3);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
  }
  .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 5px 14px -2px rgba(234, 88, 12, 0.4);
  }

  /* ===== Section contenu & données ===== */
  .panel p {
    font-size: 0.72rem;
    color: #8a7266;
    margin: 0 0 0.7rem;
    line-height: 1.5;
  }

  /* ===== Grille des fonctionnalités ===== */
  .feature-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.6rem;
  }
  @media (min-width: 640px) {
    .feature-grid {
      grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    }
  }
  .feature-grid article {
    padding: 0.7rem 0.8rem;
    background: #fff8f3;
    border: 1px solid #f1e4d8;
    border-radius: 10px;
  }
  .feature-grid h3 {
    font-size: 0.75rem;
    font-weight: 700;
    color: #1c1410;
    margin: 0 0 0.25rem;
    letter-spacing: -0.1px;
  }
  .feature-grid p {
    font-size: 0.68rem;
    color: #8a7266;
    margin: 0;
    line-height: 1.5;
  }

  /* ===== Responsive ===== */
  @media (max-width: 520px) {
    .panel { padding: 0.8rem 0.75rem 0.65rem; }
    .section-title { font-size: 0.78rem; }
    .field select { font-size: 0.72rem; padding: 0.55rem 0.65rem; }
    .btn { font-size: 0.7rem; padding: 0.55rem 0.95rem; }
    .feature-grid article { padding: 0.6rem 0.7rem; }
    .feature-grid h3 { font-size: 0.72rem; }
    .feature-grid p { font-size: 0.65rem; }
  }
  @media (prefers-reduced-motion: reduce) {
    *, .btn, .field select { transition: none !important; }
  }
</style>

<section class="grid two-col">
  <article class="panel">
    <h2 class="section-title">Apparence</h2>
    <form method="post" class="form-grid">
      <?= client_csrf_field() ?>
      <input type="hidden" name="action" value="settings">
      <div class="field full">
        <label for="theme">Thème de l’application</label>
        <select id="theme" name="theme">
          <option value="systeme" <?= ($settings['theme'] ?? '') === 'systeme' ? 'selected' : '' ?>>Système</option>
          <option value="clair" <?= ($settings['theme'] ?? '') === 'clair' ? 'selected' : '' ?>>Clair</option>
          <option value="sombre" <?= ($settings['theme'] ?? '') === 'sombre' ? 'selected' : '' ?>>Sombre</option>
        </select>
      </div>
      <div class="field full">
        <label><input type="checkbox" name="contraste_eleve" <?= !empty($access['contraste_eleve']) ? 'checked' : '' ?>> Contraste renforcé</label>
      </div>
      <div class="field full">
        <label><input type="checkbox" name="texte_agrandi" <?= !empty($access['texte_agrandi']) ? 'checked' : '' ?>> Texte agrandi</label>
      </div>
      <div class="form-actions"><button class="btn" type="submit">Enregistrer</button></div>
    </form>
  </article>
  <article class="panel">
    <h2 class="section-title">Contenu &amp; données</h2>
    <p>Enregistrez les destinations déjà publiées sur cet appareil.</p>
    <a class="btn" href="?page=offline_library">Bibliothèque hors ligne</a>
  </article>
</section>

<section class="panel">
  <h2 class="section-title">Fonctionnalités</h2>
  <div class="feature-grid">
    <article><h3>Notifications push</h3><p>Les notifications dans l’application sont prises en charge. Les notifications push nécessitent un fournisseur externe non configuré.</p></article>
    <article><h3>Traduction vocale</h3><p>La reconnaissance vocale nécessite un fournisseur de traduction configuré.</p></article>
    <article><h3>Diffusion en direct</h3><p>Aucun fournisseur de streaming n’est configuré ; la diffusion n’est pas disponible.</p></article>
  </div>
</section>