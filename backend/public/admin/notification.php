<?php
declare(strict_types=1);
require_once dirname(__DIR__,2) . '/admin/bootstrap.php';
admin_require_role(['administrateur','editeur']);
$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_check_csrf();
    $title = trim((string)($_POST['titre'] ?? ''));
    $content = trim((string)($_POST['contenu'] ?? ''));
    $type = (string)($_POST['type'] ?? 'information');
    $all = ($_POST['tous'] ?? '') === '1';
    $userId = filter_var($_POST['utilisateur_id'] ?? null,FILTER_VALIDATE_INT);
    if ($title==='' || mb_strlen($title)>180 || $content==='' || !in_array($type,['information','reservation','quiz','communaute'],true) || (!$all && (!$userId || $userId<1))) {
        $error = 'Titre, contenu, type et destinataire valides requis.';
    } else {
        if ($all) {
            $stmt = db()->prepare("INSERT INTO notifications(utilisateur_id,titre,contenu,type) SELECT id,?,?,? FROM utilisateurs WHERE statut='actif' AND deleted_at IS NULL");
            $stmt->execute([$title,$content,$type]);
        } else {
            $stmt = db()->prepare('INSERT INTO notifications(utilisateur_id,titre,contenu,type) VALUES(?,?,?,?)');
            $stmt->execute([$userId,$title,$content,$type]);
        }
        admin_flash('Notification enregistrée.');
        header('Location: index.php?module=notifications');
        exit;
    }
}
$users = db()->query("SELECT id,nom,email FROM utilisateurs WHERE statut='actif' AND deleted_at IS NULL ORDER BY nom LIMIT 1000")->fetchAll();
$csrf = admin_csrf();
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Notification — Administration</title><link rel="stylesheet" href="style.css"></head>
<body><header class="topbar"><strong>TOURISME IVOIRE · ADMIN</strong><a href="index.php?module=notifications">Retour aux notifications</a></header><main class="content editor"><h1>Créer une notification</h1>
<?php if ($error!==''): ?><div class="notice error"><?= admin_escape($error) ?></div><?php endif; ?>
<form class="panel" method="post"><input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>">
<label>Titre</label><input name="titre" maxlength="180" required>
<label>Contenu</label><textarea name="contenu" rows="5" required></textarea>
<label>Type</label><select name="type"><option value="information">Information</option><option value="reservation">Réservation</option><option value="quiz">Quiz</option><option value="communaute">Communauté</option></select>
<label>Destinataire</label><select name="utilisateur_id"><option value="">Tous les utilisateurs actifs</option><?php foreach ($users as $row): ?><option value="<?= admin_escape($row['id']) ?>"><?= admin_escape($row['nom']) ?> — <?= admin_escape($row['email']) ?></option><?php endforeach; ?></select>
<label><input type="checkbox" name="tous" value="1" style="width:auto"> Envoyer à tous les comptes actifs</label>
<p><button class="button primary" type="submit">Enregistrer la notification</button></p></form></main></body></html>
