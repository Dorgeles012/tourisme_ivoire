<?php
declare(strict_types=1);
require_once dirname(__DIR__,2) . '/admin/bootstrap.php';
$actor = admin_require_role_management();
$error = '';
$name = '';
$email = '';
$roleCode = 'Client';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_check_csrf();
    $name = trim((string)($_POST['nom'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $roleCode = (string)($_POST['role'] ?? 'Client');
    if ($name === '' || mb_strlen($name) > 100 || !filter_var($email,FILTER_VALIDATE_EMAIL) || mb_strlen($email)>190 || strlen($password)<10 || !in_array($roleCode,['Client','Sadmin'],true)) {
        $error = 'Nom, e-mail, rôle ou mot de passe invalide (10 caractères minimum).';
    } else {
        $pdo = db();
        $duplicate = $pdo->prepare('SELECT 1 FROM utilisateurs WHERE email=? LIMIT 1');
        $duplicate->execute([$email]);
        if ($duplicate->fetchColumn()) {
            $error = 'Cette adresse e-mail est déjà utilisée.';
        } else {
            $role = $pdo->prepare('SELECT code FROM roles WHERE code=? LIMIT 1');
            $role->execute([$roleCode]);
            $canonicalRole = $role->fetchColumn();
            if (!$canonicalRole) {
                $error = 'Les rôles canoniques manquent. Appliquez le schéma de la base.';
            } else {
                $pdo->beginTransaction();
                try {
                    $insert = $pdo->prepare("INSERT INTO utilisateurs(nom,email,`role`,mot_de_passe_hash,statut) VALUES(?,?,?,?,'actif')");
                    $insert->execute([$name,$email,$canonicalRole,password_hash($password,PASSWORD_DEFAULT)]);
                    $userId = (int)$pdo->lastInsertId();
                    $pdo->prepare('INSERT INTO passeports(utilisateur_id,numero,date_emission) VALUES(?,?,CURRENT_DATE)')->execute([$userId,'TI-'.strtoupper(bin2hex(random_bytes(6)))]);
                    $pdo->prepare('INSERT INTO parametres_utilisateurs(utilisateur_id) VALUES(?)')->execute([$userId]);
                    $pdo->commit();
                    admin_flash('Compte créé avec le rôle '.$roleCode.'.');
                    header('Location: index.php?module=users');
                    exit;
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    error_log((string)$e);
                    $error = 'Impossible de créer ce compte.';
                }
            }
        }
    }
}
$csrf = admin_csrf();
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Créer un utilisateur — Administration</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #e67e22;
      --primary-dark: #d35400;
      --border-color: #e9ecef;
      --bg-body: #f4f6f9;
    }
    * { font-family: 'Inter', sans-serif; }
    body { background: var(--bg-body); min-height: 100vh; margin: 0; }

    /* Topbar */
    .topbar {
      background: #fff;
      border-bottom: 1px solid var(--border-color);
      padding: 0.85rem 1.75rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: sticky;
      top: 0;
      z-index: 100;
    }
    .topbar strong { font-weight: 700; letter-spacing: 0.5px; color: #1a1d29; font-size: 0.95rem; }
    .topbar a {
      color: #4a5568; text-decoration: none; font-size: 0.875rem; font-weight: 500;
      padding: 0.45rem 1rem; border-radius: 8px; border: 1px solid var(--border-color);
      background: #fff; transition: all 0.2s;
    }
    .topbar a:hover { background: #f8f9fb; border-color: #d0d5dd; color: #1a1d29; }

    /* Content */
    .content { padding: 2.5rem 2rem; max-width: 720px; margin: 0 auto; }
    .content h1 { font-size: 1.75rem; font-weight: 700; color: #1a1d29; margin-bottom: 0.25rem; }
    .muted { color: #8892a6; font-size: 0.9rem; margin-bottom: 1.75rem; }

    /* Panel / Form card */
    .panel {
      background: #fff; border-radius: 14px; padding: 2rem 2.25rem;
      border: 1px solid var(--border-color);
      box-shadow: 0 4px 16px rgba(0,0,0,0.04);
    }
    .panel label {
      display: block; font-size: 0.85rem; font-weight: 600;
      color: #34495e; margin-bottom: 0.4rem; margin-top: 1.1rem;
    }
    .panel label:first-of-type { margin-top: 0; }
    .panel input, .panel select {
      width: 100%; 
      padding: 0.7rem 1rem; font-size: 0.95rem; color: #1a1d29;
      background: #fafbfc; transition: all 0.2s;
    }
    .panel input:focus, .panel select:focus {
      outline: none; border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(230,126,34,0.15); background: #fff;
    }

    /* Buttons */
    .button {
      display: inline-block; text-decoration: none; border-radius: 10px;
      padding: 0.7rem 1.4rem; font-size: 0.9rem; font-weight: 600;
      border: 1px solid var(--border-color); background: #fff; color: #4a5568;
      transition: all 0.2s; cursor: pointer;
    }
    .button:hover { background: #f8f9fb; }
    .button.primary {
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      color: #fff; border: none; box-shadow: 0 4px 12px rgba(230,126,34,0.3);
    }
    .button.primary:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(230,126,34,0.4); }

    /* Notice */
    .notice { padding: 1rem 1.25rem; border-radius: 10px; font-size: 0.875rem; margin-bottom: 1.5rem; font-weight: 500; }
    .notice.error { background: #fdecea; color: #c0392b; border-left: 4px solid #e74c3c; }

    /* Hint */
    .hint {
      font-size: 0.78rem; color: #8892a6; margin-top: 0.35rem;
    }
  </style>
</head>
<body class="admin-page">
<?php require __DIR__ . '/_sidebar.php'; ?>
<header class="topbar">
  <strong>TOURISME IVOIRE · ADMIN</strong>
  <a href="index.php?module=users">← Retour aux utilisateurs</a>
</header>

<main class="content">
  <h1>Créer un utilisateur</h1>
  <p class="muted">Les comptes publics reçoivent toujours le rôle Client. Seul le Sadmin peut créer ou promouvoir un autre Sadmin.</p>

  <?php if ($error!==''): ?>
    <div class="notice error"><?= admin_escape($error) ?></div>
  <?php endif; ?>

  <form class="panel" method="post">
    <input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>">

    <label for="nom">Nom</label>
    <input id="nom" name="nom" maxlength="100" required value="<?= admin_escape($name) ?>" placeholder="Nom complet de l'utilisateur">

    <label for="email">Adresse e-mail</label>
    <input id="email" name="email" type="email" maxlength="190" required value="<?= admin_escape($email) ?>" placeholder="exemple@tourisme.ci">

    <label for="password">Mot de passe</label>
    <input id="password" name="password" type="password" minlength="10" required autocomplete="new-password" placeholder="••••••••••">
    <div class="hint">10 caractères minimum.</div>

    <label for="role">Rôle</label>
    <select id="role" name="role">
      <option value="Client" <?= $roleCode==='Client'?'selected':'' ?>>Client</option>
      <option value="Sadmin" <?= $roleCode==='Sadmin'?'selected':'' ?>>Sadmin</option>
    </select>

    <div style="margin-top:1.75rem">
      <button class="button primary" type="submit">Créer le compte</button>
    </div>
  </form>
</main>
</body>
</html>