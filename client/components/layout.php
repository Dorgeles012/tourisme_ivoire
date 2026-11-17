<?php
declare(strict_types=1);

function client_layout(string $page, string $title, string $content, ?array $user = null): void {
    $flash = client_flash();
    $nav = [
        'dashboard' => ['Accueil', '⌂'], 'profile' => ['Mon profil', '◉'],
        'reservations' => ['Réservations', '▤'], 'favorites' => ['Favoris', '♡'],
        'history' => ['Historique', '◷'], 'payments' => ['Paiements', '₣'],
        'notifications' => ['Notifications', '♧'], 'messages' => ['Assistance', '✉'],
        'settings' => ['Paramètres', '⚙'], 'help' => ['Aide & FAQ', '?'],
    ];
    ?><!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="color-scheme" content="light dark"><title><?= client_h($title) ?> · Tourisme Ivoire</title><link rel="stylesheet" href="assets.php?file=portal.css"><script defer src="assets.php?file=portal.js"></script></head><body class="portal">
    <header class="topbar"><a class="brand" href="?page=dashboard"><span class="brand-mark">TI</span><span>Tourisme <b>Ivoire</b></span></a><button class="menu-toggle" type="button" aria-label="Ouvrir le menu" aria-expanded="false">☰</button><div class="top-actions"><?php if ($user): ?><span class="welcome-user">Bonjour, <?= client_h(($user['prenom'] ?? '') ?: ($user['nom'] ?? 'voyageur')) ?></span><button class="theme-toggle" type="button" aria-label="Changer le thème">◐</button><form method="post" action="?page=dashboard" class="logout-form"><?= client_csrf_field() ?><input type="hidden" name="action" value="logout"><button class="btn btn-quiet" type="submit">Déconnexion</button></form><?php endif; ?></div></header>
    <?php if ($user): ?><div class="app-shell"><aside class="sidebar" id="portal-nav"><div class="side-label">ESPACE VOYAGEUR</div><nav><?php foreach ($nav as $key => [$label,$icon]): ?><a class="nav-link <?= $page === $key ? 'active' : '' ?>" href="?page=<?= client_h($key) ?>"><span class="nav-icon"><?= client_h($icon) ?></span><?= client_h($label) ?><?php if ($key === 'notifications' && !empty($GLOBALS['unread_count'])): ?><span class="nav-count"><?= (int)$GLOBALS['unread_count'] ?></span><?php endif; ?></a><?php endforeach; ?></nav><div class="sidebar-note"><span>✦</span><p>Explorez la Côte d’Ivoire à votre rythme.</p></div></aside><main class="main-content"><div class="page-heading"><div><div class="eyebrow">TOURISME IVOIRE · ESPACE CLIENT</div><h1><?= client_h($title) ?></h1></div></div><?php if ($flash): ?><div class="alert alert-<?= client_h($flash['type'] ?? 'info') ?>" role="status"><?= client_h($flash['message'] ?? '') ?></div><?php endif; ?><?= $content ?></main></div><?php else: ?><main class="auth-main"><?php if ($flash): ?><div class="alert alert-<?= client_h($flash['type'] ?? 'info') ?>" role="status"><?= client_h($flash['message'] ?? '') ?></div><?php endif; ?><?= $content ?></main><?php endif; ?><footer class="footer">© <?= date('Y') ?> Tourisme Ivoire <span>Fait avec ♥ en Côte d’Ivoire</span></footer></body></html><?php
}

function client_status_badge(string $status): string {
    $labels = ['demande'=>'En attente','confirmee'=>'Confirmée','annulee'=>'Annulée','terminee'=>'Terminée','ouvert'=>'Ouvert','en_cours'=>'En cours','resolu'=>'Résolu'];
    $class = in_array($status, ['confirmee','terminee','resolu'], true) ? 'good' : (in_array($status, ['annulee'], true) ? 'muted' : 'pending');
    return '<span class="badge ' . $class . '">' . client_h($labels[$status] ?? ucfirst(str_replace('_',' ',$status))) . '</span>';
}
