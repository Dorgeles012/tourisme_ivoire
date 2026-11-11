<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/admin/bootstrap.php';
$user = admin_require_role(['administrateur','editeur']);
$adminRole = db()->prepare("SELECT 1 FROM utilisateurs_roles ur JOIN roles r ON r.id=ur.role_id WHERE ur.utilisateur_id=? AND r.code='administrateur' LIMIT 1");
$adminRole->execute([$user['id']]);
$isAdministrator = (bool)$adminRole->fetchColumn();
$modules = [
    'users' => ['label'=>'Utilisateurs','sql'=>"SELECT id,CONCAT(nom,' — ',email) AS label,statut FROM utilisateurs WHERE deleted_at IS NULL ORDER BY created_at DESC",'values'=>['actif','suspendu','en_attente']],
    'regions' => ['label'=>'Régions','sql'=>"SELECT id,nom AS label,statut FROM regions ORDER BY nom",'values'=>['actif','inactif']],
    'districts' => ['label'=>'Districts','sql'=>"SELECT id,nom AS label,statut FROM districts ORDER BY nom",'values'=>['actif','inactif']],
    'localities' => ['label'=>'Localités','sql'=>"SELECT l.id,CONCAT(l.nom,' — ',COALESCE(r.nom,d.nom)) AS label,l.statut FROM localites l JOIN departements dep ON dep.id=l.departement_id LEFT JOIN regions r ON r.id=dep.region_id LEFT JOIN districts d ON d.id=dep.district_id ORDER BY l.nom",'values'=>['actif','inactif']],
    'categories' => ['label'=>'Catégories','sql'=>"SELECT id,nom AS label,statut FROM categories ORDER BY ordre,nom",'values'=>['actif','inactif']],
    'destinations' => ['label'=>'Destinations','sql'=>"SELECT id,nom AS label,statut_publication AS statut FROM destinations WHERE deleted_at IS NULL ORDER BY created_at DESC",'values'=>['brouillon','publie','archive']],
    'images' => ['label'=>'Images','sql'=>"SELECT i.id,CONCAT(d.nom,' — ',COALESCE(i.credit,'crédit manquant')) AS label,i.statut FROM images_destinations i JOIN destinations d ON d.id=i.destination_id ORDER BY i.created_at DESC",'values'=>['actif','inactif']],
    'videos' => ['label'=>'Vidéos','sql'=>"SELECT v.id,CONCAT(v.titre,' — ',u.nom) AS label,v.statut FROM videos v JOIN utilisateurs u ON u.id=v.utilisateur_id WHERE v.deleted_at IS NULL ORDER BY v.created_at DESC",'values'=>['en_attente','publie','rejete','archive']],
    'quizzes' => ['label'=>'Quiz','sql'=>"SELECT id,titre AS label,statut FROM quiz ORDER BY created_at DESC",'values'=>['brouillon','publie','archive']],
    'badges' => ['label'=>'Badges','sql'=>"SELECT id,nom AS label,statut FROM badges ORDER BY nom",'values'=>['actif','inactif']],
    'reservations' => ['label'=>'Réservations','sql'=>"SELECT id,reference AS label,statut FROM reservations ORDER BY created_at DESC",'values'=>['demande','confirmee','annulee','terminee']],
    'reviews' => ['label'=>'Avis','sql'=>"SELECT a.id,CONCAT(d.nom,' — ',a.note,'/5') AS label,a.statut FROM avis a JOIN destinations d ON d.id=a.destination_id WHERE a.deleted_at IS NULL ORDER BY a.created_at DESC",'values'=>['en_attente','publie','rejete']],
    'notifications' => ['label'=>'Notifications','sql'=>"SELECT id,titre AS label,IF(lu_at IS NULL,'non_lue','lue') AS statut FROM notifications ORDER BY created_at DESC",'values'=>[]],
];
$module = (string)($_GET['module'] ?? '');
$config = $modules[$module] ?? null;
$rows = [];
if ($config !== null) {
    $rows = db()->query($config['sql'] . ' LIMIT 200')->fetchAll();
}
$counts = [
    'Utilisateurs'=>db()->query("SELECT COUNT(*) FROM utilisateurs WHERE deleted_at IS NULL")->fetchColumn(),
    'Destinations publiées'=>db()->query("SELECT COUNT(*) FROM destinations WHERE statut_publication='publie' AND deleted_at IS NULL")->fetchColumn(),
    'Vidéos à modérer'=>db()->query("SELECT COUNT(*) FROM videos WHERE statut='en_attente' AND deleted_at IS NULL")->fetchColumn(),
    'Réservations à traiter'=>db()->query("SELECT COUNT(*) FROM reservations WHERE statut='demande'")->fetchColumn(),
];
$message = admin_flash();
$csrf = admin_csrf();
?>
<!doctype html>
<html lang="fr">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Administration — Tourisme Ivoire</title><link rel="stylesheet" href="style.css"></head>
<body>
<header class="topbar"><strong>TOURISME IVOIRE · ADMIN</strong><span><?= admin_escape($user['nom']) ?> <form method="post" action="logout.php" style="display:inline"><input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>"><button type="submit">Déconnexion</button></form></span></header>
<div class="layout">
  <nav class="sidebar">
    <a class="<?= $module===''?'active':'' ?>" href="index.php">Tableau de bord</a>
    <?php foreach ($modules as $key=>$item): ?><a class="<?= $module===$key?'active':'' ?>" href="index.php?module=<?= admin_escape($key) ?>"><?= admin_escape($item['label']) ?></a><?php endforeach; ?>
    <a href="destination.php">Ajouter une destination</a>
    <a href="category.php">Ajouter une catégorie</a>
    <?php if ($isAdministrator): ?><a href="geography.php">Gérer la géographie</a><?php endif; ?>
    <a href="notification.php">Envoyer une notification</a>
  </nav>
  <main class="content">
    <?php if ($message): ?><div class="notice"><?= admin_escape($message) ?></div><?php endif; ?>
    <?php if ($config === null): ?>
      <p class="eyebrow">ESPACE DE GESTION</p><h1>Tableau de bord</h1>
      <p class="muted">Les contenus de démonstration restent non publiés tant qu’ils ne sont pas vérifiés par un éditeur.</p>
      <section class="stats"><?php foreach ($counts as $label=>$value): ?><div class="stat"><span><?= admin_escape($label) ?></span><b><?= admin_escape($value) ?></b></div><?php endforeach; ?></section>
      <div class="panel"><h2>Raccourcis</h2><p><a class="button primary" href="destination.php">Créer une destination</a> <a class="button" href="image.php">Téléverser une image</a> <a class="button" href="notification.php">Nouvelle notification</a></p></div>
      <div class="panel"><h2>À traiter</h2><p><a href="index.php?module=videos">Modérer les vidéos en attente</a></p><p><a href="index.php?module=reviews">Vérifier les avis</a></p><p><a href="index.php?module=reservations">Mettre à jour les demandes de réservation</a></p></div>
    <?php else: ?>
      <p class="eyebrow">GESTION DE CONTENU</p><h1><?= admin_escape($config['label']) ?></h1>
      <?php if ($module==='destinations'): ?><p><a class="button primary" href="destination.php">Ajouter une destination</a></p><?php endif; ?>
      <?php if ($module==='categories'): ?><p><a class="button primary" href="category.php">Ajouter une catégorie</a></p><?php endif; ?>
      <?php if ($module==='images'): ?><p><a class="button primary" href="image.php">Téléverser une image</a></p><?php endif; ?>
      <?php if ($module==='quizzes'): ?><p><a class="button primary" href="quiz.php">Créer un quiz</a></p><?php endif; ?>
      <?php if ($module==='notifications'): ?><p><a class="button primary" href="notification.php">Créer une notification</a></p><?php endif; ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>ID</th><th>Élément</th><th>État</th><th>Actions</th></tr></thead>
          <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td><?= admin_escape($row['id']) ?></td>
              <td><?= admin_escape($row['label']) ?><?php if ($module==='destinations'): ?> <a href="destination.php?id=<?= admin_escape($row['id']) ?>">Modifier</a><?php elseif ($module==='categories'): ?> <a href="category.php?id=<?= admin_escape($row['id']) ?>">Modifier</a><?php elseif ($module==='quizzes'): ?> <a href="quiz.php?id=<?= admin_escape($row['id']) ?>">Modifier</a><?php endif; ?></td>
              <td><?= admin_escape($row['statut']) ?></td>
              <td>
                <?php if ($module==='images'): ?><a href="image.php?id=<?= admin_escape($row['id']) ?>">Modifier les crédits</a><?php endif; ?>
                <?php if ($module==='notifications' && $row['statut']==='non_lue'): ?><form class="inline-form" method="post" action="actions.php"><input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>"><input type="hidden" name="module" value="notifications"><input type="hidden" name="id" value="<?= admin_escape($row['id']) ?>"><input type="hidden" name="action" value="read_notification"><button class="button small" type="submit">Marquer comme lue</button></form><?php endif; ?>
                <?php if ($config['values']): ?>
                  <form class="inline-form" method="post" action="actions.php">
                    <input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>"><input type="hidden" name="module" value="<?= admin_escape($module) ?>"><input type="hidden" name="id" value="<?= admin_escape($row['id']) ?>">
                    <select name="action" aria-label="Nouvel état"><?php foreach ($config['values'] as $value): ?><option value="<?= admin_escape($value) ?>" <?= $row['statut']===$value?'selected':'' ?>><?= admin_escape($value) ?></option><?php endforeach; ?></select>
                    <button class="button small" type="submit">Mettre à jour</button>
                  </form>
                <?php endif; ?>
                <?php if ($module==='destinations'): ?><form class="inline-form" method="post" action="actions.php" onsubmit="return confirm('Archiver cette destination ?')"><input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>"><input type="hidden" name="module" value="destinations"><input type="hidden" name="id" value="<?= admin_escape($row['id']) ?>"><input type="hidden" name="action" value="delete_destination"><button class="button small" type="submit">Archiver</button></form><?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?><tr><td colspan="4">Aucun élément.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </main>
</div>
</body>
</html>
