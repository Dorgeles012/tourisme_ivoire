<section class="auth-card">
  <div class="auth-header">
    <span class="eyebrow">Rejoignez-nous</span>
    <h1>Créer un compte</h1>
    <p class="row-meta">
      Votre espace personnel vous accompagne dans chaque découverte.
    </p>
  </div>

  <form method="post" class="form-grid">
    <?= client_csrf_field() ?>
    <input type="hidden" name="action" value="register">

    <div class="field">
      <label for="nom">Nom</label>
      <div class="input-wrapper">
        <input id="nom" name="nom" type="text" required maxlength="100"
          autocomplete="family-name" placeholder="Votre nom">
      </div>
    </div>

    <div class="field">
      <label for="prenom">Prénom</label>
      <div class="input-wrapper">
        <input id="prenom" name="prenom" type="text" maxlength="100"
          autocomplete="given-name" placeholder="Votre prénom">
      </div>
    </div>

    <div class="field full">
      <label for="email">Adresse e-mail</label>
      <div class="input-wrapper">
        <input id="email" name="email" type="email" required maxlength="190"
          autocomplete="email" inputmode="email" placeholder="vous@exemple.com">
      </div>
    </div>

    <div class="field full">
      <label for="telephone">
        Téléphone <span class="label-optional">(facultatif)</span>
      </label>
      <div class="input-wrapper">
        <input id="telephone" name="telephone" type="tel" maxlength="30"
          autocomplete="tel" inputmode="tel" placeholder="+225 XX XX XX XX">
      </div>
    </div>

    <div class="field full">
      <label for="password">
        Mot de passe <span class="label-optional">(10 car. min)</span>
      </label>
      <div class="input-wrapper">
        <input id="password" name="password" type="password" required
          minlength="10" maxlength="4096" autocomplete="new-password" placeholder="••••••••••">
        <button type="button" class="toggle-password" data-password-toggle="password" aria-label="Afficher le mot de passe" aria-pressed="false">Afficher</button>
      </div>
      <div class="password-strength" aria-hidden="true">
        <div class="strength-bar"></div>
        <div class="strength-bar"></div>
        <div class="strength-bar"></div>
        <div class="strength-bar"></div>
      </div>
    </div>

    <div class="form-actions">
      <button class="btn" type="submit">
        <span>Créer mon compte</span>
      </button>
    </div>
  </form>

  <p class="auth-switch">
    Déjà inscrit ?
    <a class="text-link" href="?page=login">Se connecter</a>
  </p>
</section>

<style>
  /* ============================================
     VARIABLES
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
    --auth-font: 'Inter', -apple-system, BlinkMacSystemFont,
                 'Segoe UI', sans-serif;
  }

  * { box-sizing: border-box; }

  /* ============================================
     PAGE — centrage horizontal + vertical
     (à appliquer sur <body class="auth-page">)
     ============================================ */
  body.auth-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0;
    padding: 1rem;
    background: #faf6f2;
    font-family: var(--auth-font);
  }

  /* ============================================
     CARTE
     ============================================ */
  .auth-card {
    width: 100%;
    max-width: 440px;
    margin: 0;
    padding: 1.2rem 1.4rem;
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
    margin-bottom: 0.9rem;
    position: relative;
    z-index: 1;
  }

  .eyebrow {
    display: inline-flex;
    align-items: center;
    padding: 0.2rem 0.6rem;
    background: var(--auth-primary-soft);
    color: var(--auth-primary-hover);
    border-radius: 999px;
    font-size: 0.62rem;
    font-weight: 600;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    margin-bottom: 0.45rem;
    border: 1px solid rgba(249, 115, 22, 0.2);
  }

  .auth-card h1 {
    font-size: 1.35rem;
    font-weight: 700;
    margin: 0 0 0.25rem;
    letter-spacing: -0.02em;
    background: linear-gradient(135deg, #1c1410 0%, #f97316 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }

  .row-meta {
    font-size: 0.75rem;
    color: var(--auth-muted);
    margin: 0;
    line-height: 1.4;
  }

  /* ============================================
     FORMULAIRE
     ============================================ */
  .form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.65rem;
    position: relative;
    z-index: 1;
  }

  .field {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    min-width: 0;
  }

  .field.full { grid-column: 1 / -1; }

  .field label {
    font-size: 0.73rem;
    font-weight: 600;
    color: var(--auth-text);
  }

  .label-optional {
    font-weight: 400;
    color: var(--auth-muted);
    font-size: 0.66rem;
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
    padding: 0.6rem 0.85rem;
    background: var(--auth-surface);
    border: 1.5px solid var(--auth-border);
    border-radius: var(--auth-radius);
    font-size: 0.82rem;
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
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.3rem 0.4rem;
    border: none;
    background: transparent;
    color: var(--auth-muted);
    font-size: 0.63rem;
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

  #password { padding-right: 4.3rem; }

  /* ============================================
     INDICATEUR DE SÉCURITÉ
     ============================================ */
  .password-strength {
    display: flex;
    gap: 4px;
    margin-top: 0.25rem;
  }

  .strength-bar {
    height: 3px;
    flex: 1;
    background: var(--auth-border);
    border-radius: 999px;
  }

  /* ============================================
     BOUTON
     ============================================ */
  .form-actions {
    grid-column: 1 / -1;
    margin-top: 0.2rem;
  }

  .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    padding: 0.65rem 1rem;
    background: linear-gradient(135deg, #fb923c 0%, #f97316 50%, #ea580c 100%);
    color: #fff;
    border: none;
    border-radius: var(--auth-radius);
    font-size: 0.85rem;
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
     LIEN DE CONNEXION
     ============================================ */
  .auth-switch {
    text-align: center;
    margin: 0.8rem 0 0;
    font-size: 0.73rem;
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
    body.auth-page { padding: 0.5rem; }

    .auth-card {
      max-width: 100%;
      padding: 1rem 1rem;
    }

    .auth-card h1 { font-size: 1.2rem; }
  }

  @media (max-width: 420px) {
    .form-grid { grid-template-columns: 1fr; }
    .field.full { grid-column: auto; }
  }
</style>