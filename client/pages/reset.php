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

$error   = '';
$success = '';
$old     = [
    'email' => '',
    'token' => '',
];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    client_check_csrf();

    $old['email'] = strtolower(trim((string)($_POST['email'] ?? '')));
    $old['token'] = trim((string)($_POST['token'] ?? ''));
    $password     = (string)($_POST['password'] ?? '');

    if ($old['email'] === '') {
        $error = "L'adresse e-mail est obligatoire.";
    } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse e-mail invalide.';
    } elseif (mb_strlen($old['email']) > 190) {
        $error = 'Adresse e-mail trop longue.';
    } elseif ($old['token'] === '') {
        $error = 'Le code reçu est obligatoire.';
    } elseif (!preg_match('/^[a-f0-9]{64}$/i', $old['token'])) {
        $error = 'Le code doit contenir 64 caractères hexadécimaux.';
    } elseif (mb_strlen($password) < 10) {
        $error = 'Le mot de passe doit contenir au moins 10 caractères.';
    } elseif (mb_strlen($password) > 4096) {
        $error = 'Mot de passe trop long.';
    } else {
        try {
            $response = $api->request('POST', 'api/auth/reset', [
                'email'    => $old['email'],
                'token'    => $old['token'],
                'password' => $password,
            ]);
            $data = ClientApi::data($response);

            if (!empty($data['message']) && empty($data['success'])) {
                $error = (string)$data['message'];
            } elseif (!empty($data['errors']) && is_array($data['errors'])) {
                $error = (string)reset($data['errors']);
            } else {
                $success = 'Votre mot de passe a bien été mis à jour. Redirection en cours…';
                header('Refresh: 2; url=?page=login');
            }
        } catch (Throwable $e) {
            error_log('Client reset failure: ' . $e->getMessage());
            $error = 'Le service est temporairement indisponible. Réessayez plus tard.';
        }
    }
}

$csrf = client_csrf_field();
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Réinitialiser le mot de passe — Tourisme Ivoire</title>
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
      margin-bottom: 1.5rem;
      position: relative;
      z-index: 1;
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

    .notice {
      padding: 0.75rem 0.85rem;
      border-radius: 8px;
      font-size: 0.8rem;
      margin-bottom: 1rem;
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

    #token {
      font-family: 'SFMono-Regular', Menlo, Consolas, monospace;
      font-size: 0.78rem;
      letter-spacing: 0.05em;
    }

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

    @media (max-width: 520px) {
      body.auth-page { padding: 0.5rem; }
      .auth-card {
        max-width: 100%;
        margin: 0.75rem auto;
        padding: 1.5rem 1.1rem 1.2rem;
      }
      .auth-card h1 { font-size: 1.25rem; }
    }
  </style>
</head>
<body class="auth-page">

  <section class="auth-card auth-reset" aria-labelledby="reset-title">

    <div class="auth-header">
      <span class="eyebrow">Sécurité du compte</span>
      <h1 id="reset-title">Réinitialiser le mot de passe</h1>
      <p class="row-meta">
        Saisissez le code reçu par e-mail et choisissez un nouveau mot de passe.
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
      <input type="hidden" name="action" value="reset">

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
            placeholder="Collez le code à 64 caractères"
            value="<?= client_h($old['token']) ?>">
        </div>
        <small class="hint">Le code figure dans l'e-mail qui vous a été envoyé.</small>
      </div>

      <div class="field full">
        <label for="password">Nouveau mot de passe <span class="label-optional">(10 car. min)</span></label>
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
      </div>

      <div class="form-actions">
        <button class="btn" type="submit">
          <span>Mettre à jour</span>
        </button>
      </div>
    </form>

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
  </script>
</body>
</html>