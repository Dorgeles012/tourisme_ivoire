<?php
declare(strict_types=1);
require_once dirname(__DIR__,2) . '/admin/bootstrap.php';
admin_require_role(['administrateur','editeur']);
$id = filter_var($_GET['id'] ?? null,FILTER_VALIDATE_INT);
$destination = ['nom'=>'','slug'=>'','localite_id'=>'','categorie_id'=>'','description'=>'','histoire'=>'','latitude'=>'','longitude'=>'','adresse'=>'','conseils_visite'=>'','informations_pratiques'=>'','source_reference'=>'','statut_publication'=>'brouillon'];
$error = '';
if ($id) {
    $stmt = db()->prepare('SELECT * FROM destinations WHERE id=? AND deleted_at IS NULL');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) { http_response_code(404); exit('Destination introuvable.'); }
    $destination = $found;
}
$localities = db()->query("SELECT l.id,CONCAT(l.nom,' — ',COALESCE(r.nom,di.nom)) AS label FROM localites l JOIN departements dep ON dep.id=l.departement_id LEFT JOIN regions r ON r.id=dep.region_id LEFT JOIN districts di ON di.id=dep.district_id WHERE l.statut='actif' ORDER BY label")->fetchAll();
$categories = db()->query("SELECT id,nom FROM categories WHERE statut='actif' ORDER BY ordre,nom")->fetchAll();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_check_csrf();
    $destination['nom'] = trim((string)($_POST['nom'] ?? ''));
    $destination['slug'] = strtolower(trim((string)($_POST['slug'] ?? '')));
    $destination['description'] = trim((string)($_POST['description'] ?? ''));
    $destination['histoire'] = trim((string)($_POST['histoire'] ?? ''));
    $destination['adresse'] = trim((string)($_POST['adresse'] ?? ''));
    $destination['conseils_visite'] = trim((string)($_POST['conseils_visite'] ?? ''));
    $destination['informations_pratiques'] = trim((string)($_POST['informations_pratiques'] ?? ''));
    $destination['source_reference'] = trim((string)($_POST['source_reference'] ?? ''));
    $destination['localite_id'] = filter_var($_POST['localite_id'] ?? null,FILTER_VALIDATE_INT);
    $destination['categorie_id'] = filter_var($_POST['categorie_id'] ?? null,FILTER_VALIDATE_INT);
    $destination['statut_publication'] = (string)($_POST['statut_publication'] ?? 'brouillon');
    $latitude = trim((string)($_POST['latitude'] ?? ''));
    $longitude = trim((string)($_POST['longitude'] ?? ''));
    $destination['latitude'] = $latitude;
    $destination['longitude'] = $longitude;
    $validSlug = preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/',$destination['slug']);
    $sourceUrl = $destination['source_reference'] === '' ? false : filter_var($destination['source_reference'],FILTER_VALIDATE_URL);
    $sourceScheme = is_string($destination['source_reference']) ? strtolower((string)parse_url($destination['source_reference'],PHP_URL_SCHEME)) : '';
    $lat = $latitude === '' ? null : filter_var($latitude,FILTER_VALIDATE_FLOAT);
    $lng = $longitude === '' ? null : filter_var($longitude,FILTER_VALIDATE_FLOAT);
    if ($destination['nom']==='' || mb_strlen($destination['nom'])>180 || !$validSlug || !$destination['description'] || !$destination['localite_id'] || !$destination['categorie_id']) {
        $error = 'Nom, slug, description, localité et catégorie sont requis.';
    } elseif (!in_array($destination['statut_publication'],['brouillon','publie','archive'],true)) {
        $error = 'Statut de publication invalide.';
    } elseif ($destination['statut_publication']==='publie' && (!$sourceUrl || !in_array($sourceScheme,['http','https'],true))) {
        $error = 'Ajoutez une URL de source vérifiable avant de publier.';
    } elseif (($latitude==='' xor $longitude==='') || ($lat!==null && ($lat===false || $lat < -90 || $lat > 90)) || ($lng!==null && ($lng===false || $lng < -180 || $lng > 180))) {
        $error = 'Les coordonnées doivent être fournies ensemble et comprises dans les plages autorisées.';
    } else {
        $values = [
            $destination['localite_id'],$destination['categorie_id'],$destination['nom'],$destination['slug'],
            $destination['description'],$destination['histoire'] ?: null,$lat,$lng,$destination['adresse'] ?: null,
            $destination['conseils_visite'] ?: null,$destination['informations_pratiques'] ?: null,
            $destination['source_reference'] ?: null,$destination['statut_publication'],
        ];
        if ($id) {
            $sql = "UPDATE destinations SET localite_id=?,categorie_id=?,nom=?,slug=?,description=?,histoire=?,latitude=?,longitude=?,adresse=?,conseils_visite=?,informations_pratiques=?,source_reference=?,statut_publication=?,published_at=IF(?='publie',COALESCE(published_at,NOW()),NULL),is_demo=0 WHERE id=?";
            db()->prepare($sql)->execute([...$values,$destination['statut_publication'],$id]);
        } else {
            $sql = "INSERT INTO destinations(localite_id,categorie_id,nom,slug,description,histoire,latitude,longitude,adresse,conseils_visite,informations_pratiques,source_reference,statut_publication,published_at,is_demo) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,IF(?='publie',NOW(),NULL),0)";
            db()->prepare($sql)->execute([...$values,$destination['statut_publication']]);
            $id = (int)db()->lastInsertId();
        }
        admin_flash('Destination enregistrée.');
        header('Location: index.php?module=destinations');
        exit;
    }
}
$csrf = admin_csrf();
?>
<!doctype html>
<html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= $id?'Modifier':'Créer' ?> une destination — Administration</title><link rel="stylesheet" href="style.css"></head>
<body><header class="topbar"><strong>TOURISME IVOIRE · ADMIN</strong><a href="index.php?module=destinations">Retour aux destinations</a></header>
<main class="content editor"><h1><?= $id?'Modifier':'Créer' ?> une destination</h1>
<?php if ($error!==''): ?><div class="notice error"><?= admin_escape($error) ?></div><?php endif; ?>
<form class="panel" method="post"><input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>">
  <div class="editor-grid">
    <div><label>Nom</label><input name="nom" maxlength="180" required value="<?= admin_escape($destination['nom']) ?>"></div>
    <div><label>Slug (minuscules et tirets)</label><input name="slug" required value="<?= admin_escape($destination['slug']) ?>"></div>
    <div><label>Localité</label><select name="localite_id" required><option value="">Choisir</option><?php foreach ($localities as $row): ?><option value="<?= admin_escape($row['id']) ?>" <?= (string)$destination['localite_id']===(string)$row['id']?'selected':'' ?>><?= admin_escape($row['label']) ?></option><?php endforeach; ?></select></div>
    <div><label>Catégorie</label><select name="categorie_id" required><option value="">Choisir</option><?php foreach ($categories as $row): ?><option value="<?= admin_escape($row['id']) ?>" <?= (string)$destination['categorie_id']===(string)$row['id']?'selected':'' ?>><?= admin_escape($row['nom']) ?></option><?php endforeach; ?></select></div>
    <div class="wide"><label>Description</label><textarea name="description" rows="4" required><?= admin_escape($destination['description']) ?></textarea></div>
    <div class="wide"><label>Histoire</label><textarea name="histoire" rows="3"><?= admin_escape($destination['histoire']) ?></textarea></div>
    <div><label>Latitude vérifiée</label><input name="latitude" inputmode="decimal" value="<?= admin_escape($destination['latitude']) ?>"></div>
    <div><label>Longitude vérifiée</label><input name="longitude" inputmode="decimal" value="<?= admin_escape($destination['longitude']) ?>"></div>
    <div class="wide"><label>Adresse</label><input name="adresse" maxlength="255" value="<?= admin_escape($destination['adresse']) ?>"></div>
    <div class="wide"><label>Conseils de visite vérifiés</label><textarea name="conseils_visite" rows="3"><?= admin_escape($destination['conseils_visite']) ?></textarea></div>
    <div class="wide"><label>Informations pratiques vérifiées</label><textarea name="informations_pratiques" rows="3"><?= admin_escape($destination['informations_pratiques']) ?></textarea></div>
    <div class="wide"><label>URL de la source (obligatoire pour publier)</label><input name="source_reference" type="url" maxlength="500" value="<?= admin_escape($destination['source_reference']) ?>"></div>
    <div><label>Publication</label><select name="statut_publication"><?php foreach (['brouillon','publie','archive'] as $status): ?><option value="<?= $status ?>" <?= $destination['statut_publication']===$status?'selected':'' ?>><?= $status ?></option><?php endforeach; ?></select></div>
  </div>
  <p><button class="button primary" type="submit">Enregistrer</button> <?php if ($id): ?><a class="button" href="image.php?destination_id=<?= admin_escape($id) ?>">Gérer les images et licences</a><?php endif; ?></p>
</form></main></body></html>
