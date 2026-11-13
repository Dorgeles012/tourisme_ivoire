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
<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Rôles utilisateur — Administration</title><link rel="stylesheet" href="style.css"></head>
<body class="admin-page">
<?php require __DIR__ . '/_sidebar.php'; ?><header class="topbar"><strong>TOURISME IVOIRE · ADMIN</strong><a href="index.php?module=users">Retour aux utilisateurs</a></header><main class="content editor">
<h1>Rôles de <?= admin_escape($target['nom']) ?></h1><p class="muted"><?= admin_escape($target['email']) ?> · <?= admin_escape($target['statut']) ?></p>
<?php if ($error!==''): ?><div class="notice error"><?= admin_escape($error) ?></div><?php endif; ?>
<form class="panel" method="post"><input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>">
<p>Chaque utilisateur possède un seul rôle. Le rôle Client ne permet pas d’accéder au back-office.</p>
<?php foreach ($availableRoles as $role): ?><label><input type="checkbox" name="role_ids[]" value="<?= admin_escape($role['id']) ?>" <?= in_array((int)$role['id'],$currentRoleIds,true)?'checked':'' ?> style="width:auto"> <?= admin_escape($role['nom']) ?> <small>(<?= admin_escape($role['code']) ?>)</small><?php if ($role['description']): ?><br><span class="muted"><?= admin_escape($role['description']) ?></span><?php endif; ?></label><?php endforeach; ?>
<p><button class="button primary" type="submit">Enregistrer les rôles</button></p></form></main></body></html>
