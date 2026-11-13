<?php
declare(strict_types=1);
require_once dirname(__DIR__,2) . '/admin/bootstrap.php';
admin_require_module('destinations',true);
$id = filter_var($_GET['id'] ?? null,FILTER_VALIDATE_INT);
$destination = ['nom'=>'','slug'=>'','localite_id'=>'','categorie_id'=>'','description'=>'','histoire'=>'','latitude'=>'','longitude'=>'','adresse'=>'','conseils_visite'=>'','informations_pratiques'=>'','source_reference'=>'','statut_publication'=>'brouillon'];
$error = '';
if ($id) {
    $stmt = db()->prepare('SELECT * FROM destinations WHERE id=? AND deleted_at IS NULL');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) { http_response_code(404); exit('Destination introuvable.'); }
    $destination = $found;
}
$localities = db()->query("SELECT l.id,CONCAT(l.nom,' — ',COALESCE(r.nom,di.nom)) AS label FROM localites l JOIN departements dep ON dep.id=l.departement_id LEFT JOIN regions r ON r.id=dep.region_id LEFT JOIN districts di ON di.id=dep.district_id WHERE l.statut='actif' ORDER BY label")->fetchAll();
$categories = db()->query("SELECT id,nom FROM categories WHERE statut='actif' ORDER BY ordre,nom")->fetchAll();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_check_csrf();
    $destination['nom'] = trim((string)($_POST['nom'] ?? ''));
    $destination['slug'] = strtolower(trim((string)($_POST['slug'] ?? '')));
    $destination['description'] = trim((string)($_POST['description'] ?? ''));
    $destination['histoire'] = trim((string)($_POST['histoire'] ?? ''));
    $destination['adresse'] = trim((string)($_POST['adresse'] ?? ''));
    $destination['conseils_visite'] = trim((string)($_POST['conseils_visite'] ?? ''));
    $destination['informations_pratiques'] = trim((string)($_POST['informations_pratiques'] ?? ''));
    $destination['source_reference'] = trim((string)($_POST['source_reference'] ?? ''));
    $destination['localite_id'] = filter_var($_POST['localite_id'] ?? null,FILTER_VALIDATE_INT);
    $destination['categorie_id'] = filter_var($_POST['categorie_id'] ?? null,FILTER_VALIDATE_INT);
    $destination['statut_publication'] = (string)($_POST['statut_publication'] ?? 'brouillon');
    $latitude = trim((string)($_POST['latitude'] ?? ''));
    $longitude = trim((string)($_POST['longitude'] ?? ''));
    $destination['latitude'] = $latitude;
    $destination['longitude'] = $longitude;
    $validSlug = preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/',$destination['slug']);
    $sourceUrl = $destination['source_reference'] === '' ? false : filter_var($destination['source_reference'],FILTER_VALIDATE_URL);
    $sourceScheme = is_string($destination['source_reference']) ? strtolower((string)parse_url($destination['source_reference'],PHP_URL_SCHEME)) : '';
    $lat = $latitude === '' ? null : filter_var($latitude,FILTER_VALIDATE_FLOAT);
    $lng = $longitude === '' ? null : filter_var($longitude,FILTER_VALIDATE_FLOAT);
    if ($destination['nom']==='' || mb_strlen($destination['nom'])>180 || !$validSlug || !$destination['description'] || !$destination['localite_id'] || !$destination['categorie_id']) {
        $error = 'Nom, slug, description, localité et catégorie sont requis.';
    } elseif (!in_array($destination['statut_publication'],['brouillon','publie','archive'],true)) {
        $error = 'Statut de publication invalide.';
    } elseif ($destination['statut_publication']==='publie' && (!$sourceUrl || !in_array($sourceScheme,['http','https'],true))) {
        $error = 'Ajoutez une URL de source vérifiable avant de publier.';
    } elseif (($latitude==='' xor $longitude==='') || ($lat!==null && ($lat===false || $lat < -90 || $lat > 90)) || ($lng!==null && ($lng===false || $lng < -180 || $lng > 180))) {
        $error = 'Les coordonnées doivent être fournies ensemble et comprises dans les plages autorisées.';
    } else {
        $values = [
            $destination['localite_id'],$destination['categorie_id'],$destination['nom'],$destination['slug'],
            $destination['description'],$destination['histoire'] ?: null,$lat,$lng,$destination['adresse'] ?: null,
            $destination['conseils_visite'] ?: null,$destination['informations_pratiques'] ?: null,
            $destination['source_reference'] ?: null,$destination['statut_publication'],
        ];
        if ($id) {
            $sql = "UPDATE destinations SET localite_id=?,categorie_id=?,nom=?,slug=?,description=?,histoire=?,latitude=?,longitude=?,adresse=?,conseils_visite=?,informations_pratiques=?,source_reference=?,statut_publication=?,published_at=IF(?='publie',COALESCE(published_at,NOW()),NULL),is_demo=0 WHERE id=?";
            db()->prepare($sql)->execute([...$values,$destination['statut_publication'],$id]);
        } else {
            $sql = "INSERT INTO destinations(localite_id,categorie_id,nom,slug,description,histoire,latitude,longitude,adresse,conseils_visite,informations_pratiques,source_reference,statut_publication,published_at,is_demo) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,IF(?='publie',NOW(),NULL),0)";
            db()->prepare($sql)->execute([...$values,$destination['statut_publication']]);
            $id = (int)db()->lastInsertId();
        }
        admin_flash('Destination enregistrée.');
        header('Location: index.php?module=destinations');
        exit;
    }
}
$csrf = admin_csrf();
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= $id?'Modifier':'Créer' ?> une destination — Administration</title>
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
    .panel textarea { resize: vertical; line-height: 1.6; min-height: 90px; }

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

    /* Info box (règle de publication) */
    .info-box {
      background: #fffaf5; border: 1px solid #f5cba7; border-left: 4px solid var(--primary);
      color: #7e5109; border-radius: 10px; padding: 0.9rem 1.1rem;
      font-size: 0.85rem; line-height: 1.55; margin: 1.25rem 0;
    }

    /* Section title */
    .section-title {
      font-size: 0.72rem; font-weight: 700; text-transform: uppercase;
      letter-spacing: 1.2px; color: #8892a6;
      grid-column: 1 / -1; margin-top: 0.75rem; padding-bottom: 0.35rem;
      border-bottom: 1px solid #f0f2f5;
    }
    .section-title:first-of-type { margin-top: 0; }

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
  <a href="index.php?module=destinations">← Retour aux destinations</a>
