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
        $stmt = db()->prepare("SELECT u.id,u.nom,u.email,u.mot_de_passe_hash FROM utilisateurs u JOIN utilisateurs_roles ur ON ur.utilisateur_id=u.id JOIN roles r ON r.id=ur.role_id WHERE u.email=? AND u.statut='actif' AND u.deleted_at IS NULL AND r.code IN ('administrateur','editeur') LIMIT 1");
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
  <link rel="stylesheet" href="style.css">
</head>
<body class="login-page">
  <main class="login-card">
    <div class="brand-mark">TI</div>
    <p class="eyebrow">TOURISME IVOIRE</p>
    <h1>Administration</h1>
    <p class="muted">Connectez-vous avec un compte administrateur ou éditeur.</p>
    <?php if ($error !== ''): ?><div class="notice error"><?= admin_escape($error) ?></div><?php endif; ?>
    <form method="post" autocomplete="on">
      <input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>">
      <label for="email">Adresse e-mail</label>
      <input id="email" name="email" type="email" required autocomplete="username" maxlength="190">
      <label for="password">Mot de passe</label>
      <input id="password" name="password" type="password" required autocomplete="current-password">
      <button class="button primary full" type="submit">Se connecter</button>
    </form>
  </main>
</body>
</html>
