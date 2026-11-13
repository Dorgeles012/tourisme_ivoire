<?php
declare(strict_types=1);
require_once dirname(__DIR__,2) . '/admin/bootstrap.php';
admin_require_module('categories',true);
$id = filter_var($_GET['id'] ?? null,FILTER_VALIDATE_INT);
$category = ['nom'=>'','slug'=>'','description'=>'','icone'=>'','ordre'=>0,'statut'=>'actif','parent_id'=>''];
$error = '';
if ($id) {
    $stmt = db()->prepare('SELECT * FROM categories WHERE id=?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) { http_response_code(404); exit('Catégorie introuvable.'); }
    $category = $found;
}
$parents = db()->query('SELECT id,nom FROM categories ORDER BY nom')->fetchAll();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_check_csrf();
    $category['nom'] = trim((string)($_POST['nom'] ?? ''));
    $category['slug'] = strtolower(trim((string)($_POST['slug'] ?? '')));
    $category['description'] = trim((string)($_POST['description'] ?? ''));
    $category['icone'] = trim((string)($_POST['icone'] ?? ''));
    $category['ordre'] = filter_var($_POST['ordre'] ?? 0,FILTER_VALIDATE_INT);
    $category['parent_id'] = filter_var($_POST['parent_id'] ?? null,FILTER_VALIDATE_INT);
    $category['statut'] = (string)($_POST['statut'] ?? '');
    if ($category['nom']==='' || mb_strlen($category['nom'])>100 || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/',$category['slug']) || $category['ordre']===false) {
        $error = 'Nom, slug valide et ordre numérique requis.';
    } elseif (!in_array($category['statut'],['actif','inactif'],true)) {
        $error = 'État invalide.';
    } else {
        $parentId = $category['parent_id'] ?: null;
        if ($id && (int)$parentId===(int)$id) {
            $error = 'Une catégorie ne peut pas être son propre parent.';
        } else {
            if ($id) {
                db()->prepare('UPDATE categories SET parent_id=?,nom=?,slug=?,description=?,icone=?,ordre=?,statut=? WHERE id=?')
                    ->execute([$parentId,$category['nom'],$category['slug'],$category['description'] ?: null,$category['icone'] ?: null,$category['ordre'],$category['statut'],$id]);
            } else {
                db()->prepare('INSERT INTO categories(parent_id,nom,slug,description,icone,ordre,statut) VALUES(?,?,?,?,?,?,?)')
                    ->execute([$parentId,$category['nom'],$category['slug'],$category['description'] ?: null,$category['icone'] ?: null,$category['ordre'],$category['statut']]);
            }
            admin_flash('Catégorie enregistrée.');
            header('Location: index.php?module=categories');
            exit;
        }
    }
}
$csrf = admin_csrf();
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Catégorie — Administration</title><link rel="stylesheet" href="style.css"></head>
<body class="admin-page">
<?php require __DIR__ . '/_sidebar.php'; ?><header class="topbar"><strong>TOURISME IVOIRE · ADMIN</strong><a href="index.php?module=categories">Retour aux catégories</a></header><main class="content editor"><h1><?= $id?'Modifier':'Créer' ?> une catégorie</h1>
<?php if ($error!==''): ?><div class="notice error"><?= admin_escape($error) ?></div><?php endif; ?>
<form class="panel" method="post"><input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>">
<label>Nom</label><input name="nom" maxlength="100" required value="<?= admin_escape($category['nom']) ?>">
<label>Slug</label><input name="slug" required value="<?= admin_escape($category['slug']) ?>">
<label>Description</label><textarea name="description" rows="3"><?= admin_escape($category['description']) ?></textarea>
<label>Icône</label><input name="icone" maxlength="80" value="<?= admin_escape($category['icone']) ?>">
<label>Catégorie parente (facultatif)</label><select name="parent_id"><option value="">Aucune</option><?php foreach ($parents as $row): if ($id && (int)$row['id']===(int)$id) continue; ?><option value="<?= admin_escape($row['id']) ?>" <?= (string)$category['parent_id']===(string)$row['id']?'selected':'' ?>><?= admin_escape($row['nom']) ?></option><?php endforeach; ?></select>
<label>Ordre</label><input name="ordre" type="number" min="0" max="65535" value="<?= admin_escape($category['ordre']) ?>">
<label>État</label><select name="statut"><option value="actif" <?= $category['statut']==='actif'?'selected':'' ?>>actif</option><option value="inactif" <?= $category['statut']==='inactif'?'selected':'' ?>>inactif</option></select>
<p><button class="button primary" type="submit">Enregistrer</button></p></form></main></body></html>
