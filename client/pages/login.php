
<section class="auth-card auth-login" aria-labelledby="login-title">

  <!-- En-tête -->
  <div class="auth-header">
    <span class="eyebrow">Votre voyage commence ici</span>
    <h1 id="login-title">Bienvenue</h1>
    <p class="row-meta">
      Connectez-vous pour retrouver vos réservations et vos découvertes.
    </p>
  </div>

  <!-- Formulaire -->
  <form method="post" class="form-grid">
    <?= client_csrf_field() ?>
    <input type="hidden" name="action" value="login">

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
          autocomplete="username"
          placeholder="vous@exemple.com"
          inputmode="email">
      </div>
    </div>

    <!-- Mot de passe -->
    <div class="field full">
      <label for="password">Mot de passe</label>
      <div class="input-wrapper">
        <input
          id="password"
          name="password"
          type="password"
          required
          maxlength="4096"
          autocomplete="current-password"
          placeholder="••••••••••">

        <button
          type="button"
          class="toggle-password"
          data-password-toggle="password"
          aria-label="Afficher le mot de passe"
          aria-pressed="false"
        >Afficher</button>
      </div>

      <!-- Lien mot de passe oublié, aligné à droite sous le champ -->
      <a class="forgot-link" href="?page=forgot">Mot de passe oublié ?</a>
    </div>

    <!-- Bouton -->
    <div class="form-actions">
      <button class="btn" type="submit">
        <span>Se connecter</span>
      </button>
    </div>
  </form>

  <!-- Lien inscription -->
  <p class="auth-switch">
    Pas encore de compte ?
    <a class="text-link" href="?page=register">Créer mon compte</a>
  </p>

</section>


<style>
  /* ============================================
     VARIABLES (identiques à la page inscription)
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
    font-size: 1.5rem;
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
     MOT DE PASSE OUBLIÉ
     ============================================ */
  .forgot-link {
    align-self: flex-end;
    font-size: 0.72rem;
    color: var(--auth-muted);
    text-decoration: none;
    transition: color 0.2s ease;
  }

  .forgot-link:hover { color: var(--auth-primary); }

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
     LIEN INSCRIPTION
     ============================================ */
  .auth-switch {
    text-align: center;
    margin: 1.2rem 0 0;
    padding-top: 1rem;
    border-top: 1px solid var(--auth-border);
    font-size: 0.78rem;
    color: var(--auth-muted);
    position: relative;
    z-index: 1;
  }

  .text-link {
    color: var(--auth-primary);
    font-weight: 600;
    text-decoration: none;
    position: relative;
    transition: color 0.2s ease;
  }

  .text-link::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 0;
    height: 1.5px;
    background: var(--auth-primary);
    transition: width 0.25s ease;
  }

  .text-link:hover { color: var(--auth-primary-hover); }
  .text-link:hover::after { width: 100%; }

  /* ============================================
     RESPONSIVE
     ============================================ */
  @media (max-width: 520px) {
    .auth-card {
      max-width: 100%;
      margin: 0.75rem auto;
      padding: 1.5rem 1.1rem 1.2rem;
    }

    .auth-card h1 { font-size: 1.3rem; }
  }
</style>