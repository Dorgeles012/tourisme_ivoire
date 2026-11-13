<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/admin/bootstrap.php';

if (!empty($_SESSION['admin_user']['id'])) {
    header('Location: index.php');
    exit;
}
$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_check_csrf();
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $emailHash = hash('sha256',$email);
    $ipHash = hash('sha256',(string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    $limits = db()->prepare("SELECT SUM(email_hash=? AND reussie=0) AS email_failures,SUM(ip_hash=? AND reussie=0) AS ip_failures FROM tentatives_auth WHERE created_at>=DATE_SUB(NOW(),INTERVAL 15 MINUTE)");
    $limits->execute([$emailHash,$ipHash]);
    $counts = $limits->fetch() ?: ['email_failures'=>0,'ip_failures'=>0];
    if ((int)$counts['email_failures'] >= 5 || (int)$counts['ip_failures'] >= 30) {
        $error = 'Trop de tentatives. Réessayez plus tard.';
    } else {
        $stmt = db()->prepare("SELECT u.id,u.nom,u.email,u.mot_de_passe_hash FROM utilisateurs u WHERE u.email=? AND u.statut='actif' AND u.deleted_at IS NULL AND u.`role`='Sadmin' LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        $valid = $user && password_verify($password,$user['mot_de_passe_hash']);
        db()->prepare('INSERT INTO tentatives_auth(email_hash,ip_hash,reussie) VALUES(?,?,?)')->execute([$emailHash,$ipHash,$valid ? 1 : 0]);
        if ($valid) {
            session_regenerate_id(true);
            unset($user['mot_de_passe_hash']);
            $_SESSION['admin_user'] = $user;
            $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
            header('Location: index.php');
            exit;
        }
        $error = 'Adresse e-mail ou mot de passe incorrect.';
    }
}
$csrf = admin_csrf();
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Connexion administration — Tourisme Ivoire</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary-color: #e67e22; /* Orange chaud, rappelant le désert */
      --primary-hover: #d35400;
      --glass-bg: rgba(255, 255, 255, 0.92);
      --glass-border: rgba(255, 255, 255, 0.4);
    }

    body.login-page {
      font-family: 'Poppins', sans-serif;
      min-height: 100vh;
      margin: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      /* Image de fond avec un overlay sombre pour la lisibilité */
      background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)), 
                  url('https://images.unsplash.com/photo-1509316785289-025f5b846b35?q=80&w=1920&auto=format&fit=crop') no-repeat center center/cover;
      background-attachment: fixed;
    }

    .login-card {
      background: var(--glass-bg);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border: 1px solid var(--glass-border);
      border-radius: 20px;
      padding: 3rem 2.5rem;
      width: 100%;
      max-width: 420px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
      animation: fadeIn 0.8s ease-out;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .brand-mark {
      width: 60px;
      height: 60px;
      background: linear-gradient(135deg, var(--primary-color), #f39c12);
      color: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
      font-size: 1.5rem;
      margin: 0 auto 1.5rem;
      box-shadow: 0 4px 15px rgba(230, 126, 34, 0.3);
    }

    .eyebrow {
      text-align: center;
      font-size: 0.75rem;
      letter-spacing: 2px;
      text-transform: uppercase;
      color: #7f8c8d;
      margin-bottom: 0.5rem;
      font-weight: 600;
    }

    h1 {
      font-size: 1.75rem;
      font-weight: 600;
      text-align: center;
      color: #2c3e50;
      margin-bottom: 0.5rem;
    }

    .muted {
      text-align: center;
      font-size: 0.9rem;
      color: #95a5a6;
      margin-bottom: 2rem;
    }

    .form-label {
      font-size: 0.85rem;
      font-weight: 600;
      color: #34495e;
      margin-bottom: 0.5rem;
    }

    .form-control {
      border: 1px solid #e0e0e0;
      border-radius: 12px;
      padding: 0.75rem 1rem;
      font-size: 0.95rem;
      transition: all 0.3s ease;
      background: rgba(255, 255, 255, 0.8);
    }

    .form-control:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(230, 126, 34, 0.15);
      background: #fff;
    }

    .button.primary.full {
      background: linear-gradient(135deg, var(--primary-color), #f39c12);
      border: none;
      border-radius: 12px;
      padding: 0.85rem;
      font-weight: 600;
      font-size: 1rem;
      color: white;
      width: 100%;
      margin-top: 1.5rem;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(230, 126, 34, 0.3);
    }

    .button.primary.full:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(230, 126, 34, 0.4);
      background: linear-gradient(135deg, var(--primary-hover), var(--primary-color));
    }

    .notice.error {
      background: #fdecea;
      color: #e74c3c;
      border-left: 4px solid #e74c3c;
      padding: 1rem;
      border-radius: 8px;
      font-size: 0.85rem;
      margin-bottom: 1.5rem;
      font-weight: 500;
    }

    /* Ajustement responsive */
    @media (max-width: 480px) {
      .login-card {
        padding: 2rem 1.5rem;
        margin: 1rem;
      }
    }
  </style>
</head>
<body class="login-page">
  <main class="login-card">
    <div class="brand-mark">TI</div>
    <p class="eyebrow">TOURISME IVOIRE</p>
    <h1>Administration</h1>
    <p class="muted">Connectez-vous avec un compte Sadmin.</p>
    
    <?php if ($error !== ''): ?>
        <div class="notice error"><?= admin_escape($error) ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="on">
      <input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>">
      
      <div class="mb-3">
        <label for="email" class="form-label">Adresse e-mail</label>
        <input id="email" name="email" type="email" class="form-control" required autocomplete="username" maxlength="190" placeholder="exemple@tourisme.ci">
      </div>

      <div class="mb-3">
        <label for="password" class="form-label">Mot de passe</label>
        <input id="password" name="password" type="password" class="form-control" required autocomplete="current-password" placeholder="••••••••">
      </div>

      <button class="button primary full" type="submit">Se connecter</button>
    </form>
  </main>
</body>
</html>