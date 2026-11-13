<?php
declare(strict_types=1);
require_once dirname(__DIR__,2) . '/admin/bootstrap.php';
admin_require_module('images',true);
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
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Images — Administration</title>
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
    .content-inner { max-width: 960px; margin: 0 auto; }
    .content h1 { font-size: 1.75rem; font-weight: 700; color: #1a1d29; margin-bottom: 0.25rem; }
    .content h2 { font-size: 1.15rem; font-weight: 600; color: #1a1d29; margin: 2rem 0 1rem; }
    .muted { color: #8892a6; font-size: 0.9rem; margin-bottom: 1.5rem; }

    /* Panel / Form card */
    .panel {
      background: #fff; border-radius: 14px; padding: 2rem 2.25rem;
      border: 1px solid var(--border-color);
      box-shadow: 0 4px 16px rgba(0,0,0,0.04);
    }
    .panel label {
      display: block; font-size: 0.82rem; font-weight: 600;
      color: #34495e; margin-bottom: 0.35rem; margin-top: 1rem;
    }
    .panel label:first-of-type { margin-top: 0; }
    .panel input[type="text"], .panel input[type="url"], .panel input:not([type]), .panel select {
      width: 100%; border: 1px solid var(--border-color); border-radius: 10px;
      padding: 0.65rem 0.95rem; font-size: 0.9rem; color: #1a1d29;
      background: #fafbfc; transition: all 0.2s; font-family: inherit;
    }
    .panel input[type="file"] {
      width: 100%; padding: 0.55rem 0.75rem; font-size: 0.85rem;
      background: #fafbfc; border: 1px dashed var(--border-color); border-radius: 10px;
      cursor: pointer;
    }
    .panel input[type="file"]:hover { border-color: var(--primary); background: #fffaf5; }
    .panel input:focus, .panel select:focus {
      outline: none; border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(230,126,34,0.15); background: #fff;
    }
    .panel .checkbox-row {
      display: flex; align-items: center; gap: 0.5rem; margin-top: 1rem;
    }
    .panel .checkbox-row input[type="checkbox"] {
      width: 18px; height: 18px; accent-color: var(--primary); cursor: pointer;
    }
    .panel .checkbox-row label { margin: 0; font-weight: 500; cursor: pointer; }

    /* Grille de formulaire */
    .editor-grid {
      display: grid; grid-template-columns: 1fr 1fr; gap: 0.25rem 1.25rem;
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
    .button:hover { background: #f8f9fb; border-color: #d0d5dd; color: #1a1d29; }
    .button.primary {
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      color: #fff; border: none; box-shadow: 0 4px 12px rgba(230,126,34,0.3);
    }
    .button.primary:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(230,126,34,0.4); }
    .button.small { padding: 0.4rem 0.85rem; font-size: 0.8rem; }

    /* Notice */
    .notice { padding: 1rem 1.25rem; border-radius: 10px; font-size: 0.875rem; margin-bottom: 1.5rem; font-weight: 500; }
    .notice.error { background: #fdecea; color: #c0392b; border-left: 4px solid #e74c3c; }

    /* Grille de cartes images */
    .image-grid {
      display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
      gap: 1.25rem;
    }
    .image-card {
      background: #fff; border-radius: 14px; overflow: hidden;
      border: 1px solid var(--border-color);
      box-shadow: 0 2px 8px rgba(0,0,0,0.03);
      transition: transform 0.2s, box-shadow 0.2s;
      display: flex; flex-direction: column;
    }
    .image-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.06); }
    .image-thumb {
      width: 100%; height: 160px; object-fit: cover; background: #f0f2f5;
      display: block;
    }
    .image-body { padding: 1rem 1.15rem; flex: 1; display: flex; flex-direction: column; }
    .image-body .image-dest { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.8px; color: var(--primary); font-weight: 700; margin-bottom: 0.35rem; }
    .image-body .image-credit { font-size: 0.875rem; font-weight: 600; color: #1a1d29; line-height: 1.3; margin-bottom: 0.2rem; }
    .image-body .image-licence { font-size: 0.78rem; color: #8892a6; margin-bottom: 0.75rem; }
    .image-body .image-badges { display: flex; gap: 0.35rem; flex-wrap: wrap; margin-bottom: 0.85rem; }
    .badge-status {
      font-size: 0.7rem; font-weight: 600; padding: 0.2rem 0.6rem; border-radius: 50px;
      text-transform: uppercase; letter-spacing: 0.4px;
    }
    .badge-actif { background: #e8f8f0; color: #1e8449; }
    .badge-inactif { background: #f0f2f5; color: #6c7a89; }
    .badge-principale { background: #fff8e5; color: #b9770e; }
    .image-body .image-actions { margin-top: auto; }
    .image-body .image-actions a {
      display: inline-block; padding: 0.45rem 0.9rem; border-radius: 8px;
      background: #fafbfc; border: 1px solid var(--border-color);
      color: var(--primary); font-size: 0.8rem; font-weight: 600;
      text-decoration: none; transition: all 0.2s;
    }
    .image-body .image-actions a:hover { background: #fffaf5; border-color: var(--primary); }

    /* Empty state */
    .empty-state {
      background: #fff; border: 1px dashed var(--border-color); border-radius: 14px;
      padding: 2.5rem; text-align: center; color: #8892a6; font-size: 0.9rem;
    }

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
  <a href="index.php?module=images">← Retour aux images</a>
</header>

<main class="content">
  <div class="content-inner">
    <h1><?= $imageId?'Modifier les crédits':'Téléverser une image' ?></h1>
    <p class="muted">
      <?php if ($imageId): ?>
        Modifiez les métadonnées (crédit, licence, texte alternatif) de l'image sélectionnée.
      <?php else: ?>
        Formats acceptés : JPEG, PNG, WebP — 10 Mo maximum. Le crédit et la licence sont obligatoires.
      <?php endif; ?>
    </p>

    <?php if ($error!==''): ?>
      <div class="notice error"><?= admin_escape($error) ?></div>
    <?php endif; ?>

    <form class="panel" method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>">
      <input type="hidden" name="destination_id" value="<?= admin_escape($destinationId) ?>">

      <div class="editor-grid">
        <?php if (!$imageId): ?>
          <div class="wide">
            <label for="destination_id_select">Destination</label>
            <select id="destination_id_select" name="destination_id" required>
              <option value="">Choisir une destination…</option>
              <?php foreach ($destinations as $row): ?>
                <option value="<?= admin_escape($row['id']) ?>" <?= (string)$destinationId===(string)$row['id']?'selected':'' ?>>
                  <?= admin_escape($row['nom']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="wide">
            <label for="image">Fichier image</label>
            <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp" required>
            <div class="hint">JPEG, PNG ou WebP — 10 Mo maximum.</div>
          </div>
        <?php endif; ?>

        <div class="wide">
          <label for="texte_alternatif">Texte alternatif</label>
          <input id="texte_alternatif" name="texte_alternatif" maxlength="255" value="<?= admin_escape($image['texte_alternatif'] ?? '') ?>" placeholder="Description de l'image pour l'accessibilité">
        </div>

        <div>
          <label for="credit">Crédit</label>
          <input id="credit" name="credit" maxlength="190" required value="<?= admin_escape($image['credit'] ?? '') ?>" placeholder="Ex : Auteur / Agence">
        </div>

        <div>
          <label for="licence">Licence / autorisation</label>
          <input id="licence" name="licence" maxlength="190" required value="<?= admin_escape($image['licence'] ?? '') ?>" placeholder="Ex : CC BY-SA 4.0">
        </div>

        <?php if ($imageId): ?>
          <div class="wide">
            <label for="statut">État</label>
            <select id="statut" name="statut">
              <option value="actif" <?= $image['statut']==='actif'?'selected':'' ?>>Actif</option>
              <option value="inactif" <?= $image['statut']==='inactif'?'selected':'' ?>>Inactif</option>
            </select>
          </div>
        <?php endif; ?>

        <div class="wide">
          <div class="checkbox-row">
            <input type="checkbox" id="principale" name="principale" value="1" <?= !empty($image['est_principale'])?'checked':'' ?>>
            <label for="principale">Définir comme image principale de la destination</label>
          </div>
        </div>
      </div>

      <div style="margin-top:1.75rem; display:flex; gap:0.75rem;">
        <button class="button primary" type="submit">Enregistrer</button>
        <a class="button" href="index.php?module=images">Annuler</a>
      </div>
    </form>

    <?php if (!$imageId): ?>
      <h2>Images récentes</h2>
      <?php if ($images): ?>
        <div class="image-grid">
          <?php foreach ($images as $row): ?>
            <div class="image-card">
              <img class="image-thumb" src="<?= admin_escape($row['url']) ?>" alt="<?= admin_escape($row['texte_alternatif'] ?? $row['credit']) ?>" loading="lazy">
              <div class="image-body">
                <div class="image-dest"><?= admin_escape($row['nom']) ?></div>
                <div class="image-credit">© <?= admin_escape($row['credit']) ?></div>
                <div class="image-licence"><?= admin_escape($row['licence']) ?></div>
                <div class="image-badges">
                  <span class="badge-status badge-<?= admin_escape($row['statut']) ?>"><?= admin_escape($row['statut']) ?></span>
                  <?php if (!empty($row['est_principale'])): ?>
                    <span class="badge-status badge-principale">★ Principale</span>
                  <?php endif; ?>
                </div>
                <div class="image-actions">
                  <a href="image.php?id=<?= admin_escape($row['id']) ?>">Modifier</a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty-state">Aucune image téléversée pour le moment.</div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</main>
</body>
</html>