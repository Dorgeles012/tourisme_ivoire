<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/admin/bootstrap.php';
require_once dirname(__DIR__, 2) . '/services/ContentNotificationService.php';
admin_require_module('destinations', true);
$modules = [
    'actualites' => ['label'=>'Actualités', 'table'=>'actualites', 'name'=>'titre', 'fields'=>['titre'=>'Titre','slug'=>'Slug','resume'=>'Résumé','contenu'=>'Contenu','categorie'=>'Catégorie','image_url'=>'URL image','published_at'=>'Publication'], 'text'=>['resume','contenu']],
    'promotions' => ['label'=>'Promotions', 'table'=>'promotions', 'name'=>'titre', 'fields'=>['titre'=>'Titre','slug'=>'Slug','description'=>'Description','reduction'=>'Réduction','date_debut'=>'Début','date_fin'=>'Fin','image_url'=>'URL image'], 'text'=>['description']],
    'hebergements' => ['label'=>'Hébergements', 'table'=>'hebergements', 'name'=>'nom', 'fields'=>['nom'=>'Nom','slug'=>'Slug','type'=>'Type','description'=>'Description','localite'=>'Localité','adresse'=>'Adresse','telephone'=>'Téléphone','site_web'=>'Site web','prix_min'=>'Prix minimum / nuit','devise'=>'Devise','image_url'=>'URL image'], 'text'=>['description']],
    'activites' => ['label'=>'Activités', 'table'=>'activites_touristiques', 'name'=>'nom', 'fields'=>['nom'=>'Nom','slug'=>'Slug','type'=>'Type','description'=>'Description','localite'=>'Localité','adresse'=>'Adresse','duree'=>'Durée','prix'=>'Prix','devise'=>'Devise','image_url'=>'URL image'], 'text'=>['description']],
];
$type=(string)($_GET['type']??$_POST['type']??'actualites'); if(!isset($modules[$type])) $type='actualites';
$config=$modules[$type]; $pdo=db();
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    admin_check_csrf(); $id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT); $action=(string)($_POST['action']??'save');
    if($action==='delete' && $id){$pdo->prepare('UPDATE '.$config['table'].' SET deleted_at=NOW() WHERE id=?')->execute([$id]);admin_flash('Contenu retiré.');header('Location: tourism_content.php?type='.$type);exit;}
    $wasPublished=false;
    if($id){$current=$pdo->prepare('SELECT statut FROM '.$config['table'].' WHERE id=? AND deleted_at IS NULL');$current->execute([$id]);$currentStatus=$current->fetchColumn();if($currentStatus===false)throw new InvalidArgumentException('Contenu introuvable.');$wasPublished=$currentStatus==='publie';}
    $values=[];
    foreach($config['fields'] as $key=>$label){$value=trim((string)($_POST[$key]??''));if($key==='slug'&&$value===''){$source=(string)($_POST[$config['name']]??'contenu');$source=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$source)?:$source;$value=strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/','-',$source)??'','-'));}if($key==='slug'&&!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/',$value))throw new InvalidArgumentException('Slug invalide. Utilisez des lettres minuscules, chiffres et tirets.');if($value==='')$value=null;if(in_array($key,['published_at','date_debut','date_fin'],true)&&$value!==null)$value=str_replace('T',' ',$value).(strlen($value)===16?':00':'');$values[$key]=$value;}
    $status=(string)($_POST['statut']??'brouillon');if(!in_array($status,['brouillon','publie','archive'],true))$status='brouillon';$values['statut']=$status;
    if($id){$set=implode(',',array_map(static fn($key)=>'`'.$key.'`=?',array_keys($values)));$pdo->prepare('UPDATE '.$config['table'].' SET '.$set.' WHERE id=? AND deleted_at IS NULL')->execute([...array_values($values),$id]);}
    else{$columns=array_keys($values);$sql='INSERT INTO '.$config['table'].' (`'.implode('`,`',$columns).'`) VALUES ('.implode(',',array_fill(0,count($columns),'?')).')';$pdo->prepare($sql)->execute(array_values($values));}
    $availableNow=true;
    if($type==='actualites'&&!empty($values['published_at'])){$publishTime=strtotime((string)$values['published_at']);$availableNow=$publishTime!==false&&$publishTime<=time();}
    if($type==='promotions'){$startTime=strtotime((string)$values['date_debut']);$endTime=strtotime((string)$values['date_fin']);$availableNow=$startTime!==false&&$endTime!==false&&$startTime<=time()&&$endTime>=time();}
    if($status==='publie'&&!$wasPublished&&$availableNow){$title=(string)($values[$config['name']]??'Nouveau contenu');$summary=(string)($values['resume']??$values['description']??$values['contenu']??'Découvrez ce nouveau contenu sur Tourisme Ivoire.');ContentNotificationService::broadcast($title,$summary,$type,(string)$values['slug']);}
    admin_flash($status==='publie'?'Contenu publié pour le Web Client et Flutter.':'Contenu enregistré.');header('Location: tourism_content.php?type='.$type);exit;
}
$id=filter_var($_GET['id']??null,FILTER_VALIDATE_INT);$editing=null;if($id){$q=$pdo->prepare('SELECT * FROM '.$config['table'].' WHERE id=? AND deleted_at IS NULL');$q->execute([$id]);$editing=$q->fetch()?:null;}
$rows=$pdo->query('SELECT id,'.$config['name'].' AS titre,slug,statut,updated_at FROM '.$config['table'].' WHERE deleted_at IS NULL ORDER BY updated_at DESC LIMIT 200')->fetchAll();$message=admin_flash();$csrf=admin_csrf();
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Contenus touristiques — Administration</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #e67e22;
      --primary-dark: #d35400;
      --primary-soft: rgba(230,126,34,0.10);
      --ink: #1a1d29;
      --muted: #8892a6;
      --bg-body: #f4f6f9;
      --border-color: #e9ecef;
      --sidebar-width: 260px;
      --topbar-height: 64px;
      --radius: 14px;
    }
    * { font-family: 'Inter', sans-serif; box-sizing: border-box; }
    html, body { overflow-x: hidden; max-width: 100vw; }
    body { background: var(--bg-body); min-height: 100vh; margin: 0; }

    /* ---------- Sidebar / Topbar ---------- */
    .sidebar {
      position: fixed !important;
      top: 0 !important; left: 0 !important; bottom: 0 !important;
      height: 100vh !important; overflow-y: auto; z-index: 1000;
    }
    .topbar {
      position: fixed; top: 0; left: var(--sidebar-width); right: 0;
      height: var(--topbar-height);
      background: rgba(255,255,255,0.92);
      backdrop-filter: blur(12px);
      border-bottom: 1px solid var(--border-color);
      padding: 0 1.5rem;
      display: flex; justify-content: space-between; align-items: center;
      z-index: 900;
    }
    .topbar strong {
      font-weight: 700; letter-spacing: 0.5px; color: var(--ink);
      font-size: 0.9rem; display: flex; align-items: center; gap: 0.5rem;
    }
    .topbar a {
      color: #4a5568; text-decoration: none; font-size: 0.82rem; font-weight: 500;
      padding: 0.45rem 0.95rem; border-radius: 999px; border: 1px solid var(--border-color);
      background: #fff; transition: all 0.2s;
    }
    .topbar a:hover { background: #f8f9fb; border-color: #d0d5dd; color: var(--ink); }

    /* ---------- Layout ---------- */
    .content {
      margin-left: var(--sidebar-width);
      padding: calc(var(--topbar-height) + 1.75rem) 2rem 2rem;
      min-height: 100vh;
    }
    .content-inner { max-width: 940px; margin: 0 auto; }

    .content h1 {
      font-size: 1.5rem; font-weight: 700; color: var(--ink);
      margin-bottom: 0.2rem; letter-spacing: -0.02em;
    }
    .muted { color: var(--muted); font-size: 0.85rem; margin-bottom: 1.25rem; }

    /* ---------- Panels ---------- */
    .panel {
      background: #fff; border-radius: var(--radius); padding: 1.5rem 1.6rem;
      border: 1px solid var(--border-color);
      box-shadow: 0 1px 2px rgba(16,24,40,0.04), 0 8px 24px -12px rgba(16,24,40,0.08);
    }
    .panel-title {
      font-size: 0.95rem; font-weight: 700; color: var(--ink);
      margin: 0 0 1.1rem; padding-bottom: 0.7rem;
      border-bottom: 1px solid #f0f2f5;
      display: flex; align-items: center; justify-content: space-between;
    }

    /* ---------- Onglets modules ---------- */
    .module-tabs {
      display: flex; gap: 0.35rem; margin-bottom: 1.1rem;
      overflow-x: auto; padding: 0.15rem 0.1rem 0.5rem;
      scrollbar-width: thin;
    }
    .module-tabs::-webkit-scrollbar { height: 3px; }
    .module-tabs::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.1); border-radius: 3px; }
    .module-tab {
      display: inline-flex; align-items: center; flex: 0 0 auto;
      padding: 0.45rem 0.95rem; font-size: 0.78rem; font-weight: 600;
      color: #4a5568; background: #fff;
      border: 1px solid var(--border-color); border-radius: 999px;
      text-decoration: none; white-space: nowrap; transition: all 0.2s ease;
    }
    .module-tab:hover { background: #f8f9fb; border-color: #d0d5dd; color: var(--ink); }
    .module-tab.is-active {
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      border-color: transparent; color: #fff;
      box-shadow: 0 4px 12px rgba(230,126,34,0.28);
    }

    /* ---------- Notice ---------- */
    .notice.success {
      background: #eafaf1; color: #1e8449;
      border-left: 4px solid #2ecc71;
      padding: 0.85rem 1.1rem; border-radius: 10px;
      font-size: 0.82rem; font-weight: 500;
      margin-bottom: 1.25rem;
    }

    /* ---------- Grille principale ---------- */
    .admin-grid {
      display: grid;
      grid-template-columns: minmax(320px, 420px) 1fr;
      gap: 1.1rem;
      align-items: start;
    }
    @media (max-width: 992px) {
      .admin-grid { grid-template-columns: 1fr; }
    }

    /* ---------- Formulaires ---------- */
    .panel label {
      display: block; font-size: 0.76rem; font-weight: 600;
      color: #34495e; margin-bottom: 0.3rem; letter-spacing: 0.01em;
    }
    .panel input, .panel select, .panel textarea {
      width: 100%; border: 1px solid var(--border-color); border-radius: 10px;
      padding: 0.6rem 0.85rem; font-size: 0.85rem; color: var(--ink);
      background: #fafbfc; transition: all 0.2s; font-family: inherit;
    }
    .panel input:focus, .panel select:focus, .panel textarea:focus {
      outline: none; border-color: var(--primary);
      box-shadow: 0 0 0 3px var(--primary-soft); background: #fff;
    }
    .panel textarea { resize: vertical; line-height: 1.55; min-height: 80px; }
    .hint { font-size: 0.72rem; color: var(--muted); margin-top: 0.28rem; }

    /* ---------- Étapes formulaire ---------- */
    .form-steps {
      display: flex; gap: 0.25rem; margin-bottom: 1.1rem;
      border-bottom: 1px solid var(--border-color);
      overflow-x: auto; scrollbar-width: none;
    }
    .form-steps::-webkit-scrollbar { display: none; }
    .form-step {
      flex: 0 0 auto; padding: 0.55rem 0.85rem;
      font-size: 0.74rem; font-weight: 600; color: var(--muted);
      background: transparent; border: none;
      border-bottom: 2px solid transparent;
      cursor: pointer; transition: all 0.15s ease;
      font-family: inherit; white-space: nowrap;
    }
    .form-step:hover { color: var(--ink); }
    .form-step.is-active { color: var(--primary); border-bottom-color: var(--primary); }

    /* ---------- Panneaux + animation SWIPE ---------- */
    .form-step-pane { display: none; }
    .form-step-pane.is-active { display: block; }

    /* Animation glissement horizontal */
    .swipe-in-forward  { animation: slideInRight 0.55s cubic-bezier(.22,.61,.36,1) both; }
    .swipe-in-backward { animation: slideInLeft  0.55s cubic-bezier(.22,.61,.36,1) both; }
    .swipe-out-forward  { animation: slideOutLeft  0.35s ease both; }
    .swipe-out-backward { animation: slideOutRight 0.35s ease both; }

    @keyframes slideInRight  { from { opacity:0; transform: translateX(60px); }  to { opacity:1; transform: translateX(0); } }
    @keyframes slideInLeft   { from { opacity:0; transform: translateX(-60px); } to { opacity:1; transform: translateX(0); } }
    @keyframes slideOutLeft  { from { opacity:1; transform: translateX(0); }  to { opacity:0; transform: translateX(-60px); } }
    @keyframes slideOutRight { from { opacity:1; transform: translateX(0); }  to { opacity:0; transform: translateX(60px); } }

    /* ---------- Grille form ---------- */
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.8rem 0.9rem; }
    .form-grid .wide { grid-column: 1 / -1; }
    @media (max-width: 600px) { .form-grid { grid-template-columns: 1fr; } }
    .form-grid .field { display: flex; flex-direction: column; }

    /* ---------- Info box ---------- */
    .info-box {
      background: #fffaf5; border: 1px solid #f5cba7; border-left: 4px solid var(--primary);
      color: #7e5109; border-radius: 10px; padding: 0.8rem 1rem;
      font-size: 0.8rem; line-height: 1.5; margin: 0.9rem 0 0;
    }

    /* ---------- Boutons ---------- */
    .button {
      display: inline-block; text-decoration: none; border-radius: 10px;
      padding: 0.65rem 1.35rem; font-size: 0.85rem; font-weight: 600;
      border: 1px solid var(--border-color); background: #fff; color: #4a5568;
      transition: all 0.2s; cursor: pointer; font-family: inherit; text-align: center;
    }
    .button:hover { background: #f8f9fb; border-color: #d0d5dd; color: var(--ink); }
    .button.primary {
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      color: #fff; border: none; box-shadow: 0 4px 12px rgba(230,126,34,0.28);
    }
    .button.primary:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(230,126,34,0.38); }
    .button.full { width: 100%; }
    .button.sm { padding: 0.35rem 0.7rem; font-size: 0.74rem; border-radius: 8px; }
    .button.danger { color: #c0392b; border-color: rgba(192,57,43,0.3); background: transparent; }
    .button.danger:hover { background: rgba(192,57,43,0.06); border-color: rgba(192,57,43,0.5); color: #c0392b; }
    .form-actions {
      display: flex; gap: 0.55rem; flex-wrap: wrap;
      margin-top: 1.1rem; padding-top: 0.9rem;
      border-top: 1px solid #f0f2f5;
    }
    .form-actions .button { flex: 1; min-width: 130px; }

    /* ---------- Tableau ---------- */
    .table-wrap { overflow-x: auto; border-radius: 10px; border: 1px solid var(--border-color); }
    .data-table { width: 100%; font-size: 0.78rem; border-collapse: collapse; min-width: 420px; }
    .data-table th {
      font-size: 0.64rem; font-weight: 700; color: var(--muted);
      text-transform: uppercase; letter-spacing: 0.06em;
      padding: 0.65rem 0.8rem; background: #fafbfc;
      border-bottom: 1px solid var(--border-color);
      text-align: left; white-space: nowrap;
    }
    .data-table td {
      padding: 0.6rem 0.8rem;
      border-bottom: 1px solid #f0f2f5;
      color: var(--ink); vertical-align: middle;
    }
    .data-table tr:last-child td { border-bottom: none; }
    .data-table tr:hover td { background: #fafbfc; }
    .data-table .row-title {
      font-weight: 600; color: var(--ink); margin: 0;
      overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
      max-width: 200px;
    }
    .data-table .row-slug {
      font-size: 0.7rem; color: var(--muted);
      display: block; margin-top: 0.1rem;
      font-family: 'JetBrains Mono', ui-monospace, monospace;
    }
    .data-table .row-actions { display: flex; gap: 0.25rem; justify-content: flex-end; }

    .status-badge {
      display: inline-flex; align-items: center;
      padding: 0.18rem 0.5rem;
      font-size: 0.64rem; font-weight: 700;
      text-transform: uppercase; letter-spacing: 0.04em;
      border-radius: 6px;
    }
    .status-badge.is-publie    { background: rgba(46, 125, 50, 0.12);   color: #1e8449; }
    .status-badge.is-brouillon { background: rgba(212, 160, 23, 0.12);  color: #b8860b; }
    .status-badge.is-archive   { background: rgba(138, 114, 102, 0.15); color: #6c757d; }

    .empty-state { text-align: center; padding: 1.75rem 1rem; color: var(--muted); }
    .empty-state p { font-size: 0.82rem; margin: 0; }

    /* ---------- Responsive ---------- */
    @media (max-width: 992px) {
      .sidebar { position: static !important; height: auto !important; width: 100% !important; }
      .topbar { position: static; left: 0; height: auto; padding: 0.8rem 1rem; }
      .content { margin-left: 0; padding: 1.25rem 1rem; }
      .panel { padding: 1.15rem; }
    }
    @media (prefers-reduced-motion: reduce) {
      *, .button, .module-tab, .form-step { transition: none !important; }
      .swipe-in-forward, .swipe-in-backward,
      .swipe-out-forward, .swipe-out-backward { animation: none !important; }
    }
  </style>
</head>
<body class="admin-page">
<?php require __DIR__ . '/_sidebar.php'; ?>

<header class="topbar">
  <strong>TOURISME IVOIRE · ADMIN</strong>
  <a href="index.php?module=destinations">Retour au tableau de bord</a>
</header>

<main class="content">
  <div class="content-inner">
    <h1>Contenus touristiques</h1>
    <p class="muted">Publiez ici les contenus visibles dans l’espace client Web et l’application Flutter.</p>

    <?php if ($message): ?>
      <div class="notice success"><?= admin_escape($message) ?></div>
    <?php endif; ?>

    <nav class="module-tabs" aria-label="Types de contenu">
      <?php foreach ($modules as $key => $item): ?>
        <a class="module-tab <?= $key === $type ? 'is-active' : '' ?>"
           href="?type=<?= admin_escape($key) ?>"
           <?= $key === $type ? 'aria-current="page"' : '' ?>>
          <?= admin_escape($item['label']) ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <div class="admin-grid">

      <section class="panel">
        <h2 class="panel-title">
          <?= $editing ? 'Modifier' : 'Ajouter' ?> — <?= admin_escape($config['label']) ?>
        </h2>

        <form method="post" id="contentForm">
          <input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>">
          <input type="hidden" name="type" value="<?= admin_escape($type) ?>">
          <input type="hidden" name="id" value="<?= admin_escape($editing['id'] ?? '') ?>">

          <?php
          $essentialKeys = [$config['name']];
          $hasImage = isset($config['fields']['image_url']);
          ?>

          <div class="form-steps" role="tablist">
            <button type="button" class="form-step is-active" data-step="1" role="tab" aria-selected="true">Essentiel</button>
            <button type="button" class="form-step" data-step="2" role="tab" aria-selected="false">Contenu</button>
            <button type="button" class="form-step" data-step="3" role="tab" aria-selected="false">Publication</button>
          </div>

          <div class="form-step-pane is-active" data-step-pane="1">
            <div class="form-grid">
              <?php foreach ($config['fields'] as $key => $label):
                if (!in_array($key, $essentialKeys, true)) continue;
                $value  = (string)($editing[$key] ?? '');
                $isRequired = ($key === $config['name']);
              ?>
                <div class="field wide">
                  <label for="f-<?= admin_escape($key) ?>">
                    <?= admin_escape($label) ?>
                    <?php if ($isRequired): ?><span style="color:#e74c3c;">*</span><?php endif; ?>
                  </label>
                  <input id="f-<?= admin_escape($key) ?>"
                         name="<?= admin_escape($key) ?>"
                         type="text"
                         value="<?= admin_escape($value) ?>"
                         <?= $isRequired ? 'required' : '' ?>>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="form-step-pane" data-step-pane="2">
            <div class="form-grid">
              <?php foreach ($config['fields'] as $key => $label):
                if (in_array($key, $essentialKeys, true)) continue;
                if ($key === 'slug') continue;
                if ($key === 'published_at' || $key === 'date_debut' || $key === 'date_fin') continue;
                $value  = (string)($editing[$key] ?? '');
                $isText = in_array($key, $config['text'], true);
              ?>
                <div class="field <?= $isText ? 'wide' : '' ?>">
                  <label for="f-<?= admin_escape($key) ?>"><?= admin_escape($label) ?></label>
                  <?php if ($isText): ?>
                    <textarea id="f-<?= admin_escape($key) ?>"
                              name="<?= admin_escape($key) ?>"
                              rows="3"
                              <?= $key === 'contenu' || $key === 'description' ? 'required' : '' ?>><?= admin_escape($value) ?></textarea>
                  <?php elseif ($key === 'image_url'): ?>
                    <input id="f-<?= admin_escape($key) ?>"
                           name="<?= admin_escape($key) ?>"
                           type="url"
                           value="<?= admin_escape($value) ?>">
                    <div class="hint">URL complète de l'image (HTTPS recommandé).</div>
                  <?php else: ?>
                    <input id="f-<?= admin_escape($key) ?>"
                           name="<?= admin_escape($key) ?>"
                           type="<?= in_array($key, ['prix','prix_min'], true) ? 'number' : 'text' ?>"
                           value="<?= admin_escape($value) ?>">
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="form-step-pane" data-step-pane="3">
            <div class="form-grid">
              <?php foreach ($config['fields'] as $key => $label):
                if ($key !== 'published_at' && $key !== 'date_debut' && $key !== 'date_fin') continue;
                $value = (string)($editing[$key] ?? '');
                if ($value !== '') $value = str_replace(' ', 'T', substr($value, 0, 16));
              ?>
                <div class="field wide">
                  <label for="f-<?= admin_escape($key) ?>"><?= admin_escape($label) ?></label>
                  <input id="f-<?= admin_escape($key) ?>"
                         name="<?= admin_escape($key) ?>"
                         type="datetime-local"
                         value="<?= admin_escape($value) ?>">
                </div>
              <?php endforeach; ?>

              <div class="field wide">
                <label for="f-statut">Statut de publication</label>
                <select name="statut" id="f-statut">
                  <option value="brouillon" <?= ($editing['statut'] ?? '') === 'brouillon' ? 'selected' : '' ?>>Brouillon</option>
                  <option value="publie"    <?= ($editing['statut'] ?? '') === 'publie'    ? 'selected' : '' ?>>Publié</option>
                  <option value="archive"   <?= ($editing['statut'] ?? '') === 'archive'   ? 'selected' : '' ?>>Archivé</option>
                </select>
              </div>
            </div>

            <div class="form-actions">
              <button class="button primary" type="submit">
                <?= $editing ? 'Enregistrer les modifications' : 'Ajouter le contenu' ?>
              </button>
              <?php if ($editing): ?>
                <a class="button" href="?type=<?= admin_escape($type) ?>">Annuler</a>
              <?php endif; ?>
            </div>
          </div>
        </form>
      </section>

      <section class="panel">
        <h2 class="panel-title">
          <span>Contenus récents</span>
          <span style="font-size:0.72rem; font-weight:500; color:var(--muted);">
            <?= count($rows) ?> au total
          </span>
        </h2>

        <?php if (!$rows): ?>
          <div class="empty-state">
            <p>Aucun contenu pour le moment. Ajoutez-en un avec le formulaire.</p>
          </div>
        <?php else: ?>
          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Titre</th>
                  <th>Statut</th>
                  <th>Modifié</th>
                  <th style="text-align:right;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $row): ?>
                  <tr>
                    <td>
                      <p class="row-title"><?= admin_escape($row['titre']) ?></p>
                      <code class="row-slug"><?= admin_escape($row['slug']) ?></code>
                    </td>
                    <td>
                      <span class="status-badge is-<?= admin_escape($row['statut']) ?>">
                        <?= admin_escape($row['statut']) ?>
                      </span>
                    </td>
                    <td style="white-space:nowrap;">
                      <?= admin_escape(date('d/m/Y H:i', strtotime((string)$row['updated_at']))) ?>
                    </td>
                    <td>
                      <div class="row-actions">
                        <a class="button sm" href="?type=<?= admin_escape($type) ?>&id=<?= (int)$row['id'] ?>">
                          Modifier
                        </a>
                        <form method="post" style="display:inline;"
                              onsubmit="return confirm('Retirer ce contenu ?');">
                          <input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>">
                          <input type="hidden" name="type" value="<?= admin_escape($type) ?>">
                          <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                          <input type="hidden" name="action" value="delete">
                          <button class="button sm danger" type="submit">Retirer</button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>

    </div>
  </div>
</main>

<script>
(function () {
  const form = document.getElementById('contentForm');
  if (!form) return;

  const steps       = Array.from(form.querySelectorAll('.form-step'));
  const panes       = Array.from(form.querySelectorAll('.form-step-pane'));

  let currentStep  = 1;
  let swipeTimer   = null;
  let isAnimating  = false;

  function getPane(n) {
    return panes.find(p => p.dataset.stepPane === String(n));
  }

  /* -------- Vérifie si TOUS les champs visibles d'un panneau sont remplis -------- */
  function isPaneFullyFilled(pane) {
    if (!pane) return false;

    const fields = Array.from(pane.querySelectorAll('input, textarea, select'))
      .filter(f => f.type !== 'hidden' && f.name !== 'csrf' && f.name !== 'type' && f.name !== 'id');

    if (fields.length === 0) return false;

    for (const field of fields) {
      const val = (field.value || '').trim();

      if (val === '') return false;

      if (field.type === 'url' && !/^https?:\/\/.+/i.test(val)) return false;

      if (field.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) return false;

      if (field.type === 'number' && isNaN(Number(val))) return false;
    }
    return true;
  }

  /* -------- Changement d'étape AVEC animation de swipe -------- */
  function goTo(stepNumber, animate = true) {
    stepNumber = Math.min(Math.max(stepNumber, 1), steps.length);

    const direction   = stepNumber > currentStep ? 'forward' : 'backward';
    const oldPane     = getPane(currentStep);
    const newPane     = getPane(stepNumber);

    if (!newPane) return;

    steps.forEach(function (s) {
      const isActive = s.dataset.step === String(stepNumber);
      s.classList.toggle('is-active', isActive);
      s.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });

    if (oldPane === newPane || !animate) {
      panes.forEach(p => {
        p.classList.remove('is-active','swipe-in-forward','swipe-in-backward','swipe-out-forward','swipe-out-backward');
      });
      newPane.classList.add('is-active');
      currentStep = stepNumber;
      return;
    }

    isAnimating = true;

    oldPane.classList.add(direction === 'forward' ? 'swipe-out-forward' : 'swipe-out-backward');

    const OUT_DURATION = 350;

    setTimeout(function () {
      panes.forEach(p => {
        p.classList.remove('is-active','swipe-in-forward','swipe-in-backward','swipe-out-forward','swipe-out-backward');
      });

      newPane.classList.add('is-active');
      void newPane.offsetWidth;
      newPane.classList.add(direction === 'forward' ? 'swipe-in-forward' : 'swipe-in-backward');

      currentStep = stepNumber;

      setTimeout(function () {
        newPane.classList.remove('swipe-in-forward','swipe-in-backward');
        isAnimating = false;
      }, 550);
    }, OUT_DURATION);
  }

  /* -------- Swipe AUTO si l'étape courante est 100% remplie -------- */
  function scheduleAutoAdvance() {
    if (isAnimating) return;
    if (currentStep >= steps.length) return;

    const pane = getPane(currentStep);
    if (!isPaneFullyFilled(pane)) {
      if (swipeTimer) { clearTimeout(swipeTimer); swipeTimer = null; }
      return;
    }

    if (swipeTimer) clearTimeout(swipeTimer);

    swipeTimer = setTimeout(function () {
      swipeTimer = null;
      if (!isAnimating && currentStep < steps.length && isPaneFullyFilled(getPane(currentStep))) {
        goTo(currentStep + 1, true);
      }
    }, 1400);
  }

  /* -------- Écouteurs -------- */
  steps.forEach(function (step) {
    step.addEventListener('click', function () {
      if (isAnimating) return;
      if (swipeTimer) { clearTimeout(swipeTimer); swipeTimer = null; }
      goTo(Number(step.dataset.step), true);
    });
  });

  panes.forEach(function (pane) {
    pane.querySelectorAll('input, textarea, select').forEach(function (field) {
      const handler = function () {
        const paneStep = Number(pane.dataset.stepPane);
        if (paneStep !== currentStep) return;
        scheduleAutoAdvance();
      };
      field.addEventListener('input', handler);
      field.addEventListener('change', handler);
      field.addEventListener('blur', handler);
    });
  });
})();
</script>
</body>
</html>