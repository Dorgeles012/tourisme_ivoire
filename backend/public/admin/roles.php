<?php
declare(strict_types=1);
require_once dirname(__DIR__,2) . '/admin/bootstrap.php';
admin_require_role_management();
$roles = db()->query("SELECT code,nom,description FROM roles WHERE code IN ('Sadmin','Client') ORDER BY CASE code WHEN 'Sadmin' THEN 0 ELSE 1 END")->fetchAll();
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Rôles système — Administration</title>
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
      padding: calc(var(--topbar-height) + 2.5rem) 2.5rem 2rem;
      min-height: 100vh;
    }
    .content-inner { max-width: 860px; margin: 0 auto; }
    .eyebrow {
      font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1.5px;
      color: var(--primary); font-weight: 600; margin-bottom: 0.25rem;
    }
    .content h1 { font-size: 1.75rem; font-weight: 700; color: #1a1d29; margin-bottom: 0.25rem; }
    .muted { color: #8892a6; font-size: 0.9rem; margin-bottom: 1.75rem; }

    /* Grille des rôles */
    .roles-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
      gap: 1.25rem;
      margin-bottom: 1.75rem;
    }

    /* Carte rôle */
    .role-card {
      background: #fff; border-radius: 14px; padding: 1.75rem 1.75rem;
      border: 1px solid var(--border-color);
      box-shadow: 0 4px 16px rgba(0,0,0,0.04);
      position: relative; overflow: hidden;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .role-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.07); }
    .role-card::before {
      content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
    }
    .role-card.sadmin::before { background: linear-gradient(90deg, var(--primary), var(--primary-dark)); }
    .role-card.client::before { background: linear-gradient(90deg, #3498db, #2980b9); }

    .role-card .role-head {
      display: flex; align-items: center; gap: 0.85rem; margin-bottom: 1rem;
    }
    .role-card .role-icon {
      width: 44px; height: 44px; border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      font-weight: 700; font-size: 1.1rem; color: #fff; flex-shrink: 0;
    }
    .role-card.sadmin .role-icon {
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      box-shadow: 0 4px 12px rgba(230,126,34,0.3);
    }
    .role-card.client .role-icon {
      background: linear-gradient(135deg, #3498db, #2980b9);
      box-shadow: 0 4px 12px rgba(52,152,219,0.3);
    }
    .role-card .role-titles { flex: 1; }
    .role-card .role-nom {
      font-size: 1.05rem; font-weight: 700; color: #1a1d29; line-height: 1.2;
    }
    .role-card .role-code {
      font-family: 'JetBrains Mono', 'Courier New', monospace;
      font-size: 0.75rem; font-weight: 600;
      color: #8892a6; background: #f0f2f5;
      padding: 0.15rem 0.55rem; border-radius: 6px;
      display: inline-block; margin-top: 0.3rem;
    }

    .role-card .role-desc {
      font-size: 0.875rem; color: #4a5568; line-height: 1.55;
      margin-bottom: 1rem;
    }

    .role-card .role-access {
      display: flex; align-items: flex-start; gap: 0.55rem;
      font-size: 0.82rem; color: #6c7a89;
      background: #fafbfc; border-radius: 10px; padding: 0.75rem 0.9rem;
      border: 1px solid var(--border-color);
    }
    .role-card .role-access .access-icon { font-size: 0.95rem; line-height: 1.3; }

    /* Note explicative */
    .info-box {
      background: #fffaf5; border: 1px solid #f5cba7; border-left: 4px solid var(--primary);
      color: #7e5109; border-radius: 10px; padding: 0.9rem 1.1rem;
      font-size: 0.85rem; line-height: 1.55; margin-bottom: 1.5rem;
    }

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

    /* Responsive */
    @media (max-width: 992px) {
      .sidebar { position: static !important; height: auto !important; width: 100% !important; }
      .topbar { position: static; left: 0; height: auto; padding: 0.85rem 1rem; }
      .content { margin-left: 0; padding: 1.5rem 1rem; }
      .roles-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body class="admin-page">
<?php require __DIR__ . '/_sidebar.php'; ?>

<header class="topbar">
  <strong>TOURISME IVOIRE · ADMIN</strong>
  <a href="index.php">← Retour au tableau de bord</a>
</header>

<main class="content">
  <div class="content-inner">
    <p class="eyebrow">SÉCURITÉ</p>
    <h1>Rôles système</h1>
    <p class="muted">L’application utilise deux rôles fixes. Leur code est référencé par les comptes utilisateurs et ne peut pas être modifié ici.</p>

    <div class="roles-grid">
      <?php foreach ($roles as $role): ?>
        <?php $isSadmin = $role['code']==='Sadmin'; ?>
        <div class="role-card <?= $isSadmin ? 'sadmin' : 'client' ?>">
          <div class="role-head">
            <div class="role-icon"><?= $isSadmin ? '⚙' : '👤' ?></div>
            <div class="role-titles">
              <div class="role-nom"><?= admin_escape($role['nom']) ?></div>
              <code class="role-code"><?= admin_escape($role['code']) ?></code>
            </div>
          </div>

          <?php if (!empty($role['description'])): ?>
            <div class="role-desc"><?= admin_escape($role['description']) ?></div>
          <?php endif; ?>

          <div class="role-access">
            <span class="access-icon"><?= $isSadmin ? '🔐' : '🧭' ?></span>
            <span>
              <?= $isSadmin
                  ? 'Accès complet au back-office, à la modération et à l’administration des comptes.'
                  : 'Accès à l’espace voyageur, aux destinations, quiz et fonctionnalités client.' ?>
            </span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="info-box">
      ℹ️ <strong>Rôles immuables :</strong> les codes <code>Sadmin</code> et <code>Client</code> sont référencés directement dans la base et dans le code applicatif. Ils ne peuvent pas être renommés ni supprimés depuis cette interface.
    </div>

    <div style="display:flex; gap:0.75rem; flex-wrap:wrap;">
      <a class="button primary" href="index.php?module=users">Gérer les comptes utilisateurs</a>
      <a class="button" href="index.php">Retour au tableau de bord</a>
    </div>
  </div>
</main>
</body>
</html>