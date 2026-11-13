<?php
declare(strict_types=1);
require_once dirname(__DIR__,2) . '/admin/bootstrap.php';
admin_require_role_management();
$roles = db()->query("SELECT code,nom,description FROM roles WHERE code IN ('Sadmin','Client') ORDER BY CASE code WHEN 'Sadmin' THEN 0 ELSE 1 END")->fetchAll();
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Rôles système — Administration</title><link rel="stylesheet" href="style.css"></head><body class="admin-page">
<?php require __DIR__ . '/_sidebar.php'; ?>
<header class="topbar"><strong>TOURISME IVOIRE · ADMIN</strong><a href="index.php">Retour au tableau de bord</a></header>
<main class="content"><p class="eyebrow">SÉCURITÉ</p><h1>Rôles système</h1><p class="muted">L’application utilise deux rôles fixes. Leur code est référencé par les comptes utilisateurs et ne peut pas être modifié ici.</p><section class="panel"><table><thead><tr><th>Code</th><th>Nom</th><th>Accès</th></tr></thead><tbody>
<?php foreach ($roles as $role): ?><tr><td><code><?= admin_escape($role['code']) ?></code></td><td><?= admin_escape($role['nom']) ?></td><td><?= $role['code']==='Sadmin'?'Back-office et administration complète':'Espace voyageur et fonctionnalités client' ?></td></tr><?php endforeach; ?>
</tbody></table></section><p><a class="button" href="index.php?module=users">Gérer les comptes utilisateurs</a></p></main></body></html>
