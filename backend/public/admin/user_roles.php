<?php
declare(strict_types=1);
require_once dirname(__DIR__,2) . '/admin/bootstrap.php';
$actor = admin_require_role_management();
$userId = filter_var($_GET['id'] ?? null,FILTER_VALIDATE_INT);
if (!$userId || $userId<1) { http_response_code(400); exit('Utilisateur invalide.'); }
$targetQuery = db()->prepare("SELECT id,nom,email,statut,`role` FROM utilisateurs WHERE id=? AND deleted_at IS NULL");
$targetQuery->execute([$userId]);
$target = $targetQuery->fetch();
if (!$target) { http_response_code(404); exit('Utilisateur introuvable.'); }
$actorIsSadmin = in_array('Sadmin',admin_role_codes((int)$actor['id']),true);
$targetIsSadmin = $target['role'] === 'Sadmin';
$currentRoleQuery = db()->prepare('SELECT id FROM roles WHERE code=?');
$currentRoleQuery->execute([$target['role']]);
$currentRoleId = (int)$currentRoleQuery->fetchColumn();
$currentRoleIds = $currentRoleId > 0 ? [$currentRoleId] : [];
if ($targetIsSadmin && !$actorIsSadmin) {
    http_response_code(403);
    exit('Seul un Sadmin peut modifier les rôles d’un compte Sadmin.');
}
$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET')==='POST') {
    admin_check_csrf();
    $rawIds = $_POST['role_ids'] ?? [];
    if (!is_array($rawIds)) $rawIds = [];
    $roleIds = [];
    foreach ($rawIds as $rawId) {
        $roleId = filter_var($rawId,FILTER_VALIDATE_INT);
        if (!$roleId || $roleId<1) { $error='La sélection contient un rôle invalide.'; break; }
        $roleIds[] = (int)$roleId;
    }
    $roleIds = array_values(array_unique($roleIds));
    if ($error==='' && count($roleIds)!==1) $error='Sélectionnez exactement un rôle.';
    if ($error==='') {
        $selectedQuery = db()->prepare("SELECT id,code FROM roles WHERE id=? AND code IN ('Sadmin','Client')");
        $selectedQuery->execute($roleIds);
        $selectedRole = $selectedQuery->fetch();
        if (!$selectedRole) {
            $error='Le rôle sélectionné n’existe plus.';
        } elseif (!$actorIsSadmin && $selectedRole['code']==='Sadmin') {
            $error='Seul un compte Sadmin peut attribuer le rôle Sadmin.';
        }
        if ($error==='') {
            $pdo = db();
            $pdo->beginTransaction();
            try {
                $liveTarget = $pdo->prepare('SELECT `role`,statut FROM utilisateurs WHERE id=? AND deleted_at IS NULL FOR UPDATE');
                $liveTarget->execute([$userId]);
                $lockedTarget = $liveTarget->fetch();
                if (!$lockedTarget) throw new RuntimeException('Utilisateur introuvable.');
                $liveActor = $pdo->prepare("SELECT `role` FROM utilisateurs WHERE id=? AND statut='actif' AND deleted_at IS NULL FOR UPDATE");
                $liveActor->execute([(int)$actor['id']]);
                if ($liveActor->fetchColumn() !== 'Sadmin') throw new RuntimeException('Votre rôle ne permet plus cette opération.');
                if ($lockedTarget['role'] === 'Sadmin' && $lockedTarget['statut'] === 'actif' && $selectedRole['code'] !== 'Sadmin') {
                    $activeSadmins = $pdo->query("SELECT id FROM utilisateurs WHERE `role`='Sadmin' AND statut='actif' AND deleted_at IS NULL FOR UPDATE")->fetchAll(PDO::FETCH_COLUMN);
                    if (count($activeSadmins) <= 1) throw new RuntimeException('Impossible de retirer le dernier rôle Sadmin actif.');
                }
                $pdo->prepare('UPDATE utilisateurs SET `role`=? WHERE id=? AND deleted_at IS NULL')->execute([$selectedRole['code'],$userId]);
                $pdo->commit();
                admin_flash('Rôle de l’utilisateur mis à jour.');
                header('Location: index.php?module=users');
                exit;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log((string)$e);
                $error = $e instanceof RuntimeException ? $e->getMessage() : 'Impossible d’enregistrer le rôle de cet utilisateur.';
            }
        }
    }
}
$availableRoles = db()->query("SELECT id,code,nom,description FROM roles WHERE code IN ('Sadmin','Client') ORDER BY CASE code WHEN 'Sadmin' THEN 0 ELSE 1 END")->fetchAll();
if (!$actorIsSadmin) {
    $availableRoles = array_values(array_filter($availableRoles,static fn(array $role): bool => $role['code']!=='Sadmin'));
}
$csrf = admin_csrf();
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Rôles utilisateur — Administration</title>
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
    body.admin-page { background: var(--bg-body); min-height: 100vh; margin: 0; display: flex; }

    /* Topbar */
    .topbar {
      background: #fff; border-bottom: 1px solid var(--border-color);
      padding: 0.85rem 1.75rem; display: flex; justify-content: space-between;
      align-items: center; position: sticky; top: 0; z-index: 100;
      margin-left: 270px;
    }
    .topbar strong { font-weight: 700; letter-spacing: 0.5px; color: #1a1d29; font-size: 0.95rem; }
    .topbar a {
      color: #4a5568; text-decoration: none; font-size: 0.875rem; font-weight: 500;
      padding: 0.45rem 1rem; border-radius: 8px; border: 1px solid var(--border-color);
      background: #fff; transition: all 0.2s;
    }
    .topbar a:hover { background: #f8f9fb; border-color: #d0d5dd; color: #1a1d29; }

    /* Content */
    .content { padding: 2.5rem 2rem; max-width: 780px; width: 100%; margin: 0 auto; }
    .content h1 { font-size: 1.75rem; font-weight: 700; color: #1a1d29; margin-bottom: 0.25rem; }
    .muted { color: #8892a6; font-size: 0.875rem; }

    /* User header card */
    .user-header {
      display: flex; align-items: center; gap: 1rem;
      background: #fff; border: 1px solid var(--border-color); border-radius: 14px;
      padding: 1.25rem 1.5rem; margin-bottom: 1.5rem;
      box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    }
    .user-avatar {
      width: 52px; height: 52px; border-radius: 50%;
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      color: #fff; display: flex; align-items: center; justify-content: center;
      font-weight: 700; font-size: 1.15rem; flex-shrink: 0;
      box-shadow: 0 4px 12px rgba(230,126,34,0.3);
    }
    .user-header .user-name { font-weight: 600; color: #1a1d29; font-size: 1rem; }
    .user-header .user-meta { font-size: 0.82rem; color: #8892a6; }
    .user-header .badge-status {
      margin-left: auto; font-size: 0.72rem; padding: 0.35rem 0.8rem;
      border-radius: 50px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;
    }
    .badge-actif { background: #e8f8f0; color: #1e8449; }
    .badge-suspendu { background: #fdecea; color: #c0392b; }
    .badge-en_attente { background: #fff8e5; color: #b9770e; }

    /* Panel / Form card */
    .panel {
      background: #fff; border-radius: 14px; padding: 2rem 2.25rem;
      border: 1px solid var(--border-color);
      box-shadow: 0 4px 16px rgba(0,0,0,0.04);
    }
    .panel-intro { color: #4a5568; font-size: 0.9rem; margin-bottom: 1.5rem; }

    /* Rôle cards (radio-style) */
    .role-option {
      display: block; border: 2px solid var(--border-color); border-radius: 12px;
      padding: 1rem 1.25rem; cursor: pointer; transition: all 0.2s;
      background: #fafbfc; margin-bottom: 0.85rem; position: relative;
    }
    .role-option:hover { border-color: #f5cba7; background: #fff; }
    .role-option input[type="checkbox"] {
      position: absolute; opacity: 0; pointer-events: none;
    }
    .role-option .role-head {
      display: flex; align-items: center; gap: 0.75rem;
    }
    .role-option .role-radio {
      width: 20px; height: 20px; border-radius: 50%;
      border: 2px solid #cbd5e1; background: #fff; flex-shrink: 0;
      display: flex; align-items: center; justify-content: center;
      transition: all 0.2s;
    }
    .role-option .role-radio::after {
      content: ''; width: 10px; height: 10px; border-radius: 50%;
      background: var(--primary); transform: scale(0); transition: transform 0.2s;
    }
    .role-option input:checked ~ .role-head .role-radio { border-color: var(--primary); }
    .role-option input:checked ~ .role-head .role-radio::after { transform: scale(1); }
    .role-option:has(input:checked) {
      border-color: var(--primary); background: #fffaf5;
      box-shadow: 0 4px 14px rgba(230,126,34,0.12);
    }
    .role-option .role-name { font-weight: 600; color: #1a1d29; font-size: 0.95rem; }
    .role-option .role-code {
      font-size: 0.72rem; color: #8892a6; font-weight: 500;
      background: #f0f2f5; padding: 0.15rem 0.55rem; border-radius: 6px;
      margin-left: 0.5rem;
    }
    .role-option .role-desc { font-size: 0.82rem; color: #8892a6; margin-top: 0.4rem; margin-left: 2rem; }

    /* Buttons */
    .button {
      display: inline-block; text-decoration: none; border-radius: 10px;
      padding: 0.7rem 1.4rem; font-size: 0.9rem; font-weight: 600;
      border: 1px solid var(--border-color); background: #fff; color: #4a5568;
      transition: all 0.2s; cursor: pointer;
    }
    .button.primary {
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      color: #fff; border: none; box-shadow: 0 4px 12px rgba(230,126,34,0.3);
    }
    .button.primary:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(230,126,34,0.4); }

    /* Notice */
    .notice { padding: 1rem 1.25rem; border-radius: 10px; font-size: 0.875rem; margin-bottom: 1.5rem; font-weight: 500; }
    .notice.error { background: #fdecea; color: #c0392b; border-left: 4px solid #e74c3c; }

    /* Responsive */
    @media (max-width: 992px) {
      body.admin-page { flex-direction: column; }
      .topbar { margin-left: 0; }
      .content { padding: 1.5rem 1rem; }
      .panel { padding: 1.5rem; }
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
  <h1>Rôles de <?= admin_escape($target['nom']) ?></h1>
  <p class="muted mb-4">Gestion des permissions d'accès au back-office.</p>

  <?php if ($error!==''): ?>
    <div class="notice error"><?= admin_escape($error) ?></div>
  <?php endif; ?>

  <!-- Carte d'identité utilisateur -->
  <div class="user-header">
    <div class="user-avatar"><?= admin_escape(mb_strtoupper(mb_substr($target['nom'], 0, 1))) ?></div>
    <div>
      <div class="user-name"><?= admin_escape($target['nom']) ?></div>
      <div class="user-meta"><?= admin_escape($target['email']) ?></div>
    </div>
    <span class="badge-status badge-<?= admin_escape($target['statut']) ?>">
      <?= admin_escape(str_replace('_', ' ', $target['statut'])) ?>
    </span>
  </div>

  <form class="panel" method="post">
    <input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>">

    <p class="panel-intro">
      ℹ️ Chaque utilisateur possède un seul rôle. Le rôle <strong>Client</strong> ne permet pas d’accéder au back-office.
    </p>

    <?php foreach ($availableRoles as $role): ?>
      <label class="role-option">
        <input type="checkbox" name="role_ids[]" value="<?= admin_escape($role['id']) ?>" <?= in_array((int)$role['id'],$currentRoleIds,true)?'checked':'' ?>>
        <div class="role-head">
          <span class="role-radio" aria-hidden="true"></span>
          <span class="role-name"><?= admin_escape($role['nom']) ?></span>
          <span class="role-code"><?= admin_escape($role['code']) ?></span>
        </div>
        <?php if ($role['description']): ?>
          <div class="role-desc"><?= admin_escape($role['description']) ?></div>
        <?php endif; ?>
      </label>
    <?php endforeach; ?>

    <div style="margin-top:1.75rem">
      <button class="button primary" type="submit">Enregistrer les rôles</button>
    </div>
  </form>
</main>
</body>
</html>