<?php
declare(strict_types=1);
require_once dirname(__DIR__,2) . '/admin/bootstrap.php';
admin_require_module('categories',true);
$id = filter_var($_GET['id'] ?? null,FILTER_VALIDATE_INT);
$category = ['nom'=>'','slug'=>'','description'=>'','icone'=>'','ordre'=>0,'statut'=>'actif','parent_id'=>''];
$error = '';
if ($id) {
    $stmt = db()->prepare('SELECT * FROM categories WHERE id=?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) { http_response_code(404); exit('Catégorie introuvable.'); }
    $category = $found;
}
$parents = db()->query('SELECT id,nom FROM categories ORDER BY nom')->fetchAll();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_check_csrf();
    $category['nom'] = trim((string)($_POST['nom'] ?? ''));
    $category['slug'] = strtolower(trim((string)($_POST['slug'] ?? '')));
    $category['description'] = trim((string)($_POST['description'] ?? ''));
    $category['icone'] = trim((string)($_POST['icone'] ?? ''));
    $category['ordre'] = filter_var($_POST['ordre'] ?? 0,FILTER_VALIDATE_INT);
    $category['parent_id'] = filter_var($_POST['parent_id'] ?? null,FILTER_VALIDATE_INT);
    $category['statut'] = (string)($_POST['statut'] ?? '');
    if ($category['nom']==='' || mb_strlen($category['nom'])>100 || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/',$category['slug']) || $category['ordre']===false) {
        $error = 'Nom, slug valide et ordre numérique requis.';
    } elseif (!in_array($category['statut'],['actif','inactif'],true)) {
        $error = 'État invalide.';
    } else {
        $parentId = $category['parent_id'] ?: null;
        if ($id && (int)$parentId===(int)$id) {
            $error = 'Une catégorie ne peut pas être son propre parent.';
        } else {
            if ($id) {
                db()->prepare('UPDATE categories SET parent_id=?,nom=?,slug=?,description=?,icone=?,ordre=?,statut=? WHERE id=?')
                    ->execute([$parentId,$category['nom'],$category['slug'],$category['description'] ?: null,$category['icone'] ?: null,$category['ordre'],$category['statut'],$id]);
            } else {
                db()->prepare('INSERT INTO categories(parent_id,nom,slug,description,icone,ordre,statut) VALUES(?,?,?,?,?,?,?)')
                    ->execute([$parentId,$category['nom'],$category['slug'],$category['description'] ?: null,$category['icone'] ?: null,$category['ordre'],$category['statut']]);
            }
            admin_flash('Catégorie enregistrée.');
            header('Location: index.php?module=categories');
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
  <title>Catégorie — Administration</title>
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
      padding: calc(var(--topbar-height) + 2.5rem) 2rem 2.5rem;
      min-height: 100vh;
    }
    .content-inner { max-width: 860px; margin: 0 auto; }
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
      color: #34495e; margin-bottom: 0.4rem;
    }
    .panel input, .panel select, .panel textarea {
      width: 100%; border: 1px solid var(--border-color); border-radius: 10px;
      padding: 0.7rem 1rem; font-size: 0.9rem; color: #1a1d29;
      background: #fafbfc; transition: all 0.2s; font-family: inherit;
    }
    .panel input:focus, .panel select:focus, .panel textarea:focus {
      outline: none; border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(230,126,34,0.15); background: #fff;
    }
    .panel textarea { resize: vertical; min-height: 90px; line-height: 1.55; }

    /* Grille de formulaire */
    .form-grid {
      display: grid; grid-template-columns: 1fr 1fr; gap: 1.1rem 1.25rem;
    }
    .form-grid .full { grid-column: 1 / -1; }
    @media (max-width: 640px) {
      .form-grid { grid-template-columns: 1fr; }
    }

    /* Hint sous champ */
    .hint { font-size: 0.75rem; color: #8892a6; margin-top: 0.3rem; }

    /* Boutons */
    .button {
      display: inline-block; text-decoration: none; border-radius: 10px;
      padding: 0.7rem 1.5rem; font-size: 0.9rem; font-weight: 600;
      border: 1px solid var(--border-color); background: #fff; color: #4a5568;
      transition: all 0.2s; cursor: pointer;
    }
    .button:hover { background: #f8f9fb; border-color: #d0d5dd; color: #1a1d29; }
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
  <a href="index.php?module=categories">← Retour aux catégories</a>
</header>

<main class="content">
  <div class="content-inner">
    <h1><?= $id?'Modifier':'Créer' ?> une catégorie</h1>
    <p class="muted">Les catégories permettent d'organiser les destinations et patrimoines du catalogue.</p>

    <?php if ($error!==''): ?>
      <div class="notice error"><?= admin_escape($error) ?></div>
    <?php endif; ?>

    <form class="panel" method="post">
      <input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>">

      <div class="form-grid">
        <!-- Nom (pleine largeur) -->
        <div class="full">
          <label for="nom">Nom</label>
          <input id="nom" name="nom" maxlength="100" required value="<?= admin_escape($category['nom']) ?>" placeholder="Ex : Plages et lagunes">
        </div>

        <!-- Slug -->
        <div>
          <label for="slug">Slug</label>
          <input id="slug" name="slug" required value="<?= admin_escape($category['slug']) ?>" placeholder="plages-et-lagunes">
          <div class="hint">Lettres minuscules, chiffres et tirets uniquement.</div>
        </div>

        <!-- Icône -->
        <div>
          <label for="icone">Icône</label>
          <input id="icone" name="icone" maxlength="80" value="<?= admin_escape($category['icone']) ?>" placeholder="Ex : bi-umbrella">
          <div class="hint">Nom d'icône (facultatif).</div>
        </div>

        <!-- Description (pleine largeur) -->
        <div class="full">
          <label for="description">Description</label>
          <textarea id="description" name="description" rows="3" placeholder="Description courte de la catégorie…"><?= admin_escape($category['description']) ?></textarea>
        </div>

        <!-- Parent -->
        <div>
          <label for="parent_id">Catégorie parente</label>
          <select id="parent_id" name="parent_id">
            <option value="">Aucune (racine)</option>
            <?php foreach ($parents as $row): if ($id && (int)$row['id']===(int)$id) continue; ?>
              <option value="<?= admin_escape($row['id']) ?>" <?= (string)$category['parent_id']===(string)$row['id']?'selected':'' ?>>
                <?= admin_escape($row['nom']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Ordre -->
        <div>
          <label for="ordre">Ordre d'affichage</label>
          <input id="ordre" name="ordre" type="number" min="0" max="65535" value="<?= admin_escape($category['ordre']) ?>">
          <div class="hint">Plus petit = affiché en premier.</div>
        </div>

        <!-- Statut -->
        <div>
          <label for="statut">État</label>
          <select id="statut" name="statut">
            <option value="actif" <?= $category['statut']==='actif'?'selected':'' ?>>Actif</option>
            <option value="inactif" <?= $category['statut']==='inactif'?'selected':'' ?>>Inactif</option>
          </select>
        </div>
      </div>

      <div style="margin-top:1.75rem; display:flex; gap:0.75rem;">
        <button class="button primary" type="submit">Enregistrer</button>
        <a class="button" href="index.php?module=categories">Annuler</a>
      </div>
    </form>
  </div>
</main>
</body>
</html>