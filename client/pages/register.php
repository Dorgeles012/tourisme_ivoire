<?php
declare(strict_types=1);

$bootstrapCandidates = [
    dirname(__DIR__, 2) . '/backend/admin/bootstrap.php',
    dirname(__DIR__, 2) . '/backend/config/bootstrap.php',
    dirname(__DIR__, 2) . '/client/bootstrap.php',
    dirname(__DIR__, 3) . '/client/bootstrap.php',
    dirname(__DIR__) . '/bootstrap.php',
];
$bootstrapLoaded = false;
foreach ($bootstrapCandidates as $candidate) {
    if (is_file($candidate)) {
        require_once $candidate;
        $bootstrapLoaded = true;
        break;
    }
}
if (!$bootstrapLoaded) {
    http_response_code(500);
    exit('Bootstrap introuvable.');
}

if (!empty($_SESSION['client_user']['id'])) {
    header('Location: ?page=account');
    exit;
}

$error   = '';
$success = '';
$old     = [
    'nom'       => '',
    'prenom'    => '',
    'email'     => '',
    'telephone' => '',
];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    client_check_csrf();

    $old['nom']       = trim((string)($_POST['nom'] ?? ''));
    $old['prenom']    = trim((string)($_POST['prenom'] ?? ''));
    $old['email']     = strtolower(trim((string)($_POST['email'] ?? '')));
    $old['telephone'] = trim((string)($_POST['telephone'] ?? ''));
    $password         = (string)($_POST['password'] ?? '');

    if ($old['nom'] === '') {
        $error = 'Le nom est obligatoire.';
    } elseif (mb_strlen($old['nom']) > 100) {
        $error = 'Le nom ne peut pas dépasser 100 caractères.';
    } elseif (mb_strlen($old['prenom']) > 100) {
        $error = 'Le prénom ne peut pas dépasser 100 caractères.';
    } elseif ($old['email'] === '') {
        $error = "L'adresse e-mail est obligatoire.";
    } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse e-mail invalide.';
    } elseif (mb_strlen($old['email']) > 190) {
        $error = 'Adresse e-mail trop longue.';
    } elseif ($old['telephone'] !== '' && mb_strlen($old['telephone']) > 30) {
        $error = 'Numéro de téléphone trop long.';
    } elseif ($old['telephone'] !== '' && !preg_match('/^[0-9+\s().-]{6,30}$/', $old['telephone'])) {
        $error = 'Numéro de téléphone invalide.';
    } elseif (mb_strlen($password) < 10) {
        $error = 'Le mot de passe doit contenir au moins 10 caractères.';
    } elseif (mb_strlen($password) > 4096) {
        $error = 'Mot de passe trop long.';
    } else {
        try {
            $response = $api->request('POST', 'api/auth/register', [
                'nom'       => $old['nom'],
                'prenom'    => $old['prenom'],
                'email'     => $old['email'],
                'telephone' => $old['telephone'] !== '' ? $old['telephone'] : null,
                'password'  => $password,
            ]);

            $data = ClientApi::data($response);

            if (!empty($data['token'])) {
                session_regenerate_id(true);
                $_SESSION['client_user']  = $data['user'] ?? ['email' => $old['email']];
                $_SESSION['client_token'] = $data['token'];
                header('Location: ?page=account');
                exit;
            }

            if (!empty($data['message'])) {
                $error = (string)$data['message'];
            } elseif (!empty($data['errors']) && is_array($data['errors'])) {
                $error = (string)reset($data['errors']);
            } else {
                $error = "Une erreur est survenue lors de la création du compte.";
            }
        } catch (Throwable $e) {
            error_log('Client register failure: ' . $e->getMessage());
            $error = "Le service d'inscription est temporairement indisponible. Réessayez plus tard.";
        }
    }

    if ($error === '') {
        $success = 'Votre compte a bien été créé. Redirection en cours…';
        header('Refresh: 2; url=?page=login');
    }
}

