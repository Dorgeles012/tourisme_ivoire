<?php
declare(strict_types=1);
$currentPage = basename((string)($_SERVER['SCRIPT_NAME'] ?? 'index.php'));
$currentModule = (string)($_GET['module'] ?? '');
$pageModule = [
    // Pages de création / modification : garder la rubrique parente active.
    'create_user.php' => 'users',
    'user_roles.php' => 'users',
    'destination.php' => 'destinations',
    'category.php' => 'categories',
    'heritage.php' => 'heritage',
    'virtual_visit.php' => 'virtual_visits',
    'image.php' => 'images',
    'quiz.php' => 'quizzes',
    // Pages admin accessibles directement depuis leur rubrique.
    'geography.php' => 'geography',
    'notification.php' => 'notifications',
];
$sidebarLinks = [
 ['label'=>'Tableau de bord','href'=>'index.php','page'=>'index.php','module'=>''],
 ['label'=>'Utilisateurs','href'=>'index.php?module=users','page'=>'index.php','module'=>'users'],
 ['label'=>'Destinations','href'=>'index.php?module=destinations','page'=>'index.php','module'=>'destinations'],
 ['label'=>'Catégories','href'=>'index.php?module=categories','page'=>'index.php','module'=>'categories'],
 ['label'=>'Géographie','href'=>'geography.php','page'=>'geography.php','module'=>''],
 ['label'=>'Patrimoines','href'=>'index.php?module=heritage','page'=>'index.php','module'=>'heritage'],
 ['label'=>'Visites virtuelles','href'=>'index.php?module=virtual_visits','page'=>'index.php','module'=>'virtual_visits'],
 ['label'=>'Images','href'=>'index.php?module=images','page'=>'index.php','module'=>'images'],
 ['label'=>'Quiz','href'=>'index.php?module=quizzes','page'=>'index.php','module'=>'quizzes'],
 ['label'=>'Réservations','href'=>'index.php?module=reservations','page'=>'index.php','module'=>'reservations'],
 ['label'=>'Notifications','href'=>'notification.php','page'=>'notification.php','module'=>''],
 ['label'=>'Rôles système','href'=>'roles.php','page'=>'roles.php','module'=>''],
];
?>
<style>
  /* ===== Sidebar moderne sans icônes — à inclure une seule fois ===== */
  :root {
    --sb-primary: #e67e22;
    --sb-primary-dark: #d35400;
    --sb-bg: #1a1d29;
    --sb-hover: #2a2e3f;
    --sb-text: #a0a6b8;
    --sb-text-light: #5a6078;
  }

  .sidebar {
    width: 250px;
    background: var(--sb-bg);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    padding: 1.5rem 0.85rem;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    position: sticky;
    top: 0;
    height: 100vh;
    overflow-y: auto;
    flex-shrink: 0;
    box-sizing: border-box;
  }
  .sidebar *, .sidebar *::before, .sidebar *::after { box-sizing: border-box; }
  .sidebar::-webkit-scrollbar { width: 4px; }
  .sidebar::-webkit-scrollbar-thumb { background: #3a3f54; border-radius: 4px; }
  .sidebar::-webkit-scrollbar-track { background: transparent; }

  /* Brand */
  .sidebar-brand {
    display: flex; align-items: center; gap: 0.75rem;
    padding: 0.5rem 0.75rem 1.5rem;
    text-decoration: none;
    border-bottom: 1px solid rgba(255,255,255,0.06);
    margin-bottom: 1.25rem;
  }
  .sidebar-logo {
    width: 40px; height: 40px; border-radius: 10px;
    background: linear-gradient(135deg, var(--sb-primary), var(--sb-primary-dark));
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 0.95rem; letter-spacing: 0.5px;
    box-shadow: 0 4px 12px rgba(230,126,34,0.35);
    flex-shrink: 0;
  }
  .sidebar-brand b {
    color: #fff; font-size: 0.8rem; font-weight: 700;
    letter-spacing: 1px; line-height: 1.3;
  }

  /* Caption */
  .sidebar-caption {
    color: var(--sb-text-light);
    font-size: 0.68rem; text-transform: uppercase;
    letter-spacing: 1.5px; font-weight: 600;
    padding: 0 0.85rem 0.6rem;
  }

  /* Nav */
  .sidebar nav { flex: 1; display: flex; flex-direction: column; gap: 2px; }
  .sidebar nav a {
    display: block;
    padding: 0.65rem 0.95rem;
    border-radius: 9px;
    color: var(--sb-text);
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.18s ease;
  }
  .sidebar nav a:hover {
    background: var(--sb-hover);
    color: #fff;
    padding-left: 1.1rem;
  }
  .sidebar nav a.active {
    background: linear-gradient(135deg, var(--sb-primary), var(--sb-primary-dark));
    color: #fff;
    font-weight: 600;
    box-shadow: 0 4px 14px rgba(230,126,34,0.35);
  }

  /* Logout */
  .sidebar-logout {
    margin-top: 1rem; padding-top: 1rem;
    border-top: 1px solid rgba(255,255,255,0.06);
  }
  .sidebar-logout button {
    display: block;
    width: 100%;
    padding: 0.65rem 0.95rem;
    border-radius: 9px;
    background: transparent;
    border: none;
    color: #e07a7a;
    font-size: 0.875rem;
    font-weight: 500;
    font-family: inherit;
    cursor: pointer;
    transition: all 0.18s ease;
    text-align: left;
  }
  .sidebar-logout button:hover {
    background: rgba(231,76,60,0.12);
    color: #ff6b6b;
    padding-left: 1.1rem;
  }

  /* Responsive */
  @media (max-width: 992px) {
    .sidebar {
      width: 100%; height: auto; min-height: auto;
      position: static; padding: 1rem;
      flex-direction: row; flex-wrap: wrap;
      align-items: center; gap: 0.35rem;
    }
    .sidebar-brand { border: none; margin: 0; padding: 0 0.5rem 0 0; }
    .sidebar-caption { display: none; }
    .sidebar nav { flex-direction: row; flex-wrap: wrap; flex: 1; gap: 0.35rem; }
    .sidebar nav a { padding: 0.5rem 0.75rem; font-size: 0.8rem; }
    .sidebar nav a:hover { padding-left: 0.75rem; }
    .sidebar-logout { margin: 0; padding: 0; border: none; }
    .sidebar-logout button {
      padding: 0.5rem 0.85rem; width: auto;
      border: 1px solid rgba(231,76,60,0.3);
    }
    .sidebar-logout button:hover { padding-left: 0.85rem; }
  }
</style>

<aside class="sidebar" aria-label="Navigation du back-office">
  <a class="sidebar-brand" href="index.php">
    <span class="sidebar-logo">TI</span>
    <b>TOURISME IVOIRE</b>
  </a>

  <div class="sidebar-caption">MENU PRINCIPAL</div>

  <nav>
    <?php foreach($sidebarLinks as $link):
      $active = false;
      if ($currentPage === 'index.php') {
        $active = $link['page'] === 'index.php' && $currentModule === $link['module'];
      } elseif ($currentPage === $link['page'] && $link['page'] !== 'index.php') {
        $active = true;
      } elseif (isset($pageModule[$currentPage])) {
        $active = $link['page'] === 'index.php' && $link['module'] === $pageModule[$currentPage];
      }
    ?>
      <a class="<?= $active ? 'active' : '' ?>" href="<?= admin_escape($link['href']) ?>">
        <?= admin_escape($link['label']) ?>
      </a>
    <?php endforeach; ?>
  </nav>

  <form class="sidebar-logout" method="post" action="logout.php">
    <input type="hidden" name="csrf" value="<?= admin_escape(admin_csrf()) ?>">
    <button type="submit">Déconnexion</button>
  </form>
</aside>