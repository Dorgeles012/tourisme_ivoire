<?php
declare(strict_types=1);
require_once dirname(__DIR__,2) . '/admin/bootstrap.php';
admin_require_module('geography',true);
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
            db()->beginTransaction();
            $districtCheck=db()->prepare("SELECT type FROM districts WHERE id=? AND statut='actif'");
            $districtCheck->execute([$district]);
            if ($districtCheck->fetchColumn() !== 'ordinaire') throw new InvalidArgumentException('Un district autonome rattache directement ses départements, pas une région.');
            $regionCheck=db()->prepare("SELECT id FROM regions WHERE id=? AND statut='actif'");
            $regionCheck->execute([$region]);
            if (!$regionCheck->fetchColumn()) throw new InvalidArgumentException('Région introuvable ou inactive.');
            db()->prepare('UPDATE regions SET district_id=? WHERE id=?')->execute([$district,$region]);
            db()->prepare('INSERT INTO districts_regions(district_id,region_id,source_reference,verified_at) VALUES(?,?,?,NOW()) ON DUPLICATE KEY UPDATE source_reference=VALUES(source_reference),verified_at=NOW()')->execute([$district,$region,$source]);
            db()->commit();
        } elseif ($action==='remove_district_region') {
            $district=filter_var($_POST['district_id'] ?? null,FILTER_VALIDATE_INT);
            $region=filter_var($_POST['region_id'] ?? null,FILTER_VALIDATE_INT);
            if (!$district || !$region) throw new InvalidArgumentException('Relation invalide.');
            db()->beginTransaction();
            db()->prepare('UPDATE regions SET district_id=NULL WHERE district_id=? AND id=?')->execute([$district,$region]);
            db()->prepare('DELETE FROM districts_regions WHERE district_id=? AND region_id=?')->execute([$district,$region]);
            db()->commit();
        } else {
            throw new InvalidArgumentException('Action inconnue.');
        }
        admin_flash('Référentiel géographique mis à jour.');
        header('Location: geography.php');
        exit;
    } catch (Throwable $e) {
        if (db()->inTransaction()) db()->rollBack();
        error_log((string)$e);
        $error=$e instanceof InvalidArgumentException?$e->getMessage():'Enregistrement impossible; vérifiez les doublons et les rattachements.';
    }
}
$regions=db()->query('SELECT id,nom FROM regions WHERE statut="actif" ORDER BY nom')->fetchAll();
$districts=db()->query('SELECT id,nom FROM districts WHERE statut="actif" ORDER BY nom')->fetchAll();
$departments=db()->query('SELECT d.id,d.nom,COALESCE(r.nom,di.nom) AS parent FROM departements d LEFT JOIN regions r ON r.id=d.region_id LEFT JOIN districts di ON di.id=d.district_id ORDER BY parent,d.nom')->fetchAll();
$relations=db()->query('SELECT d.id AS district_id,d.nom AS district,r.id AS region_id,r.nom AS region,x.source_reference FROM regions r JOIN districts d ON d.id=r.district_id LEFT JOIN districts_regions x ON x.district_id=d.id AND x.region_id=r.id ORDER BY d.nom,r.nom')->fetchAll();
$csrf=admin_csrf();
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Géographie — Administration</title>
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
      top: 0 !important;
      left: 0 !important;
      bottom: 0 !important;
      height: 100vh !important;
      overflow-y: auto;
      z-index: 1000;
    }
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
    .content h2 { font-size: 1.05rem; font-weight: 600; color: #1a1d29; margin: 2rem 0 1rem; }
    .muted { color: #8892a6; font-size: 0.9rem; margin-bottom: 1.5rem; }

    /* Grille des formulaires */
    .editor-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
      gap: 1.25rem;
      margin-bottom: 1.5rem;
    }

    /* Panels / Form cards */
    .panel {
      background: #fff; border-radius: 14px; padding: 1.5rem 1.75rem;
      border: 1px solid var(--border-color);
      box-shadow: 0 2px 8px rgba(0,0,0,0.03);
      margin-bottom: 0;
    }
    .panel h2 { font-size: 1rem; font-weight: 600; color: #1a1d29; margin: 0 0 1rem; }
    .panel label {
      display: block; font-size: 0.82rem; font-weight: 600;
      color: #34495e; margin-bottom: 0.35rem; margin-top: 0.9rem;
    }
    .panel label:first-of-type { margin-top: 0; }
    .panel input, .panel select {
      width: 100%; border: 1px solid var(--border-color); border-radius: 10px;
      padding: 0.6rem 0.9rem; font-size: 0.9rem; color: #1a1d29;
      background: #fafbfc; transition: all 0.2s;
    }
    .panel input:focus, .panel select:focus {
      outline: none; border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(230,126,34,0.15); background: #fff;
    }
    .panel .row-2 {
      display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;
    }
    .panel .row-2 label { margin-top: 0.9rem; }

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
    .button.danger { background: #fff; color: #c0392b; border: 1px solid #f5b7b1; }
    .button.danger:hover { background: #fdecea; border-color: #e74c3c; }

    /* Table */
    .table-wrap { background: #fff; border-radius: 14px; border: 1px solid var(--border-color); overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.03); }
    table { width: 100%; border-collapse: collapse; }
    thead { background: #f8f9fb; }
    th { text-align: left; padding: 0.9rem 1.25rem; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: #8892a6; font-weight: 600; border-bottom: 1px solid var(--border-color); }
    td { padding: 0.95rem 1.25rem; font-size: 0.875rem; color: #4a5568; border-bottom: 1px solid #f0f2f5; vertical-align: middle; }
    tbody tr:hover { background: #fafbfc; }
    tbody tr:last-child td { border-bottom: none; }
    td a { color: var(--primary); text-decoration: none; font-weight: 500; }
    td a:hover { text-decoration: underline; }

    /* Notice */
    .notice { padding: 1rem 1.25rem; border-radius: 10px; font-size: 0.875rem; margin-bottom: 1.5rem; font-weight: 500; }
    .notice.error { background: #fdecea; color: #c0392b; border-left: 4px solid #e74c3c; }

    /* Responsive */
    @media (max-width: 992px) {
      .sidebar { position: static !important; height: auto !important; width: 100% !important; }
      .topbar { position: static; left: 0; height: auto; padding: 0.85rem 1rem; }
      .content { margin-left: 0; padding: 1.5rem 1rem; }
      .editor-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body class="admin-page">
<?php require __DIR__ . '/_sidebar.php'; ?>

<header class="topbar">
  <strong>TOURISME IVOIRE · ADMIN</strong>
  <a href="index.php">← Tableau de bord</a>
</header>

<main class="content">
  <h1>Référentiel géographique</h1>
  <p class="muted">N’ajoutez un rattachement district-région que si une source officielle l’atteste. Les coordonnées restent facultatives lorsqu’elles ne sont pas vérifiées.</p>

  <?php if ($error!==''): ?>
    <div class="notice error"><?= admin_escape($error) ?></div>
  <?php endif; ?>

  <div class="editor-grid">
    <!-- Formulaire : département -->
    <form class="panel" method="post">
      <h2>Ajouter un département</h2>
      <input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>">
      <input type="hidden" name="action" value="department">

      <label>Région / district</label>
      <select name="parent" required>
        <optgroup label="Régions">
          <?php foreach($regions as $row): ?>
            <option value="region:<?= admin_escape($row['id']) ?>"><?= admin_escape($row['nom']) ?></option>
          <?php endforeach; ?>
        </optgroup>
        <optgroup label="Districts">
          <?php foreach($districts as $row): ?>
            <option value="district:<?= admin_escape($row['id']) ?>"><?= admin_escape($row['nom']) ?></option>
          <?php endforeach; ?>
        </optgroup>
      </select>

      <label>Nom du département</label>
      <input name="nom" required placeholder="Ex : Abidjan">

      <p class="mt-3 mb-0"><button class="button primary" type="submit">Ajouter</button></p>
    </form>

    <!-- Formulaire : localité -->
    <form class="panel" method="post">
      <h2>Ajouter une localité</h2>
      <input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>">
      <input type="hidden" name="action" value="locality">

      <label>Département</label>
      <select name="departement_id" required>
        <?php foreach($departments as $row): ?>
          <option value="<?= admin_escape($row['id']) ?>"><?= admin_escape($row['nom']) ?> — <?= admin_escape($row['parent']) ?></option>
        <?php endforeach; ?>
      </select>

      <label>Nom</label>
      <input name="nom" required placeholder="Ex : Yopougon">

      <label>Slug</label>
      <input name="slug" required placeholder="yopougon">

      <div class="row-2">
        <div>
          <label>Latitude vérifiée</label>
          <input name="latitude" inputmode="decimal" placeholder="5.3364">
        </div>
        <div>
          <label>Longitude vérifiée</label>
          <input name="longitude" inputmode="decimal" placeholder="-4.0267">
        </div>
      </div>

      <p class="mt-3 mb-0"><button class="button primary" type="submit">Ajouter</button></p>
    </form>

    <!-- Formulaire : relation district-région -->
    <form class="panel" method="post">
      <h2>Documenter une relation district-région</h2>
      <input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>">
      <input type="hidden" name="action" value="district_region">

      <label>District</label>
      <select name="district_id">
        <?php foreach($districts as $row): ?>
          <option value="<?= admin_escape($row['id']) ?>"><?= admin_escape($row['nom']) ?></option>
        <?php endforeach; ?>
      </select>

      <label>Région</label>
      <select name="region_id">
        <?php foreach($regions as $row): ?>
          <option value="<?= admin_escape($row['id']) ?>"><?= admin_escape($row['nom']) ?></option>
        <?php endforeach; ?>
      </select>

      <label>Source officielle</label>
      <input name="source_reference" type="url" required placeholder="https://…">

      <p class="mt-3 mb-0"><button class="button primary" type="submit">Enregistrer le rattachement vérifié</button></p>
    </form>
  </div>

  <h2>Relations district-région enregistrées</h2>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>District</th><th>Région</th><th>Source</th><th style="width:120px"></th></tr>
      </thead>
      <tbody>
        <?php foreach($relations as $row): ?>
          <tr>
            <td><strong><?= admin_escape($row['district']) ?></strong></td>
            <td><?= admin_escape($row['region']) ?></td>
            <td>
              <?php if ($row['source_reference']): ?>
                <a href="<?= admin_escape($row['source_reference']) ?>" rel="noopener noreferrer" target="_blank">Consulter ↗</a>
              <?php else: ?>
                <span class="text-muted">Non renseignée</span>
              <?php endif; ?>
            </td>
            <td>
              <form method="post" class="m-0">
                <input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>">
                <input type="hidden" name="action" value="remove_district_region">
                <input type="hidden" name="district_id" value="<?= admin_escape($row['district_id']) ?>">
                <input type="hidden" name="region_id" value="<?= admin_escape($row['region_id']) ?>">
                <button type="submit" class="button small danger">Retirer</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if(!$relations): ?>
          <tr><td colspan="4" class="text-center text-muted py-4">Aucune relation documentée.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>
</body>
</html>