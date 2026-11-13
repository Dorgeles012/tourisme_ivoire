<?php
declare(strict_types=1);
require_once dirname(__DIR__,2) . '/admin/bootstrap.php';
admin_require_module('heritage',true);
$id = filter_var($_GET['id'] ?? null,FILTER_VALIDATE_INT);
$heritage = ['titre'=>'','slug'=>'','localite_id'=>'','categorie_id'=>'','contenu'=>'','periode'=>'','statut'=>'brouillon'];
$error = '';
if ($id) {
    $stmt = db()->prepare('SELECT * FROM patrimoines WHERE id=?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) { http_response_code(404); exit('Patrimoine introuvable.'); }
    $heritage = $found;
}
$localities = db()->query("SELECT l.id,CONCAT(l.nom,' — ',COALESCE(r.nom,d.nom)) AS label FROM localites l JOIN departements dep ON dep.id=l.departement_id LEFT JOIN regions r ON r.id=dep.region_id LEFT JOIN districts d ON d.id=dep.district_id WHERE l.statut='actif' ORDER BY label")->fetchAll();
$categories = db()->query("SELECT id,nom FROM categories WHERE statut='actif' ORDER BY ordre,nom")->fetchAll();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_check_csrf();
    $heritage['titre'] = trim((string)($_POST['titre'] ?? ''));
    $heritage['slug'] = strtolower(trim((string)($_POST['slug'] ?? '')));
    $heritage['contenu'] = trim((string)($_POST['contenu'] ?? ''));
    $heritage['periode'] = trim((string)($_POST['periode'] ?? ''));
    $heritage['localite_id'] = filter_var($_POST['localite_id'] ?? null,FILTER_VALIDATE_INT);
    $heritage['categorie_id'] = filter_var($_POST['categorie_id'] ?? null,FILTER_VALIDATE_INT);
    $heritage['statut'] = (string)($_POST['statut'] ?? 'brouillon');
    if ($heritage['titre']==='' || mb_strlen($heritage['titre'])>180 || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/',$heritage['slug']) || $heritage['contenu']==='' || mb_strlen($heritage['periode'])>120) {
        $error = 'Titre, slug valide et contenu sont requis; la période ne doit pas dépasser 120 caractères.';
    } elseif (!in_array($heritage['statut'],['brouillon','publie','archive'],true)) {
        $error = 'Statut de publication invalide.';
    } else {
        $localityId = $heritage['localite_id'] ?: null;
        $categoryId = $heritage['categorie_id'] ?: null;
        if ($localityId) {
            $check = db()->prepare("SELECT id FROM localites WHERE id=? AND statut='actif'");
            $check->execute([$localityId]);
            if (!$check->fetchColumn()) $error = 'La localité sélectionnée est introuvable ou inactive.';
        }
        if ($error==='' && $categoryId) {
            $check = db()->prepare("SELECT id FROM categories WHERE id=? AND statut='actif'");
            $check->execute([$categoryId]);
            if (!$check->fetchColumn()) $error = 'La catégorie sélectionnée est introuvable ou inactive.';
        }
        if ($error==='') {
            $values = [$localityId,$categoryId,$heritage['titre'],$heritage['slug'],$heritage['contenu'],$heritage['periode'] ?: null,$heritage['statut']];
            if ($id) {
                db()->prepare('UPDATE patrimoines SET localite_id=?,categorie_id=?,titre=?,slug=?,contenu=?,periode=?,statut=?,is_demo=0 WHERE id=?')
                    ->execute([...$values,$id]);
            } else {
                db()->prepare('INSERT INTO patrimoines(localite_id,categorie_id,titre,slug,contenu,periode,statut,is_demo) VALUES(?,?,?,?,?,?,?,0)')
                    ->execute($values);
            }
            admin_flash('Patrimoine enregistré.');
            header('Location: index.php?module=heritage');
            exit;
        }
    }
}
$csrf = admin_csrf();
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= $id?'Modifier':'Créer' ?> un patrimoine — Administration</title><link rel="stylesheet" href="style.css"></head>
<body class="admin-page">
<?php require __DIR__ . '/_sidebar.php'; ?><header class="topbar"><strong>TOURISME IVOIRE · ADMIN</strong><a href="index.php?module=heritage">Retour aux patrimoines</a></header><main class="content editor"><h1><?= $id?'Modifier':'Créer' ?> un patrimoine</h1>
<?php if ($error!==''): ?><div class="notice error"><?= admin_escape($error) ?></div><?php endif; ?>
<form class="panel" method="post"><input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>">
  <div class="editor-grid">
    <div><label for="titre">Titre</label><input id="titre" name="titre" maxlength="180" required value="<?= admin_escape($heritage['titre']) ?>"></div>
    <div><label for="slug">Slug (minuscules et tirets)</label><input id="slug" name="slug" maxlength="200" required value="<?= admin_escape($heritage['slug']) ?>"></div>
    <div><label for="localite_id">Localité</label><select id="localite_id" name="localite_id"><option value="">Aucune</option><?php foreach ($localities as $row): ?><option value="<?= admin_escape($row['id']) ?>" <?= (string)$heritage['localite_id']===(string)$row['id']?'selected':'' ?>><?= admin_escape($row['label']) ?></option><?php endforeach; ?></select></div>
    <div><label for="categorie_id">Catégorie</label><select id="categorie_id" name="categorie_id"><option value="">Aucune</option><?php foreach ($categories as $row): ?><option value="<?= admin_escape($row['id']) ?>" <?= (string)$heritage['categorie_id']===(string)$row['id']?'selected':'' ?>><?= admin_escape($row['nom']) ?></option><?php endforeach; ?></select></div>
    <div class="wide"><label for="periode">Période</label><input id="periode" name="periode" maxlength="120" value="<?= admin_escape($heritage['periode']) ?>"></div>
    <div class="wide"><label for="contenu">Contenu culturel</label><textarea id="contenu" name="contenu" rows="10" required><?= admin_escape($heritage['contenu']) ?></textarea></div>
    <div><label for="statut">Publication</label><select id="statut" name="statut"><?php foreach (['brouillon','publie','archive'] as $status): ?><option value="<?= admin_escape($status) ?>" <?= $heritage['statut']===$status?'selected':'' ?>><?= admin_escape($status) ?></option><?php endforeach; ?></select></div>
  </div>
  <p><button class="button primary" type="submit">Enregistrer</button></p>
</form></main></body></html>
