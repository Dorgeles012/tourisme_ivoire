<?php
declare(strict_types=1);
require_once dirname(__DIR__,2) . '/admin/bootstrap.php';
admin_require_role(['administrateur']);
$error='';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET')==='POST') {
    admin_check_csrf();
    $action=(string)($_POST['action'] ?? '');
    try {
        if ($action==='department') {
            $parentValue=(string)($_POST['parent'] ?? '');
            [$type,$rawParent]=array_pad(explode(':',$parentValue,2),2,'');
            $parent=filter_var($rawParent,FILTER_VALIDATE_INT);
            $name=trim((string)($_POST['nom'] ?? ''));
            if (!in_array($type,['region','district'],true) || !$parent || $parent<1 || $name==='' || mb_strlen($name)>120) throw new InvalidArgumentException('Parent et nom de département requis.');
            $region=$type==='region'?$parent:null;
            $district=$type==='district'?$parent:null;
            db()->prepare('INSERT INTO departements(region_id,district_id,nom) VALUES(?,?,?)')->execute([$region,$district,$name]);
        } elseif ($action==='locality') {
            $department=filter_var($_POST['departement_id'] ?? null,FILTER_VALIDATE_INT);
            $name=trim((string)($_POST['nom'] ?? ''));
            $slug=strtolower(trim((string)($_POST['slug'] ?? '')));
            $lat=trim((string)($_POST['latitude'] ?? ''));
            $lng=trim((string)($_POST['longitude'] ?? ''));
            $latitude=$lat===''?null:filter_var($lat,FILTER_VALIDATE_FLOAT);
            $longitude=$lng===''?null:filter_var($lng,FILTER_VALIDATE_FLOAT);
            if (!$department || $department<1 || $name==='' || mb_strlen($name)>140 || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/',$slug)) throw new InvalidArgumentException('Département, nom et slug valides requis.');
            if (($lat==='' xor $lng==='') || ($latitude!==null && ($latitude===false || $latitude < -90 || $latitude > 90)) || ($longitude!==null && ($longitude===false || $longitude < -180 || $longitude > 180))) throw new InvalidArgumentException('Coordonnées invalides ou incomplètes.');
            db()->prepare('INSERT INTO localites(departement_id,nom,slug,latitude,longitude) VALUES(?,?,?,?,?)')->execute([$department,$name,$slug,$latitude,$longitude]);
        } elseif ($action==='district_region') {
            $district=filter_var($_POST['district_id'] ?? null,FILTER_VALIDATE_INT);
            $region=filter_var($_POST['region_id'] ?? null,FILTER_VALIDATE_INT);
            $source=trim((string)($_POST['source_reference'] ?? ''));
            $scheme=strtolower((string)parse_url($source,PHP_URL_SCHEME));
            if (!$district || !$region || $district<1 || $region<1 || !filter_var($source,FILTER_VALIDATE_URL) || !in_array($scheme,['http','https'],true)) throw new InvalidArgumentException('Choisissez un district et une région et indiquez une source HTTP(S) officielle.');
            db()->prepare('INSERT INTO districts_regions(district_id,region_id,source_reference,verified_at) VALUES(?,?,?,NOW())')->execute([$district,$region,$source]);
        } elseif ($action==='remove_district_region') {
            $district=filter_var($_POST['district_id'] ?? null,FILTER_VALIDATE_INT);
            $region=filter_var($_POST['region_id'] ?? null,FILTER_VALIDATE_INT);
            if (!$district || !$region) throw new InvalidArgumentException('Relation invalide.');
            db()->prepare('DELETE FROM districts_regions WHERE district_id=? AND region_id=?')->execute([$district,$region]);
        } else {
            throw new InvalidArgumentException('Action inconnue.');
        }
        admin_flash('Référentiel géographique mis à jour.');
        header('Location: geography.php');
        exit;
    } catch (Throwable $e) {
        error_log((string)$e);
        $error=$e instanceof InvalidArgumentException?$e->getMessage():'Enregistrement impossible; vérifiez les doublons et les rattachements.';
    }
}
$regions=db()->query('SELECT id,nom FROM regions WHERE statut="actif" ORDER BY nom')->fetchAll();
$districts=db()->query('SELECT id,nom FROM districts WHERE statut="actif" ORDER BY nom')->fetchAll();
$departments=db()->query('SELECT d.id,d.nom,COALESCE(r.nom,di.nom) AS parent FROM departements d LEFT JOIN regions r ON r.id=d.region_id LEFT JOIN districts di ON di.id=d.district_id ORDER BY parent,d.nom')->fetchAll();
$relations=db()->query('SELECT d.id AS district_id,d.nom AS district,r.id AS region_id,r.nom AS region,x.source_reference FROM districts_regions x JOIN districts d ON d.id=x.district_id JOIN regions r ON r.id=x.region_id ORDER BY d.nom,r.nom')->fetchAll();
$csrf=admin_csrf();
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Géographie — Administration</title><link rel="stylesheet" href="style.css"></head>
<body><header class="topbar"><strong>TOURISME IVOIRE · ADMIN</strong><a href="index.php">Tableau de bord</a></header><main class="content">
<h1>Référentiel géographique</h1><p class="muted">N’ajoutez un rattachement district-région que si une source officielle l’atteste. Les coordonnées restent facultatives lorsqu’elles ne sont pas vérifiées.</p>
<?php if ($error!==''): ?><div class="notice error"><?= admin_escape($error) ?></div><?php endif; ?>
<div class="editor-grid">
<form class="panel" method="post"><h2>Ajouter un département</h2><input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>"><input type="hidden" name="action" value="department"><label>Région / district</label><select name="parent" required><optgroup label="Régions"><?php foreach($regions as $row): ?><option value="region:<?= admin_escape($row['id']) ?>"><?= admin_escape($row['nom']) ?></option><?php endforeach; ?></optgroup><optgroup label="Districts"><?php foreach($districts as $row): ?><option value="district:<?= admin_escape($row['id']) ?>"><?= admin_escape($row['nom']) ?></option><?php endforeach; ?></optgroup></select><label>Nom du département</label><input name="nom" required><p><button class="button primary">Ajouter</button></p></form>
<form class="panel" method="post"><h2>Ajouter une localité</h2><input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>"><input type="hidden" name="action" value="locality"><label>Département</label><select name="departement_id" required><?php foreach($departments as $row): ?><option value="<?= admin_escape($row['id']) ?>"><?= admin_escape($row['nom']) ?> — <?= admin_escape($row['parent']) ?></option><?php endforeach; ?></select><label>Nom</label><input name="nom" required><label>Slug</label><input name="slug" required><div class="editor-grid"><div><label>Latitude vérifiée</label><input name="latitude" inputmode="decimal"></div><div><label>Longitude vérifiée</label><input name="longitude" inputmode="decimal"></div></div><p><button class="button primary">Ajouter</button></p></form>
<form class="panel" method="post"><h2>Documenter une relation district-région</h2><input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>"><input type="hidden" name="action" value="district_region"><label>District</label><select name="district_id"><?php foreach($districts as $row): ?><option value="<?= admin_escape($row['id']) ?>"><?= admin_escape($row['nom']) ?></option><?php endforeach; ?></select><label>Région</label><select name="region_id"><?php foreach($regions as $row): ?><option value="<?= admin_escape($row['id']) ?>"><?= admin_escape($row['nom']) ?></option><?php endforeach; ?></select><label>Source officielle</label><input name="source_reference" type="url" required placeholder="https://…"><p><button class="button primary">Enregistrer le rattachement vérifié</button></p></form>
</div>
<h2>Relations district-région enregistrées</h2><div class="table-wrap"><table><thead><tr><th>District</th><th>Région</th><th>Source</th><th></th></tr></thead><tbody><?php foreach($relations as $row): ?><tr><td><?= admin_escape($row['district']) ?></td><td><?= admin_escape($row['region']) ?></td><td><?php if ($row['source_reference']): ?><a href="<?= admin_escape($row['source_reference']) ?>" rel="noopener noreferrer" target="_blank">Consulter</a><?php else: ?>Non renseignée<?php endif; ?></td><td><form method="post"><input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>"><input type="hidden" name="action" value="remove_district_region"><input type="hidden" name="district_id" value="<?= admin_escape($row['district_id']) ?>"><input type="hidden" name="region_id" value="<?= admin_escape($row['region_id']) ?>"><button type="submit">Retirer</button></form></td></tr><?php endforeach; ?><?php if(!$relations): ?><tr><td colspan="4">Aucune relation documentée.</td></tr><?php endif; ?></tbody></table></div>
</main></body></html>