</header>

<main class="content">
  <div class="content-inner">
    <h1><?= $id?'Modifier':'Créer' ?> une destination</h1>
    <p class="muted">Renseignez les informations vérifiées. Une URL de source HTTP(S) est obligatoire avant toute publication.</p>

    <?php if ($error!==''): ?>
      <div class="notice error"><?= admin_escape($error) ?></div>
    <?php endif; ?>

    <form class="panel" method="post">
      <input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>">

      <div class="editor-grid">
        <div class="section-title">Informations principales</div>

        <div>
          <label for="nom">Nom</label>
          <input id="nom" name="nom" maxlength="180" required value="<?= admin_escape($destination['nom']) ?>" placeholder="Ex : Parc national de Taï">
        </div>
        <div>
          <label for="slug">Slug</label>
          <input id="slug" name="slug" required value="<?= admin_escape($destination['slug']) ?>" placeholder="parc-national-de-tai">
          <div class="hint">Lettres minuscules, chiffres et tirets uniquement.</div>
        </div>

        <div>
          <label for="localite_id">Localité</label>
          <select id="localite_id" name="localite_id" required>
            <option value="">Choisir…</option>
            <?php foreach ($localities as $row): ?>
              <option value="<?= admin_escape($row['id']) ?>" <?= (string)$destination['localite_id']===(string)$row['id']?'selected':'' ?>>
                <?= admin_escape($row['label']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label for="categorie_id">Catégorie</label>
          <select id="categorie_id" name="categorie_id" required>
            <option value="">Choisir…</option>
            <?php foreach ($categories as $row): ?>
              <option value="<?= admin_escape($row['id']) ?>" <?= (string)$destination['categorie_id']===(string)$row['id']?'selected':'' ?>>
                <?= admin_escape($row['nom']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="wide">
          <label for="description">Description</label>
          <textarea id="description" name="description" rows="4" required placeholder="Décrivez la destination en quelques phrases…"><?= admin_escape($destination['description']) ?></textarea>
        </div>

        <div class="wide">
          <label for="histoire">Histoire <span class="text-muted" style="font-weight:400">(facultatif)</span></label>
          <textarea id="histoire" name="histoire" rows="3" placeholder="Contexte historique…"><?= admin_escape($destination['histoire']) ?></textarea>
        </div>

        <div class="section-title">Localisation</div>

        <div>
          <label for="latitude">Latitude vérifiée</label>
          <input id="latitude" name="latitude" inputmode="decimal" value="<?= admin_escape($destination['latitude']) ?>" placeholder="5.3364">
        </div>
        <div>
          <label for="longitude">Longitude vérifiée</label>
          <input id="longitude" name="longitude" inputmode="decimal" value="<?= admin_escape($destination['longitude']) ?>" placeholder="-4.0267">
        </div>

        <div class="wide">
          <label for="adresse">Adresse <span class="text-muted" style="font-weight:400">(facultatif)</span></label>
          <input id="adresse" name="adresse" maxlength="255" value="<?= admin_escape($destination['adresse']) ?>" placeholder="Rue, quartier, ville…">
        </div>

        <div class="section-title">Contenu éditorial vérifié</div>

        <div class="wide">
          <label for="conseils_visite">Conseils de visite</label>
          <textarea id="conseils_visite" name="conseils_visite" rows="3" placeholder="Période idéale, équipement, précautions…"><?= admin_escape($destination['conseils_visite']) ?></textarea>
        </div>
        <div class="wide">
          <label for="informations_pratiques">Informations pratiques</label>
          <textarea id="informations_pratiques" name="informations_pratiques" rows="3" placeholder="Horaires, tarifs, accès…"><?= admin_escape($destination['informations_pratiques']) ?></textarea>
        </div>

        <div class="section-title">Source et publication</div>

        <div class="wide">
          <label for="source_reference">URL de la source <span class="text-muted" style="font-weight:400">(obligatoire pour publier)</span></label>
          <input id="source_reference" name="source_reference" type="url" maxlength="500" value="<?= admin_escape($destination['source_reference']) ?>" placeholder="https://…">
        </div>

        <div>
          <label for="statut_publication">Publication</label>
          <select id="statut_publication" name="statut_publication">
            <?php foreach (['brouillon','publie','archive'] as $status): ?>
              <option value="<?= $status ?>" <?= $destination['statut_publication']===$status?'selected':'' ?>>
                <?= admin_escape(ucfirst($status)) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="info-box">
        ℹ️ <strong>Règle de publication :</strong> pour passer une destination en <em>Publié</em>, une URL de source HTTP(S) valide est obligatoire. Les coordonnées GPS doivent être fournies ensemble (latitude <em>et</em> longitude) ou laissées vides.
      </div>

      <div style="margin-top:1.25rem; display:flex; gap:0.75rem; flex-wrap:wrap;">
        <button class="button primary" type="submit">Enregistrer</button>
        <?php if ($id): ?>
          <a class="button" href="image.php?destination_id=<?= admin_escape($id) ?>">Gérer les images et licences</a>
        <?php endif; ?>
        <a class="button" href="index.php?module=destinations">Annuler</a>
      </div>
    </form>
  </div>
</main>
</body>
</html>