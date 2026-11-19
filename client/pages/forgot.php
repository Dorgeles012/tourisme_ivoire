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
$old     = ['email' => ''];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    client_check_csrf();

    $old['email'] = strtolower(trim((string)($_POST['email'] ?? '')));

    if ($old['email'] === '') {
        $error = "L'adresse e-mail est obligatoire.";
    } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse e-mail invalide.';
    } elseif (mb_strlen($old['email']) > 190) {
        $error = 'Adresse e-mail trop longue.';
    } else {
        try {
            $response = $api->request('POST', 'api/auth/forgot', [
                'email' => $old['email'],
            ]);
            ClientApi::data($response);

            $success = 'Si un compte correspond à cette adresse, un code vous a été envoyé. Vérifiez votre boîte de réception.';
        } catch (Throwable $e) {
            error_log('Client forgot failure: ' . $e->getMessage());
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
  <title>Mot de passe oublié — Tourisme Ivoire</title>
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

    .input-wrapper {
      position: relative;
      display: flex;
      align-items: center;
      width: 100%;
    }

    .input-wrapper input {
      width: 100%;
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
      transition: color 0.2s ease;
    }

    .text-link:hover { color: var(--auth-primary-hover); }

    @media (max-width: 520px) {
      body.auth-page { padding: 0.5rem; }
      .auth-card {
        max-width: 100%;
        margin: 0.75rem auto;
        padding: 1.5rem 1.1rem 1.2rem;
      }
      .auth-card h1 { font-size: 1.3rem; }
    }
  </style>
</head>
<body class="auth-page">

  <section class="auth-card auth-forgot" aria-labelledby="forgot-title">

    <div class="auth-header">
      <span class="eyebrow">Accès au compte</span>
      <h1 id="forgot-title">Mot de passe oublié</h1>
      <p class="row-meta">
        Nous vous enverrons un code si un compte correspond à cette adresse.
      </p>
    </div>

    <?php if ($error !== ''): ?>
      <div class="notice error" role="alert"><?= client_h($error) ?></div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
      <div class="notice success" role="status"><?= client_h($success) ?></div>
    <?php endif; ?>

    <form method="post" class="form-grid" autocomplete="on" data-client-validation novalidate>
      <?= $csrf ?>
      <input type="hidden" name="action" value="forgot">

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

      <div class="form-actions">
        <button class="btn" type="submit">
          <span>Envoyer le code</span>
        </button>
      </div>
    </form>

    <p class="auth-switch">
      <a class="text-link" href="?page=login">← Retour à la connexion</a>
    </p>

  </section>

</body>
</html>