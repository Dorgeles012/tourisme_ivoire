<?php
declare(strict_types=1);
require_once dirname(__DIR__,2) . '/admin/bootstrap.php';
admin_require_module('notifications',true);
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
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Notification — Administration</title>
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
    .content-inner { max-width: 720px; margin: 0 auto; }
    .content h1 { font-size: 1.75rem; font-weight: 700; color: #1a1d29; margin-bottom: 0.25rem; }
    .muted { color: #8892a6; font-size: 0.9rem; margin-bottom: 1.5rem; }

    /* Panel / Form card */
    .panel {
      background: #fff; border-radius: 14px; padding: 2rem 2.25rem;
      border: 1px solid var(--border-color);
      box-shadow: 0 4px 16px rgba(0,0,0,0.04);
    }
    .panel label.field-label {
      display: block; font-size: 0.82rem; font-weight: 600;
      color: #34495e; margin-bottom: 0.35rem; margin-top: 1.1rem;
    }
    .panel label.field-label:first-of-type { margin-top: 0; }
    .panel input[type="text"], .panel input:not([type]), .panel select, .panel textarea {
      width: 100%; border: 1px solid var(--border-color); border-radius: 10px;
      padding: 0.65rem 0.95rem; font-size: 0.9rem; color: #1a1d29;
      background: #fafbfc; transition: all 0.2s; font-family: inherit;
    }
    .panel input:focus, .panel select:focus, .panel textarea:focus {
      outline: none; border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(230,126,34,0.15); background: #fff;
    }
    .panel textarea { resize: vertical; min-height: 130px; line-height: 1.6; }
    .panel select:disabled {
      background: #f0f2f5; color: #a0a6b8; cursor: not-allowed;
    }

    /* Checkbox stylisée */
    .checkbox-card {
      display: flex; align-items: flex-start; gap: 0.75rem;
      background: #fffaf5; border: 1px solid #f5cba7; border-radius: 10px;
      padding: 0.85rem 1rem; margin-top: 1rem; cursor: pointer;
      transition: all 0.2s;
    }
    .checkbox-card:hover { background: #fff5e6; border-color: var(--primary); }
    .checkbox-card input[type="checkbox"] {
      width: 18px; height: 18px; margin-top: 0.15rem;
      accent-color: var(--primary); cursor: pointer; flex-shrink: 0;
    }
    .checkbox-card .checkbox-text { font-size: 0.875rem; color: #7e5109; }
    .checkbox-card .checkbox-text strong { color: #5d3c07; }

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
  <a href="index.php?module=notifications">← Retour aux notifications</a>
</header>

<main class="content">
  <div class="content-inner">
    <h1>Créer une notification</h1>
    <p class="muted">Envoyez un message à un utilisateur précis ou à l'ensemble des comptes actifs.</p>

    <?php if ($error!==''): ?>
      <div class="notice error"><?= admin_escape($error) ?></div>
    <?php endif; ?>

    <form class="panel" method="post" id="notif-form">
      <input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>">

      <label class="field-label" for="titre">Titre</label>
      <input id="titre" name="titre" maxlength="180" required placeholder="Ex : Nouvelle destination disponible">

      <label class="field-label" for="contenu">Contenu</label>
      <textarea id="contenu" name="contenu" rows="5" required placeholder="Rédigez le message de la notification…"></textarea>

      <label class="field-label" for="type">Type</label>
      <select id="type" name="type">
        <option value="information">Information</option>
        <option value="reservation">Réservation</option>
        <option value="quiz">Quiz</option>
        <option value="communaute">Communauté</option>
      </select>

      <label class="field-label" for="utilisateur_id">Destinataire individuel</label>
      <select id="utilisateur_id" name="utilisateur_id">
        <option value="">— Choisir un utilisateur —</option>
        <?php foreach ($users as $row): ?>
          <option value="<?= admin_escape($row['id']) ?>">
            <?= admin_escape($row['nom']) ?> — <?= admin_escape($row['email']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <div class="hint">Ignoré si "Envoyer à tous" est coché.</div>

      <label class="checkbox-card" for="tous">
        <input type="checkbox" id="tous" name="tous" value="1">
        <span class="checkbox-text">
          <strong>Envoyer à tous les comptes actifs</strong><br>
          La notification sera envoyée à l'ensemble des utilisateurs actifs (le champ destinataire sera ignoré).
        </span>
      </label>

      <div style="margin-top:1.75rem; display:flex; gap:0.75rem;">
        <button class="button primary" type="submit">Enregistrer la notification</button>
        <a class="button" href="index.php?module=notifications">Annuler</a>
      </div>
    </form>
  </div>
</main>

<script>
  // Purement visuel : désactive le select individuel quand "tous" est coché
  (function () {
    const all = document.getElementById('tous');
    const select = document.getElementById('utilisateur_id');
    function sync() {
      if (all.checked) {
        select.value = '';
        select.disabled = true;
      } else {
        select.disabled = false;
      }
    }
    all.addEventListener('change', sync);
    sync();
  })();
</script>
</body>
</html>