$csrf = client_csrf_field();
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Créer un compte — Tourisme Ivoire</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
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
      animation: fadeIn 0.8s ease-out;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to   { opacity: 1; transform: translateY(0); }
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

    .notice {
      padding: 0.7rem 0.85rem;
      border-radius: 8px;
      font-size: 0.78rem;
      margin-bottom: 0.9rem;
      font-weight: 500;
      position: relative;
      z-index: 1;
    }

    .notice.error {
      background: #fdecea;
      color: #e74c3c;
      border-left: 4px solid #e74c3c;
      animation: shake 0.4s ease-in-out;
    }

    .notice.success {
      background: #e8f8ef;
      color: #1e8f4e;
      border-left: 4px solid #1e8f4e;
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-4px); }
      75% { transform: translateX(4px); }
    }

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

    .input-wrapper input.is-invalid {
      border-color: #e74c3c;
      background: #fff;
    }

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
      transition: background 0.25s ease;
    }

    .strength-bar.active-1 { background: #e74c3c; }
    .strength-bar.active-2 { background: #f39c12; }
    .strength-bar.active-3 { background: #f1c40f; }
    .strength-bar.active-4 { background: #27ae60; }

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

    @media (max-width: 520px) {
      body.auth-page { padding: 0.5rem; }
      .auth-card { max-width: 100%; padding: 1rem 1rem; }
      .auth-card h1 { font-size: 1.2rem; }
    }

    @media (max-width: 420px) {
      .form-grid { grid-template-columns: 1fr; }
      .field.full { grid-column: auto; }
    }
  </style>
</head>
<body class="auth-page">

  <section class="auth-card" aria-labelledby="register-title">

    <div class="auth-header">
      <span class="eyebrow">Rejoignez-nous</span>
      <h1 id="register-title">Créer un compte</h1>
      <p class="row-meta">
        Votre espace personnel vous accompagne dans chaque découverte.
      </p>
    </div>

    <?php if ($error !== ''): ?>
      <div class="notice error" role="alert"><?= client_h($error) ?></div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
      <div class="notice success" role="status"><?= client_h($success) ?></div>
    <?php endif; ?>

    <form method="post" class="form-grid" autocomplete="on" novalidate>
      <?= $csrf ?>
      <input type="hidden" name="action" value="register">

      <div class="field">
        <label for="nom">Nom</label>
        <div class="input-wrapper">
          <input
            id="nom"
            name="nom"
            type="text"
            required
            maxlength="100"
            autocomplete="family-name"
            placeholder="Votre nom"
            value="<?= client_h($old['nom']) ?>">
        </div>
      </div>

      <div class="field">
        <label for="prenom">Prénom</label>
        <div class="input-wrapper">
          <input
            id="prenom"
            name="prenom"
            type="text"
            maxlength="100"
            autocomplete="given-name"
            placeholder="Votre prénom"
            value="<?= client_h($old['prenom']) ?>">
        </div>
      </div>

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
            placeholder="vous@exemple.com"
            value="<?= client_h($old['email']) ?>">
        </div>
      </div>

      <div class="field full">
        <label for="telephone">
          Téléphone <span class="label-optional">(facultatif)</span>
        </label>
        <div class="input-wrapper">
          <input
            id="telephone"
            name="telephone"
            type="tel"
            maxlength="30"
            autocomplete="tel"
            inputmode="tel"
            placeholder="+225 XX XX XX XX"
            value="<?= client_h($old['telephone']) ?>">
        </div>
      </div>

      <div class="field full">
        <label for="password">
          Mot de passe <span class="label-optional">(10 car. min)</span>
        </label>
        <div class="input-wrapper">
          <input
            id="password"
            name="password"
            type="password"
            required
            minlength="10"
            maxlength="4096"
            autocomplete="new-password"
            placeholder="••••••••••">
          <button
            type="button"
            class="toggle-password"
            data-password-toggle="password"
            aria-label="Afficher le mot de passe"
            aria-pressed="false"
          >Afficher</button>
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

  <script>
    document.querySelectorAll('[data-password-toggle]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var input = document.getElementById(btn.dataset.passwordToggle);
        if (!input) return;
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.textContent = show ? 'Masquer' : 'Afficher';
        btn.setAttribute('aria-pressed', show ? 'true' : 'false');
        btn.setAttribute('aria-label', show ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
        input.focus();
      });
    });

    (function () {
      var input = document.getElementById('password');
      var bars  = document.querySelectorAll('.password-strength .strength-bar');
      if (!input || !bars.length) return;

      input.addEventListener('input', function () {
        var v = input.value;
        var score = 0;
        if (v.length >= 10) score++;
        if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score++;
        if (/\d/.test(v)) score++;
        if (/[^A-Za-z0-9]/.test(v)) score++;

        bars.forEach(function (bar, i) {
          bar.classList.remove('active-1', 'active-2', 'active-3', 'active-4');
          if (i < score) bar.classList.add('active-' + score);
        });
      });
    })();
  </script>
</body>
</html>