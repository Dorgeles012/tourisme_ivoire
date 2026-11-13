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
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= $id?'Modifier':'Créer' ?> une visite virtuelle — Administration</title>
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

    /* ===== LAYOUT FIXE ===== */
    .sidebar {
      position: fixed !important;
      top: 0 !important; left: 0 !important; bottom: 0 !important;
      height: 100vh !important; overflow-y: auto; z-index: 1000;
    }
    .topbar {
      position: fixed; top: 0; left: var(--sidebar-width); right: 0;
      height: var(--topbar-height);
      background: #fff; border-bottom: 1px solid var(--border-color);
      padding: 0 1.75rem;
      display: flex; justify-content: space-between; align-items: center;
      z-index: 900; box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }
    .topbar strong { font-weight: 700; letter-spacing: 0.5px; color: #1a1d29; font-size: 0.95rem; }
    .topbar a {
      color: #4a5568; text-decoration: none; font-size: 0.875rem; font-weight: 500;
      padding: 0.45rem 1rem; border-radius: 8px; border: 1px solid var(--border-color);
      background: #fff; transition: all 0.2s;
    }
    .topbar a:hover { background: #f8f9fb; border-color: #d0d5dd; color: #1a1d29; }

    /* Contenu */
    .content {
      margin-left: var(--sidebar-width);
      padding: calc(var(--topbar-height) + 2rem) 2.5rem 2rem;
      min-height: 100vh;
    }
    .content h1 { font-size: 1.75rem; font-weight: 700; color: #1a1d29; margin-bottom: 0.25rem; }
    .muted { color: #8892a6; font-size: 0.9rem; margin-bottom: 1.5rem; }

    /* Panel / Form card */
    .panel {
      background: #fff; border-radius: 14px; padding: 2rem 2.25rem;
      border: 1px solid var(--border-color);
      box-shadow: 0 4px 16px rgba(0,0,0,0.04);
    }
    .panel label {
      display: block; font-size: 0.82rem; font-weight: 600;
      color: #34495e; margin-bottom: 0.35rem;
    }
    .panel input, .panel select, .panel textarea {
      width: 100%; border: 1px solid var(--border-color); border-radius: 10px;
      padding: 0.65rem 0.95rem; font-size: 0.9rem; color: #1a1d29;
      background: #fafbfc; transition: all 0.2s; font-family: inherit;
    }
    .panel input:focus, .panel select:focus, .panel textarea:focus {
      outline: none; border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(230,126,34,0.15); background: #fff;
    }

    /* Grille de formulaire */
    .editor-grid {
      display: grid; grid-template-columns: 1fr 1fr; gap: 1.1rem 1.25rem;
    }
    .editor-grid .wide { grid-column: 1 / -1; }
    @media (max-width: 720px) {
      .editor-grid { grid-template-columns: 1fr; }
    }

    /* Hint / aside info */
    .hint { font-size: 0.75rem; color: #8892a6; margin-top: 0.3rem; }
    .info-box {
      background: #fffaf5; border: 1px solid #f5cba7; border-left: 4px solid var(--primary);
      color: #7e5109; border-radius: 10px; padding: 0.9rem 1.1rem;
      font-size: 0.85rem; line-height: 1.55; margin: 1.25rem 0;
    }

    /* Buttons */
    .button {
      display: inline-block; text-decoration: none; border-radius: 10px;
      padding: 0.7rem 1.5rem; font-size: 0.9rem; font-weight: 600;
      border: 1px solid var(--border-color); background: #fff; color: #4a5568;
      transition: all 0.2s; cursor: pointer;
    }
    .button.primary {
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      color: #fff; border: none; box-shadow: 0 4px 12px rgba(230,126,34,0.3);
    }
    .button.primary:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(230,126,34,0.4); }

    /* Notice */
    .notice { padding: 1rem 1.25rem; border-radius: 10px; font-size: 0.875rem; margin-bottom: 1.5rem; font-weight: 500; }
    .notice.error { background: #fdecea; color: #c0392b; border-left: 4px solid #e74c3c; }

    /* Responsive */
    @media (max-width: 992px) {
      .sidebar { position: static !important; height: auto !important; width: 100% !important; }
      .topbar { position: static; left: 0; height: auto; padding: 0.85rem 1rem; }
      .content { margin-left: 0; padding: 1.5rem 1rem; }
      .panel { padding: 1.5rem; }
    }
  </style>
</head>
<body class="admin-page">
<?php require __DIR__ . '/_sidebar.php'; ?>

<header class="topbar">
  <strong>TOURISME IVOIRE · ADMIN</strong>
  <a href="index.php?module=virtual_visits">← Retour aux visites virtuelles</a>
</header>

<main class="content">
  <h1><?= $id?'Modifier':'Créer' ?> une visite virtuelle</h1>
  <p class="muted">Une visite doit être associée à un patrimoine <em>ou</em> à une destination, jamais aux deux. Elle ne peut être activée que si le contenu associé est publié.</p>

  <?php if ($error!==''): ?>
    <div class="notice error"><?= admin_escape($error) ?></div>
  <?php endif; ?>

  <form class="panel" method="post">
    <input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>">

    <div class="editor-grid">
      <div class="wide">
        <label for="titre">Titre</label>
        <input id="titre" name="titre" maxlength="180" required value="<?= admin_escape($visit['titre']) ?>" placeholder="Ex : Visite 360° de la Basilique">
      </div>

      <div>
        <label for="type_visite">Type de visite</label>
        <select id="type_visite" name="type_visite">
          <?php foreach (['360'=>'Panorama 360°','video'=>'Vidéo','ar'=>'Réalité augmentée','avant_apres'=>'Avant / après'] as $type=>$label): ?>
            <option value="<?= admin_escape($type) ?>" <?= $visit['type_visite']===$type?'selected':'' ?>><?= admin_escape($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="statut">État</label>
        <select id="statut" name="statut">
          <option value="inactif" <?= $visit['statut']==='inactif'?'selected':'' ?>>Inactif</option>
          <option value="actif" <?= $visit['statut']==='actif'?'selected':'' ?>>Actif</option>
        </select>
        <div class="hint">Une nouvelle visite reste inactive par défaut.</div>
      </div>

      <div class="wide">
        <label for="url">URL de la visite</label>
        <input id="url" name="url" type="url" maxlength="500" required value="<?= admin_escape($visit['url']) ?>" placeholder="https://exemple.com/visite-360">
      </div>

      <div class="wide">
        <label for="fournisseur">Fournisseur <span class="text-muted" style="font-weight:400">(facultatif)</span></label>
        <input id="fournisseur" name="fournisseur" maxlength="120" value="<?= admin_escape($visit['fournisseur']) ?>" placeholder="Ex : Matterport, Google Arts & Culture…">
      </div>

      <div>
        <label for="patrimoine_id">Patrimoine associé</label>
        <select id="patrimoine_id" name="patrimoine_id">
          <option value="">Aucun</option>
          <?php foreach ($heritages as $row): ?>
            <option value="<?= admin_escape($row['id']) ?>" <?= (string)$visit['patrimoine_id']===(string)$row['id']?'selected':'' ?>>
              <?= admin_escape($row['titre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="destination_id">Destination associée</label>
        <select id="destination_id" name="destination_id">
          <option value="">Aucune</option>
          <?php foreach ($destinations as $row): ?>
            <option value="<?= admin_escape($row['id']) ?>" <?= (string)$visit['destination_id']===(string)$row['id']?'selected':'' ?>>
              <?= admin_escape($row['nom']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="info-box">
      ⚠️ <strong>Règle d'association :</strong> choisissez <strong>un seul</strong> patrimoine <em>ou</em> une seule destination. Si les deux champs sont remplis, l'enregistrement sera refusé.
    </div>

    <div style="margin-top:1.25rem; display:flex; gap:0.75rem;">
      <button class="button primary" type="submit">Enregistrer</button>
      <a class="button" href="index.php?module=virtual_visits">Annuler</a>
    </div>
  </form>
</main>
</body>
</html>