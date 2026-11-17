
<section class="auth-card auth-reset" aria-labelledby="reset-title">

  <!-- En-tête -->
  <div class="auth-header">
    <span class="eyebrow">Sécurité du compte</span>
    <h1 id="reset-title">Réinitialiser le mot de passe</h1>
    <p class="row-meta">
      Saisissez le code reçu par e-mail et choisissez un nouveau mot de passe.
    </p>
  </div>

  <!-- Formulaire -->
  <form method="post" class="form-grid">
    <?= client_csrf_field() ?>
    <input type="hidden" name="action" value="reset">

    <!-- E-mail -->
    <div class="field full">
      <label for="email">Adresse e-mail</label>
      <div class="input-wrapper">
        <input
          id="email"
          name="email"
          type="email"
          required
          maxlength="190"
          autocomplete="email"
          inputmode="email"
          placeholder="vous@exemple.com">
      </div>
    </div>

    <!-- Code reçu -->
    <div class="field full">
      <label for="token">Code reçu</label>
      <div class="input-wrapper">
        <input
          id="token"
          name="token"
          required
          minlength="64"
          maxlength="64"
          autocomplete="one-time-code"
          inputmode="text"
          spellcheck="false"
          autocapitalize="off"
          placeholder="Collez le code à 64 caractères">
      </div>
      <small class="hint">Le code figure dans l'e-mail qui vous a été envoyé.</small>
    </div>

    <!-- Nouveau mot de passe -->
    <div class="field full">
      <label for="password">Nouveau mot de passe <span class="label-optional">(10 car. min)</span></label>
      <div class="input-wrapper">
        <input
          id="password"
          name="password"
          type="password"
          required
          minlength="10"
          autocomplete="new-password"
          placeholder="••••••••••">

        <button
          type="button"
          class="toggle-password"
          aria-label="Afficher le mot de passe"
          aria-pressed="false"
          onclick="
            const p = document.getElementById('password');
            const visible = p.type === 'password';
            p.type = visible ? 'text' : 'password';
            this.classList.toggle('is-visible', visible);
            this.setAttribute('aria-pressed', visible);
            this.setAttribute('aria-label',
              visible ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
            this.textContent = visible ? 'Masquer' : 'Afficher';
          "
        >Afficher</button>
      </div>
    </div>

    <!-- Bouton -->
    <div class="form-actions">
      <button class="btn" type="submit">
        <span>Mettre à jour</span>
      </button>
    </div>
  </form>

</section>


<style>
  /* ============================================
     VARIABLES (identiques aux autres pages)
     ============================================ */
  :root {
    --auth-bg: #ffffff;
    --auth-surface: #fff8f3;
    --auth-border: #f1e4d8;
    --auth-border-hover: #e8d0bd;
    --auth-border-focus: #f97316;
    --auth-text: #1c1410;
    --auth-muted: #8a7266;
    --auth-primary: #f97316;
    --auth-primary-hover: #ea580c;
    --auth-primary-soft: rgba(249, 115, 22, 0.10);
    --auth-radius: 10px;
    --auth-shadow: 0 8px 28px -8px rgba(234, 88, 12, 0.18);
    --auth-font: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  * { box-sizing: border-box; }

  /* ============================================
     CARTE
     ============================================ */
  .auth-card {
    width: 100%;
    max-width: 440px;
    margin: 1.5rem auto;
    padding: 2rem 1.6rem 1.6rem;
    background: var(--auth-bg);
    border: 1px solid var(--auth-border);
    border-radius: 16px;
    box-shadow: var(--auth-shadow);
    font-family: var(--auth-font);
    color: var(--auth-text);
    position: relative;
    overflow: hidden;
  }

  .auth-card::before {
    content: '';
    position: absolute;
    top: -70px;
    right: -70px;
    width: 160px;
    height: 160px;
    background: radial-gradient(circle, rgba(249,115,22,0.14) 0%, transparent 70%);
    pointer-events: none;
  }

  /* ============================================
     EN-TÊTE
     ============================================ */
  .auth-header {
    text-align: center;
    margin-bottom: 1.5rem;
    position: relative;
    z-index: 1;
  }

  .brand-mark {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 52px;
    height: 52px;
    margin-bottom: 0.85rem;
    background: linear-gradient(135deg, #fb923c 0%, #ea580c 100%);
    color: #fff;
    font-weight: 800;
    font-size: 1.05rem;
    letter-spacing: -0.02em;
    border-radius: 14px;
    box-shadow: 0 6px 16px rgba(234, 88, 12, 0.35);
  }

  .eyebrow {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.7rem;
    background: var(--auth-primary-soft);
    color: var(--auth-primary-hover);
    border-radius: 999px;
    font-size: 0.65rem;
    font-weight: 600;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    margin-bottom: 0.6rem;
    border: 1px solid rgba(249, 115, 22, 0.2);
  }

  .auth-card h1 {
    font-size: 1.4rem;
    font-weight: 700;
    margin: 0 0 0.35rem;
    letter-spacing: -0.02em;
    background: linear-gradient(135deg, #1c1410 0%, #f97316 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }

  .row-meta {
    font-size: 0.8rem;
    color: var(--auth-muted);
    margin: 0;
    line-height: 1.5;
  }

  /* ============================================
     FORMULAIRE
     ============================================ */
  .form-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.9rem;
    position: relative;
    z-index: 1;
  }

  .field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    min-width: 0;
  }

  .field label {
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--auth-text);
  }

  .label-optional {
    font-weight: 400;
    color: var(--auth-muted);
    font-size: 0.7rem;
  }

  .hint {
    font-size: 0.7rem;
    color: var(--auth-muted);
    margin-top: 0.1rem;
  }

  /* ============================================
     INPUTS
     ============================================ */
  .input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    width: 100%;
  }

  .input-wrapper input {
    width: 100%;
    min-width: 0;
    padding: 0.75rem 0.9rem;
    background: var(--auth-surface);
    border: 1.5px solid var(--auth-border);
    border-radius: var(--auth-radius);
    font-size: 0.85rem;
    font-family: inherit;
    color: var(--auth-text);
    outline: none;
    transition: all 0.2s ease;
  }

  .input-wrapper input::placeholder { color: #d8bfa8; }

  .input-wrapper input:hover {
    border-color: var(--auth-border-hover);
    background: #fff;
  }

  .input-wrapper input:focus {
    border-color: var(--auth-border-focus);
    background: #fff;
    box-shadow: 0 0 0 3px var(--auth-primary-soft);
  }

  /* Champ code : police monospace pour lisibilité */
  #token {
    font-family: 'SFMono-Regular', Menlo, Consolas, monospace;
    font-size: 0.78rem;
    letter-spacing: 0.05em;
  }

  /* ============================================
     TOGGLE MOT DE PASSE
     ============================================ */
  .toggle-password {
    position: absolute;
    right: 0.5rem;
    padding: 0.35rem 0.45rem;
    border: none;
    background: transparent;
    color: var(--auth-muted);
    font-size: 0.68rem;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    border-radius: 5px;
    transition: all 0.2s ease;
  }

  .toggle-password:hover {
    color: var(--auth-primary);
    background: var(--auth-primary-soft);
  }

  #password { padding-right: 5rem; }

  /* ============================================
     BOUTON
     ============================================ */
  .form-actions { margin-top: 0.4rem; }

  .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    padding: 0.8rem 1rem;
    background: linear-gradient(135deg, #fb923c 0%, #f97316 50%, #ea580c 100%);
    color: #fff;
    border: none;
    border-radius: var(--auth-radius);
    font-size: 0.9rem;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    transition: all 0.25s ease;
    box-shadow: 0 4px 12px rgba(249, 115, 22, 0.40);
  }

  .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 7px 18px rgba(234, 88, 12, 0.50);
    background: linear-gradient(135deg, #f97316 0%, #ea580c 50%, #c2410c 100%);
  }

  .btn:active { transform: translateY(0); }

  /* ============================================
     RESPONSIVE
     ============================================ */
  @media (max-width: 520px) {
    .auth-card {
      max-width: 100%;
      margin: 0.75rem auto;
      padding: 1.5rem 1.1rem 1.2rem;
    }

    .auth-card h1 { font-size: 1.25rem; }
  }
</style>