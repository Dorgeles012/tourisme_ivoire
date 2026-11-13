<?php
declare(strict_types=1);
$currentPage = basename((string)($_SERVER['SCRIPT_NAME'] ?? 'index.php'));
$currentModule = (string)($_GET['module'] ?? '');
$sidebarLinks = [
 ['label'=>'Tableau de bord','icon'=>'▦','href'=>'index.php','page'=>'index.php','module'=>''],
 ['label'=>'Utilisateurs','icon'=>'♙','href'=>'index.php?module=users','page'=>'index.php','module'=>'users'],
 ['label'=>'Destinations','icon'=>'⌖','href'=>'index.php?module=destinations','page'=>'index.php','module'=>'destinations'],
 ['label'=>'Catégories','icon'=>'◇','href'=>'index.php?module=categories','page'=>'index.php','module'=>'categories'],
 ['label'=>'Géographie','icon'=>'⌂','href'=>'geography.php','page'=>'geography.php','module'=>''],
 ['label'=>'Patrimoines','icon'=>'▤','href'=>'index.php?module=heritage','page'=>'index.php','module'=>'heritage'],
 ['label'=>'Visites virtuelles','icon'=>'◉','href'=>'index.php?module=virtual_visits','page'=>'index.php','module'=>'virtual_visits'],
 ['label'=>'Images','icon'=>'▧','href'=>'index.php?module=images','page'=>'index.php','module'=>'images'],
 ['label'=>'Quiz','icon'=>'?','href'=>'index.php?module=quizzes','page'=>'index.php','module'=>'quizzes'],
 ['label'=>'Réservations','icon'=>'▣','href'=>'index.php?module=reservations','page'=>'index.php','module'=>'reservations'],
 ['label'=>'Notifications','icon'=>'♧','href'=>'notification.php','page'=>'notification.php','module'=>''],
 ['label'=>'Rôles système','icon'=>'⚙','href'=>'roles.php','page'=>'roles.php','module'=>''],
];
?>
<aside class="sidebar" aria-label="Navigation du back-office">
 <a class="sidebar-brand" href="index.php"><span class="sidebar-logo">TI</span><b>TOURISME IVOIRE</b></a>
 <div class="sidebar-caption">MENU PRINCIPAL</div>
 <nav>
 <?php foreach($sidebarLinks as $link): $active=$currentPage===$link['page']&&($link['page']!=='index.php'||$currentModule===$link['module']); ?>
 <a class="<?= $active?'active':'' ?>" href="<?= admin_escape($link['href']) ?>"><span class="sidebar-icon" aria-hidden="true"><?= admin_escape($link['icon']) ?></span><span><?= admin_escape($link['label']) ?></span></a>
 <?php endforeach; ?>
 </nav>
 <form class="sidebar-logout" method="post" action="logout.php"><input type="hidden" name="csrf" value="<?= admin_escape(admin_csrf()) ?>"><button type="submit"><span class="sidebar-icon" aria-hidden="true">↪</span><span>Déconnexion</span></button></form>
</aside>
