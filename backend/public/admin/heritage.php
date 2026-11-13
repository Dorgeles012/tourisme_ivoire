<?php
declare(strict_types=1);
require_once dirname(__DIR__,2) . '/admin/bootstrap.php';
admin_require_module('heritage',true);
$id = filter_var($_GET['id'] ?? null,FILTER_VALIDATE_INT);
$heritage = ['titre'=>'','slug'=>'','localite_id'=>'','categorie_id'=>'','contenu'=>'','periode'=>'','statut'=>'brouillon'];
$error = '';
if ($id) {
    $stmt = db()->prepare('SELECT * FROM patrimoines WHERE id=?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) { http_response_code(404); exit('Patrimoine introuvable.'); }
    $heritage = $found;
}
$localities = db()->query("SELECT l.id,CONCAT(l.nom,' — ',COALESCE(r.nom,d.nom)) AS label FROM localites l JOIN departements dep ON dep.id=l.departement_id LEFT JOIN regions r ON r.id=dep.region_id LEFT JOIN districts d ON d.id=dep.district_id WHERE l.statut='actif' ORDER BY label")->fetchAll();
$categories = db()->query("SELECT id,nom FROM categories WHERE statut='actif' ORDER BY ordre,nom")->fetchAll();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_check_csrf();
    $heritage['titre'] = trim((string)($_POST['titre'] ?? ''));
    $heritage['slug'] = strtolower(trim((string)($_POST['slug'] ?? '')));
    $heritage['contenu'] = trim((string)($_POST['contenu'] ?? ''));
    $heritage['periode'] = trim((string)($_POST['periode'] ?? ''));
    $heritage['localite_id'] = filter_var($_POST['localite_id'] ?? null,FILTER_VALIDATE_INT);
    $heritage['categorie_id'] = filter_var($_POST['categorie_id'] ?? null,FILTER_VALIDATE_INT);
    $heritage['statut'] = (string)($_POST['statut'] ?? 'brouillon');
    if ($heritage['titre']==='' || mb_strlen($heritage['titre'])>180 || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/',$heritage['slug']) || $heritage['contenu']==='' || mb_strlen($heritage['periode'])>120) {
        $error = 'Titre, slug valide et contenu sont requis; la période ne doit pas dépasser 120 caractères.';
    } elseif (!in_array($heritage['statut'],['brouillon','publie','archive'],true)) {
        $error = 'Statut de publication invalide.';
    } else {
        $localityId = $heritage['localite_id'] ?: null;
        $categoryId = $heritage['categorie_id'] ?: null;
        if ($localityId) {
            $check = db()->prepare("SELECT id FROM localites WHERE id=? AND statut='actif'");
            $check->execute([$localityId]);
            if (!$check->fetchColumn()) $error = 'La localité sélectionnée est introuvable ou inactive.';
        }
        if ($error==='' && $categoryId) {
            $check = db()->prepare("SELECT id FROM categories WHERE id=? AND statut='actif'");
            $check->execute([$categoryId]);
            if (!$check->fetchColumn()) $error = 'La catégorie sélectionnée est introuvable ou inactive.';
        }
        if ($error==='') {
            $values = [$localityId,$categoryId,$heritage['titre'],$heritage['slug'],$heritage['contenu'],$heritage['periode'] ?: null,$heritage['statut']];
            if ($id) {
                db()->prepare('UPDATE patrimoines SET localite_id=?,categorie_id=?,titre=?,slug=?,contenu=?,periode=?,statut=?,is_demo=0 WHERE id=?')
                    ->execute([...$values,$id]);
            } else {
                db()->prepare('INSERT INTO patrimoines(localite_id,categorie_id,titre,slug,contenu,periode,statut,is_demo) VALUES(?,?,?,?,?,?,?,0)')
                    ->execute($values);
            }
            admin_flash('Patrimoine enregistré.');
            header('Location: index.php?module=heritage');
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
  <title><?= $id?'Modifier':'Créer' ?> un patrimoine — Administration</title>
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
    .panel textarea { resize: vertical; min-height: 180px; line-height: 1.6; }

    /* Grille de formulaire */
    .editor-grid {
      display: grid; grid-template-columns: 1fr 1fr; gap: 1.1rem 1.25rem;
    }
    .editor-grid .wide { grid-column: 1 / -1; }
    @media (max-width: 720px) {
      .editor-grid { grid-template-columns: 1fr; }
    }

    /* Hint */
    .hint { font-size: 0.75rem; color: #8892a6; margin-top: 0.3rem; }

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
  <a href="index.php?module=heritage">← Retour aux patrimoines</a>
</header>

<main class="content">
  <h1><?= $id?'Modifier':'Créer' ?> un patrimoine</h1>
  <p class="muted">Renseignez les informations culturelles du patrimoine. Le contenu doit être vérifié avant publication.</p>

  <?php if ($error!==''): ?>
    <div class="notice error"><?= admin_escape($error) ?></div>
  <?php endif; ?>

  <form class="panel" method="post">
    <input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>">

    <div class="editor-grid">
      <div class="wide">
        <label for="titre">Titre</label>
        <input id="titre" name="titre" maxlength="180" required value="<?= admin_escape($heritage['titre']) ?>" placeholder="Ex : Basilique Notre-Dame de la Paix">
      </div>

      <div>
        <label for="slug">Slug</label>
        <input id="slug" name="slug" maxlength="200" required value="<?= admin_escape($heritage['slug']) ?>" placeholder="basilique-notre-dame-de-la-paix">
        <div class="hint">Lettres minuscules, chiffres et tirets uniquement.</div>
      </div>

      <div>
        <label for="periode">Période</label>
        <input id="periode" name="periode" maxlength="120" value="<?= admin_escape($heritage['periode']) ?>" placeholder="Ex : XIXe siècle">
        <div class="hint">120 caractères maximum.</div>
      </div>

      <div>
        <label for="localite_id">Localité</label>
        <select id="localite_id" name="localite_id">
          <option value="">Aucune</option>
          <?php foreach ($localities as $row): ?>
            <option value="<?= admin_escape($row['id']) ?>" <?= (string)$heritage['localite_id']===(string)$row['id']?'selected':'' ?>>
              <?= admin_escape($row['label']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="categorie_id">Catégorie</label>
        <select id="categorie_id" name="categorie_id">
          <option value="">Aucune</option>
          <?php foreach ($categories as $row): ?>
            <option value="<?= admin_escape($row['id']) ?>" <?= (string)$heritage['categorie_id']===(string)$row['id']?'selected':'' ?>>
              <?= admin_escape($row['nom']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="wide">
        <label for="contenu">Contenu culturel</label>
        <textarea id="contenu" name="contenu" rows="10" required placeholder="Décrivez l'histoire, la valeur culturelle et les caractéristiques de ce patrimoine…"><?= admin_escape($heritage['contenu']) ?></textarea>
      </div>

      <div>
        <label for="statut">Publication</label>
        <select id="statut" name="statut">
          <?php foreach (['brouillon','publie','archive'] as $status): ?>
            <option value="<?= admin_escape($status) ?>" <?= $heritage['statut']===$status?'selected':'' ?>>
              <?= admin_escape(ucfirst($status)) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div style="margin-top:1.75rem; display:flex; gap:0.75rem;">
      <button class="button primary" type="submit">Enregistrer</button>
      <a class="button" href="index.php?module=heritage">Annuler</a>
    </div>
  </form>
</main>
</body>
</html>