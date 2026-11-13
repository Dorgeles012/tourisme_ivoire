<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/admin/bootstrap.php';
$user = admin_require_login();
$modules = [
    'users' => ['label'=>'Utilisateurs','sql'=>"SELECT id,CONCAT(nom,' — ',email) AS label,statut FROM utilisateurs WHERE deleted_at IS NULL ORDER BY created_at DESC",'values'=>['actif','suspendu','en_attente']],
    'regions' => ['label'=>'Régions','sql'=>"SELECT id,nom AS label,statut FROM regions ORDER BY nom",'values'=>['actif','inactif']],
    'districts' => ['label'=>'Districts','sql'=>"SELECT id,nom AS label,statut FROM districts ORDER BY nom",'values'=>['actif','inactif']],
    'localities' => ['label'=>'Localités','sql'=>"SELECT l.id,CONCAT(l.nom,' — ',COALESCE(r.nom,d.nom)) AS label,l.statut FROM localites l JOIN departements dep ON dep.id=l.departement_id LEFT JOIN regions r ON r.id=dep.region_id LEFT JOIN districts d ON d.id=dep.district_id ORDER BY l.nom",'values'=>['actif','inactif']],
    'categories' => ['label'=>'Catégories','sql'=>"SELECT id,nom AS label,statut FROM categories ORDER BY ordre,nom",'values'=>['actif','inactif']],
    'destinations' => ['label'=>'Destinations','sql'=>"SELECT id,nom AS label,statut_publication AS statut FROM destinations WHERE deleted_at IS NULL ORDER BY created_at DESC",'values'=>['brouillon','publie','archive']],
    'heritage' => ['label'=>'Patrimoines','sql'=>"SELECT id,titre AS label,statut FROM patrimoines ORDER BY created_at DESC",'values'=>['brouillon','publie','archive']],
    'virtual_visits' => ['label'=>'Visites virtuelles','sql'=>"SELECT v.id,CONCAT(v.titre,' — ',COALESCE(p.titre,d.nom)) AS label,v.statut FROM visites_virtuelles v LEFT JOIN patrimoines p ON p.id=v.patrimoine_id LEFT JOIN destinations d ON d.id=v.destination_id ORDER BY v.titre",'values'=>['actif','inactif']],
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
if ($config !== null && !admin_can_module($module,false,$user)) {
    http_response_code(403);
    exit('Vous n’avez pas accès à ce module.');
}
$modules = array_filter($modules,static fn(array $item,string $key): bool => admin_can_module($key,false),ARRAY_FILTER_USE_BOTH);
$canManageModule = $config !== null && admin_can_module($module,true,$user);
$canManageRoles = admin_can_manage_roles($user);
$canManageGeography = admin_can_module('geography',true,$user);
$rows = [];
if ($config !== null) {
    $rows = db()->query($config['sql'] . ' LIMIT 200')->fetchAll();
}
$counts = [
    'Utilisateurs'=>db()->query("SELECT COUNT(*) FROM utilisateurs WHERE deleted_at IS NULL")->fetchColumn(),
    'Destinations publiées'=>db()->query("SELECT COUNT(*) FROM destinations WHERE statut_publication='publie' AND deleted_at IS NULL")->fetchColumn(),
    'Patrimoines publiés'=>db()->query("SELECT COUNT(*) FROM patrimoines WHERE statut='publie'")->fetchColumn(),
    'Vidéos à modérer'=>db()->query("SELECT COUNT(*) FROM videos WHERE statut='en_attente' AND deleted_at IS NULL")->fetchColumn(),
    'Réservations à traiter'=>db()->query("SELECT COUNT(*) FROM reservations WHERE statut='demande'")->fetchColumn(),
];
$countModules = ['Utilisateurs'=>'users','Destinations publiées'=>'destinations','Patrimoines publiés'=>'heritage','Vidéos à modérer'=>'videos','Réservations à traiter'=>'reservations'];
$counts = array_filter($counts,static fn(string $label): bool => admin_can_module($countModules[$label],false,$user),ARRAY_FILTER_USE_KEY);
$message = admin_flash();
$csrf = admin_csrf();

// Petite fonction utilitaire pour les badges de statut
function status_badge(string $status): string {
    $map = [
        'actif' => 'success', 'publie' => 'success', 'confirmee' => 'success', 'terminee' => 'success', 'lue' => 'secondary',
        'inactif' => 'secondary', 'brouillon' => 'warning', 'en_attente' => 'warning', 'demande' => 'warning', 'non_lue' => 'warning',
        'suspendu' => 'danger', 'archive' => 'dark', 'annulee' => 'danger', 'rejete' => 'danger',
    ];
    $class = $map[$status] ?? 'secondary';
    return '<span class="badge bg-'.$class.'-subtle text-'.$class.'-emphasis border border-'.$class.'-subtle rounded-pill px-3 py-1">'.admin_escape($status).'</span>';
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Administration — Tourisme Ivoire</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #e67e22;
      --primary-dark: #d35400;
      --bg-body: #f4f6f9;
      --border-color: #e9ecef;
      --sidebar-width: 260px;   /* ⚠️ À ajuster selon la largeur réelle de votre _sidebar.php */
      --topbar-height: 64px;    /* ⚠️ À ajuster selon la hauteur réelle de votre topbar */
    }
    * { font-family: 'Inter', sans-serif; box-sizing: border-box; }
    body { background: var(--bg-body); min-height: 100vh; margin: 0; }

    /* ============================================
       LAYOUT FIXE :
       - Sidebar fixée à gauche (gérée par _sidebar.php)
       - Topbar fixée en haut
       - Contenu scrollable avec les bons offsets
       ============================================ */

    /* La sidebar est déjà positionnée par votre _sidebar.php.
       On s'assure qu'elle reste bien fixée à gauche sur grand écran. */
    .sidebar {
      position: fixed !important;
      top: 0 !important;
      left: 0 !important;
      bottom: 0 !important;
      height: 100vh !important;
      overflow-y: auto;
      z-index: 1000;
    }

    /* Topbar fixe en haut, décalée à droite de la sidebar */
    .topbar {
      position: fixed;
      top: 0;
      left: var(--sidebar-width);
      right: 0;
      height: var(--topbar-height);
      background: #fff;
      border-bottom: 1px solid var(--border-color);
      padding: 0 1.75rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      z-index: 900;
      box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }
    .topbar strong { font-weight: 700; letter-spacing: 0.5px; color: #1a1d29; font-size: 0.95rem; }
    .topbar .user-info { display: flex; align-items: center; gap: 0.65rem; font-size: 0.875rem; color: #4a5568; }
    .topbar .user-info .avatar {
      width: 32px; height: 32px; border-radius: 50%;
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      color: #fff; display: flex; align-items: center; justify-content: center;
      font-weight: 600; font-size: 0.85rem;
    }

    /* Contenu principal : décalé à droite de la sidebar et sous la topbar */
    .content {
      margin-left: var(--sidebar-width);
      padding: calc(var(--topbar-height) + 2rem) 2.5rem 2rem;
      min-height: 100vh;
      max-width: 100%;
      overflow-x: hidden;
    }
    .content h1 { font-size: 1.75rem; font-weight: 700; color: #1a1d29; margin-bottom: 0.25rem; }
    .eyebrow {
      font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1.5px;
      color: var(--primary); font-weight: 600; margin-bottom: 0.25rem;
    }
    .muted { color: #8892a6; font-size: 0.9rem; }

    /* Stats cards */
    .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem; margin: 1.5rem 0; }
    .stat {
      background: #fff; border-radius: 14px; padding: 1.25rem 1.5rem;
      border: 1px solid var(--border-color);
      box-shadow: 0 2px 8px rgba(0,0,0,0.03);
      transition: transform 0.2s, box-shadow 0.2s;
      display: flex; flex-direction: column; gap: 0.25rem;
    }
    .stat:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.06); }
    .stat span { font-size: 0.8rem; color: #8892a6; font-weight: 500; }
    .stat b { font-size: 1.75rem; font-weight: 700; color: #1a1d29; }

    /* Panels */
    .panel {
      background: #fff; border-radius: 14px; padding: 1.5rem 1.75rem;
      border: 1px solid var(--border-color);
      box-shadow: 0 2px 8px rgba(0,0,0,0.03);
      margin-bottom: 1.25rem;
    }
    .panel h2 { font-size: 1.05rem; font-weight: 600; color: #1a1d29; margin-bottom: 1rem; }

    /* Buttons */
    .button {
      display: inline-block; text-decoration: none; border-radius: 8px;
      padding: 0.55rem 1.1rem; font-size: 0.85rem; font-weight: 500;
      border: 1px solid var(--border-color); background: #fff; color: #4a5568;
      transition: all 0.2s; cursor: pointer;
    }
    .button:hover { background: #f8f9fb; border-color: #d0d5dd; color: #1a1d29; }
    .button.primary { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: #fff; border: none; box-shadow: 0 4px 12px rgba(230,126,34,0.25); }
    .button.primary:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(230,126,34,0.35); }
    .button.small { padding: 0.35rem 0.75rem; font-size: 0.78rem; }

    /* Table */
    .table-wrap { background: #fff; border-radius: 14px; border: 1px solid var(--border-color); overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.03); margin-top: 1rem; }
    table { width: 100%; border-collapse: collapse; }
    thead { background: #f8f9fb; }
    th { text-align: left; padding: 0.9rem 1.25rem; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: #8892a6; font-weight: 600; border-bottom: 1px solid var(--border-color); }
    td { padding: 1rem 1.25rem; font-size: 0.875rem; color: #4a5568; border-bottom: 1px solid #f0f2f5; vertical-align: middle; }
    tbody tr:hover { background: #fafbfc; }
    tbody tr:last-child td { border-bottom: none; }
    td a { color: var(--primary); text-decoration: none; font-weight: 500; font-size: 0.8rem; margin-left: 0.5rem; }
    td a:hover { text-decoration: underline; }

    /* Inline forms */
    .inline-form { display: inline-flex; align-items: center; gap: 0.4rem; margin: 0.15rem 0; }
    .inline-form select { border: 1px solid var(--border-color); border-radius: 6px; padding: 0.3rem 0.5rem; font-size: 0.78rem; background: #fff; color: #4a5568; }

    /* Notice */
    .notice { background: #e8f8f0; color: #1e8449; border-left: 4px solid #27ae60; padding: 0.9rem 1.25rem; border-radius: 8px; font-size: 0.875rem; margin-bottom: 1.25rem; font-weight: 500; }

    /* ============================================
       RESPONSIVE : la sidebar passe en haut
       On annule les décalages fixes
       ============================================ */
    @media (max-width: 992px) {
      .sidebar {
        position: static !important;
        height: auto !important;
        width: 100% !important;
      }
      .topbar {
        position: static;
        left: 0;
        height: auto;
        padding: 0.85rem 1rem;
      }
      .content {
        margin-left: 0;
        padding: 1.5rem 1rem;
      }
    }
  </style>
</head>
<body class="admin-page">
<?php require __DIR__ . '/_sidebar.php'; ?>

<header class="topbar">
  <strong>TOURISME IVOIRE · ADMIN</strong>
  <div class="user-info">
    <span class="avatar"><?= admin_escape(mb_strtoupper(mb_substr($user['nom'], 0, 1))) ?></span>
    <span><?= admin_escape($user['nom']) ?></span>
  </div>
</header>

<main class="content">
  <?php if ($message): ?><div class="notice"><?= admin_escape($message) ?></div><?php endif; ?>
  
  <?php if ($config === null): ?>
    <p class="eyebrow">ESPACE DE GESTION</p>
    <h1>Tableau de bord</h1>
    <p class="muted">Les contenus de démonstration restent non publiés tant qu’ils ne sont pas vérifiés par un éditeur.</p>
    
    <section class="stats">
      <?php foreach ($counts as $label=>$value): ?>
        <div class="stat">
          <span><?= admin_escape($label) ?></span>
          <b><?= admin_escape($value) ?></b>
        </div>
      <?php endforeach; ?>
    </section>

    <div class="panel">
      <h2>Raccourcis</h2>
      <p class="mb-0">
        <?php if (admin_can_module('destinations',true,$user)): ?><a class="button primary" href="destination.php">Créer une destination</a><?php endif; ?>
        <?php if (admin_can_module('heritage',true,$user)): ?><a class="button" href="heritage.php">Ajouter un patrimoine</a><?php endif; ?>
        <?php if (admin_can_module('virtual_visits',true,$user)): ?><a class="button" href="virtual_visit.php">Ajouter une visite virtuelle</a><?php endif; ?>
        <?php if (admin_can_module('images',true,$user)): ?><a class="button" href="image.php">Téléverser une image</a><?php endif; ?>
        <?php if (admin_can_module('notifications',true,$user)): ?><a class="button" href="notification.php">Nouvelle notification</a><?php endif; ?>
      </p>
    </div>

  <?php else: ?>
    <p class="eyebrow">GESTION DE CONTENU</p>
    <h1><?= admin_escape($config['label']) ?></h1>
    
    <div class="mb-3">
      <?php if ($module==='users' && $canManageRoles): ?><a class="button primary" href="create_user.php">Créer un utilisateur</a><?php endif; ?>
      <?php if ($module==='destinations' && $canManageModule): ?><a class="button primary" href="destination.php">Ajouter une destination</a><?php endif; ?>
      <?php if ($module==='heritage' && $canManageModule): ?><a class="button primary" href="heritage.php">Ajouter un patrimoine</a><?php endif; ?>
      <?php if ($module==='virtual_visits' && $canManageModule): ?><a class="button primary" href="virtual_visit.php">Ajouter une visite virtuelle</a><?php endif; ?>
      <?php if ($module==='categories' && $canManageModule): ?><a class="button primary" href="category.php">Ajouter une catégorie</a><?php endif; ?>
      <?php if ($module==='images' && $canManageModule): ?><a class="button primary" href="image.php">Téléverser une image</a><?php endif; ?>
      <?php if ($module==='quizzes' && $canManageModule): ?><a class="button primary" href="quiz.php">Créer un quiz</a><?php endif; ?>
      <?php if ($module==='notifications' && $canManageModule): ?><a class="button primary" href="notification.php">Créer une notification</a><?php endif; ?>
    </div>

    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>ID</th><th>Élément</th><th>État</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td><span class="text-muted">#<?= admin_escape($row['id']) ?></span></td>
            <td>
              <strong><?= admin_escape($row['label']) ?></strong>
              <?php if ($canManageModule && $module==='destinations'): ?> <a href="destination.php?id=<?= admin_escape($row['id']) ?>">Modifier</a>
              <?php elseif ($canManageModule && $module==='heritage'): ?> <a href="heritage.php?id=<?= admin_escape($row['id']) ?>">Modifier</a>
              <?php elseif ($canManageModule && $module==='virtual_visits'): ?> <a href="virtual_visit.php?id=<?= admin_escape($row['id']) ?>">Modifier</a>
              <?php elseif ($canManageModule && $module==='categories'): ?> <a href="category.php?id=<?= admin_escape($row['id']) ?>">Modifier</a>
              <?php elseif ($canManageModule && $module==='quizzes'): ?> <a href="quiz.php?id=<?= admin_escape($row['id']) ?>">Modifier</a><?php endif; ?>
              <?php if ($canManageRoles && $module==='users'): ?> <a href="user_roles.php?id=<?= admin_escape($row['id']) ?>">Gérer les rôles</a><?php endif; ?>
            </td>
            <td><?= status_badge((string)$row['statut']) ?></td>
            <td>
              <?php if ($canManageModule && $module==='images'): ?><a href="image.php?id=<?= admin_escape($row['id']) ?>">Modifier les crédits</a><?php endif; ?>
              <?php if ($canManageModule && $module==='notifications' && $row['statut']==='non_lue'): ?>
                <form class="inline-form" method="post" action="actions.php">
                  <input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>">
                  <input type="hidden" name="module" value="notifications">
                  <input type="hidden" name="id" value="<?= admin_escape($row['id']) ?>">
                  <input type="hidden" name="action" value="read_notification">
                  <button class="button small" type="submit">Marquer comme lue</button>
                </form>
              <?php endif; ?>
              <?php if ($canManageModule && $config['values']): ?>
                <form class="inline-form" method="post" action="actions.php">
                  <input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>">
                  <input type="hidden" name="module" value="<?= admin_escape($module) ?>">
                  <input type="hidden" name="id" value="<?= admin_escape($row['id']) ?>">
                  <select name="action" aria-label="Nouvel état">
                    <?php foreach ($config['values'] as $value): ?>
                      <option value="<?= admin_escape($value) ?>" <?= $row['statut']===$value?'selected':'' ?>><?= admin_escape($value) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button class="button small" type="submit">Mettre à jour</button>
                </form>
              <?php endif; ?>
              <?php if ($canManageModule && $module==='destinations'): ?>
                <form class="inline-form" method="post" action="actions.php" onsubmit="return confirm('Archiver cette destination ?')">
                  <input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>">
                  <input type="hidden" name="module" value="destinations">
                  <input type="hidden" name="id" value="<?= admin_escape($row['id']) ?>">
                  <input type="hidden" name="action" value="delete_destination">
                  <button class="button small" type="submit">Archiver</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="4" class="text-center text-muted py-4">Aucun élément.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</main>
</body>
</html>