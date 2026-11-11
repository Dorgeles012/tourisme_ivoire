<?php
declare(strict_types=1);
require_once dirname(__DIR__,2) . '/admin/bootstrap.php';
admin_require_role(['administrateur','editeur']);
$imageId = filter_var($_GET['id'] ?? null,FILTER_VALIDATE_INT);
$destinationId = filter_var($_GET['destination_id'] ?? $_POST['destination_id'] ?? null,FILTER_VALIDATE_INT);
$error = '';
$image = null;
if ($imageId) {
    $stmt = db()->prepare('SELECT * FROM images_destinations WHERE id=?');
    $stmt->execute([$imageId]);
    $image = $stmt->fetch();
    if (!$image) { http_response_code(404); exit('Image introuvable.'); }
    $destinationId = (int)$image['destination_id'];
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_check_csrf();
    $credit = trim((string)($_POST['credit'] ?? ''));
    $licence = trim((string)($_POST['licence'] ?? ''));
    $alt = trim((string)($_POST['texte_alternatif'] ?? ''));
    if (!$destinationId || $destinationId < 1 || mb_strlen($credit)>190 || mb_strlen($licence)>190 || mb_strlen($alt)>255) {
        $error = 'Destination, crédit, licence ou texte alternatif invalide.';
    } elseif ($imageId) {
        $status = (string)($_POST['statut'] ?? 'inactif');
        if (!in_array($status,['actif','inactif'],true)) $error='État invalide.';
        else {
            $pdo = db();
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE images_destinations SET credit=?,licence=?,texte_alternatif=?,statut=? WHERE id=?')
                ->execute([$credit ?: null,$licence ?: null,$alt ?: null,$status,$imageId]);
            if (!empty($_POST['principale']) && $status==='actif') {
                $pdo->prepare('UPDATE images_destinations SET est_principale=0 WHERE destination_id=?')->execute([$destinationId]);
                $pdo->prepare('UPDATE images_destinations SET est_principale=1 WHERE id=?')->execute([$imageId]);
                $pdo->prepare('UPDATE destinations SET image_principale=(SELECT url FROM images_destinations WHERE id=?) WHERE id=?')->execute([$imageId,$destinationId]);
            } elseif (empty($_POST['principale']) && !empty($image['est_principale'])) {
                $pdo->prepare('UPDATE images_destinations SET est_principale=0 WHERE id=?')->execute([$imageId]);
                $pdo->prepare('UPDATE destinations SET image_principale=NULL WHERE id=?')->execute([$destinationId]);
            }
            $pdo->commit();
            admin_flash('Métadonnées de l’image enregistrées.');
            header('Location: index.php?module=images');
            exit;
        }
    } else {
        $file = $_FILES['image'] ?? null;
        $finfo = is_array($file) && is_file($file['tmp_name'] ?? '') ? new finfo(FILEINFO_MIME_TYPE) : null;
        $mime = $finfo ? $finfo->file($file['tmp_name']) : '';
        $extensions = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name']) || $file['size']<1 || $file['size']>10485760 || !isset($extensions[$mime])) {
            $error = 'Choisissez une image JPEG, PNG ou WebP de 10 Mo maximum.';
        } elseif ($credit==='' || $licence==='') {
            $error = 'Le crédit et la licence d’utilisation sont obligatoires.';
        } else {
            $check = db()->prepare('SELECT id FROM destinations WHERE id=? AND deleted_at IS NULL');
            $check->execute([$destinationId]);
            if (!$check->fetchColumn()) {
                $error='Destination introuvable.';
            } else {
                $directory = dirname(__DIR__) . '/uploads/images';
                if (!is_dir($directory) && !mkdir($directory,0750,true) && !is_dir($directory)) throw new RuntimeException('Impossible de créer le répertoire image.');
                $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
                $path = $directory . '/' . $filename;
                if (!move_uploaded_file($file['tmp_name'],$path)) throw new RuntimeException('Échec du stockage de l’image.');
                try {
                    $url = '/uploads/images/' . $filename;
                    $pdo = db();
                    $pdo->beginTransaction();
                    if (!empty($_POST['principale'])) $pdo->prepare('UPDATE images_destinations SET est_principale=0 WHERE destination_id=?')->execute([$destinationId]);
                    $pdo->prepare('INSERT INTO images_destinations(destination_id,url,texte_alternatif,credit,licence,est_principale,statut) VALUES(?,?,?,?,?,?,?)')
                        ->execute([$destinationId,$url,$alt ?: null,$credit,$licence,!empty($_POST['principale']) ? 1 : 0,'actif']);
                    if (!empty($_POST['principale'])) $pdo->prepare('UPDATE destinations SET image_principale=? WHERE id=?')->execute([$url,$destinationId]);
                    $pdo->commit();
                    admin_flash('Image téléversée avec crédit et licence.');
                    header('Location: index.php?module=images');
                    exit;
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    if (is_file($path)) unlink($path);
                    throw $e;
                }
            }
        }
    }
}
$destinations = db()->query('SELECT id,nom FROM destinations WHERE deleted_at IS NULL ORDER BY nom')->fetchAll();
$images = db()->query('SELECT i.id,i.destination_id,i.url,i.credit,i.licence,i.texte_alternatif,i.statut,i.est_principale,d.nom FROM images_destinations i JOIN destinations d ON d.id=i.destination_id ORDER BY i.created_at DESC LIMIT 100')->fetchAll();
$csrf = admin_csrf();
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Images — Administration</title><link rel="stylesheet" href="style.css"></head>
<body><header class="topbar"><strong>TOURISME IVOIRE · ADMIN</strong><a href="index.php?module=images">Retour aux images</a></header><main class="content editor"><h1><?= $imageId?'Modifier les crédits':'Téléverser une image' ?></h1>
<?php if ($error!==''): ?><div class="notice error"><?= admin_escape($error) ?></div><?php endif; ?>
<form class="panel" method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>"><input type="hidden" name="destination_id" value="<?= admin_escape($destinationId) ?>">
<?php if (!$imageId): ?><label>Destination</label><select name="destination_id" required><option value="">Choisir</option><?php foreach ($destinations as $row): ?><option value="<?= admin_escape($row['id']) ?>" <?= (string)$destinationId===(string)$row['id']?'selected':'' ?>><?= admin_escape($row['nom']) ?></option><?php endforeach; ?></select><label>Fichier image</label><input type="file" name="image" accept="image/jpeg,image/png,image/webp" required><?php endif; ?>
<label>Texte alternatif</label><input name="texte_alternatif" maxlength="255" value="<?= admin_escape($image['texte_alternatif'] ?? '') ?>">
<label>Crédit</label><input name="credit" maxlength="190" required value="<?= admin_escape($image['credit'] ?? '') ?>">
<label>Licence / autorisation</label><input name="licence" maxlength="190" required value="<?= admin_escape($image['licence'] ?? '') ?>">
<?php if ($imageId): ?><label>État</label><select name="statut"><option value="actif" <?= $image['statut']==='actif'?'selected':'' ?>>actif</option><option value="inactif" <?= $image['statut']==='inactif'?'selected':'' ?>>inactif</option></select><?php endif; ?>
<label><input type="checkbox" name="principale" value="1" <?= !empty($image['est_principale'])?'checked':'' ?> style="width:auto"> Image principale</label>
<p><button class="button primary" type="submit">Enregistrer</button></p></form>
<?php if (!$imageId): ?><h2>Images récentes</h2><div class="table-wrap"><table><thead><tr><th>Destination</th><th>Crédit</th><th>Licence</th><th>État</th><th></th></tr></thead><tbody><?php foreach ($images as $row): ?><tr><td><?= admin_escape($row['nom']) ?></td><td><?= admin_escape($row['credit']) ?></td><td><?= admin_escape($row['licence']) ?></td><td><?= admin_escape($row['statut']) ?></td><td><a href="image.php?id=<?= admin_escape($row['id']) ?>">Modifier</a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</main></body></html>
