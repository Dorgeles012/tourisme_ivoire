<?php
declare(strict_types=1);
require_once dirname(__DIR__,2) . '/admin/bootstrap.php';
admin_require_module('virtual_visits',true);
$id = filter_var($_GET['id'] ?? null,FILTER_VALIDATE_INT);
$visit = ['titre'=>'','type_visite'=>'360','url'=>'','fournisseur'=>'','patrimoine_id'=>'','destination_id'=>'','statut'=>'inactif'];
$error = '';
if ($id) {
    $stmt = db()->prepare('SELECT * FROM visites_virtuelles WHERE id=?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) { http_response_code(404); exit('Visite virtuelle introuvable.'); }
    $visit = $found;
}
$heritages = db()->query('SELECT id,titre FROM patrimoines ORDER BY titre')->fetchAll();
$destinations = db()->query('SELECT id,nom FROM destinations WHERE deleted_at IS NULL ORDER BY nom')->fetchAll();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_check_csrf();
    $visit['titre'] = trim((string)($_POST['titre'] ?? ''));
    $visit['type_visite'] = (string)($_POST['type_visite'] ?? '');
    $visit['url'] = trim((string)($_POST['url'] ?? ''));
    $visit['fournisseur'] = trim((string)($_POST['fournisseur'] ?? ''));
    $visit['patrimoine_id'] = filter_var($_POST['patrimoine_id'] ?? null,FILTER_VALIDATE_INT);
    $visit['destination_id'] = filter_var($_POST['destination_id'] ?? null,FILTER_VALIDATE_INT);
    $visit['statut'] = (string)($_POST['statut'] ?? 'inactif');
    $url = filter_var($visit['url'],FILTER_VALIDATE_URL);
    $scheme = strtolower((string)parse_url($visit['url'],PHP_URL_SCHEME));
    if ($visit['titre']==='' || mb_strlen($visit['titre'])>180 || !$url || !in_array($scheme,['http','https'],true) || mb_strlen($visit['url'])>500 || mb_strlen($visit['fournisseur'])>120) {
        $error = 'Titre, URL HTTP(S) valide et fournisseur de 120 caractères maximum requis.';
    } elseif (!in_array($visit['type_visite'],['360','video','ar','avant_apres'],true) || !in_array($visit['statut'],['actif','inactif'],true)) {
        $error = 'Type ou état de la visite invalide.';
    } elseif ((bool)$visit['patrimoine_id']===(bool)$visit['destination_id']) {
        $error = 'Associez la visite à un seul patrimoine ou à une seule destination.';
    } else {
        $parentId = $visit['patrimoine_id'] ?: $visit['destination_id'];
        $parentTable = $visit['patrimoine_id'] ? 'patrimoines' : 'destinations';
        $check = db()->prepare("SELECT id FROM {$parentTable} WHERE id=?" . ($parentTable==='destinations' ? ' AND deleted_at IS NULL' : ''));
        $check->execute([$parentId]);
        if (!$check->fetchColumn()) {
            $error = 'Le contenu associé est introuvable.';
        } elseif ($visit['statut']==='actif') {
            $publicationColumn = $parentTable==='destinations' ? 'statut_publication' : 'statut';
            $publication = db()->prepare("SELECT {$publicationColumn} FROM {$parentTable} WHERE id=?");
            $publication->execute([$parentId]);
            if ($publication->fetchColumn()!=='publie') $error = 'Publiez le contenu associé avant d’activer cette visite virtuelle.';
        }
        if ($error==='') {
            $patrimoineId = $visit['patrimoine_id'] ?: null;
            $destinationId = $visit['destination_id'] ?: null;
            $values = [$patrimoineId,$destinationId,$visit['titre'],$visit['type_visite'],$visit['url'],$visit['fournisseur'] ?: null,$visit['statut']];
            if ($id) {
                db()->prepare('UPDATE visites_virtuelles SET patrimoine_id=?,destination_id=?,titre=?,type_visite=?,url=?,fournisseur=?,statut=?,is_demo=0 WHERE id=?')
                    ->execute([...$values,$id]);
            } else {
                db()->prepare('INSERT INTO visites_virtuelles(patrimoine_id,destination_id,titre,type_visite,url,fournisseur,statut,is_demo) VALUES(?,?,?,?,?,?,?,0)')
                    ->execute($values);
            }
            admin_flash('Visite virtuelle enregistrée.');
            header('Location: index.php?module=virtual_visits');
            exit;
        }
    }
}
$csrf = admin_csrf();
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= $id?'Modifier':'Créer' ?> une visite virtuelle — Administration</title><link rel="stylesheet" href="style.css"></head>
<body class="admin-page">
<?php require __DIR__ . '/_sidebar.php'; ?><header class="topbar"><strong>TOURISME IVOIRE · ADMIN</strong><a href="index.php?module=virtual_visits">Retour aux visites virtuelles</a></header><main class="content editor"><h1><?= $id?'Modifier':'Créer' ?> une visite virtuelle</h1>
<?php if ($error!==''): ?><div class="notice error"><?= admin_escape($error) ?></div><?php endif; ?>
<form class="panel" method="post"><input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>">
  <div class="editor-grid">
    <div class="wide"><label for="titre">Titre</label><input id="titre" name="titre" maxlength="180" required value="<?= admin_escape($visit['titre']) ?>"></div>
    <div><label for="type_visite">Type</label><select id="type_visite" name="type_visite"><?php foreach (['360'=>'Panorama 360°','video'=>'Vidéo','ar'=>'Réalité augmentée','avant_apres'=>'Avant / après'] as $type=>$label): ?><option value="<?= admin_escape($type) ?>" <?= $visit['type_visite']===$type?'selected':'' ?>><?= admin_escape($label) ?></option><?php endforeach; ?></select></div>
    <div><label for="statut">État</label><select id="statut" name="statut"><option value="inactif" <?= $visit['statut']==='inactif'?'selected':'' ?>>inactif</option><option value="actif" <?= $visit['statut']==='actif'?'selected':'' ?>>actif</option></select></div>
    <div class="wide"><label for="url">URL de la visite</label><input id="url" name="url" type="url" maxlength="500" required value="<?= admin_escape($visit['url']) ?>"></div>
    <div class="wide"><label for="fournisseur">Fournisseur (facultatif)</label><input id="fournisseur" name="fournisseur" maxlength="120" value="<?= admin_escape($visit['fournisseur']) ?>"></div>
    <div><label for="patrimoine_id">Patrimoine associé</label><select id="patrimoine_id" name="patrimoine_id"><option value="">Aucun</option><?php foreach ($heritages as $row): ?><option value="<?= admin_escape($row['id']) ?>" <?= (string)$visit['patrimoine_id']===(string)$row['id']?'selected':'' ?>><?= admin_escape($row['titre']) ?></option><?php endforeach; ?></select></div>
    <div><label for="destination_id">Destination associée</label><select id="destination_id" name="destination_id"><option value="">Aucune</option><?php foreach ($destinations as $row): ?><option value="<?= admin_escape($row['id']) ?>" <?= (string)$visit['destination_id']===(string)$row['id']?'selected':'' ?>><?= admin_escape($row['nom']) ?></option><?php endforeach; ?></select></div>
  </div>
  <p class="muted">Associez la visite à un seul patrimoine ou à une seule destination. Une visite nouvellement créée reste inactive par défaut.</p>
  <p><button class="button primary" type="submit">Enregistrer</button></p>
</form></main></body></html>
