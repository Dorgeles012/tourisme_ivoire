<?php
declare(strict_types=1);

/**
 * Layout principal du portail client.
 */
function client_layout(string $page, string $title, string $content, ?array $user = null): void {
    $flash = client_flash();

    // ─── Navigation ────────────────────────────────
    $nav = [
        'dashboard'     => ['Accueil', '⌂'],
        'profile'       => ['Mon profil', '◉'],
        'reservations'  => ['Réservations', '▤'],
        'favorites'     => ['Favoris', '♡'],
        'history'       => ['Historique', '◷'],
        'payments'      => ['Paiements', '₣'],
        'notifications' => ['Notifications', '♧'],
        'messages'      => ['Assistance', '✉'],
        'settings'      => ['Paramètres', '⚙'],
        'help'          => ['Aide & FAQ', '?'],
    ];

    $displayName = ($user['prenom'] ?? '') ?: ($user['nom'] ?? 'voyageur');
    ?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title><?= client_h($title) ?> · Tourisme Ivoire</title>
    <link rel="stylesheet" href="assets.php?file=portal.css">
    <script defer src="assets.php?file=portal.js"></script>
</head>
<body class="portal">

    <!-- ─── Barre supérieure ─────────────────────── -->
    <header class="topbar">
        <div class="top-actions">
            <?php if ($user): ?>
                <span class="welcome-user">Bonjour, <?= client_h($displayName) ?></span>
                <button class="theme-toggle" type="button" aria-label="Changer le thème">◐</button>
                <form method="post" action="?page=dashboard" class="logout-form">
                    <?= client_csrf_field() ?>
                    <input type="hidden" name="action" value="logout">
                    <button class="btn btn-quiet" type="submit">Déconnexion</button>
                </form>
            <?php endif; ?>
        </div>
    </header>

    <?php if ($user): ?>
    <!-- ─── Espace connecté ──────────────────────── -->
    <div class="app-shell">

        <!-- Sidebar -->
        <aside class="sidebar" id="portal-nav">
            <div class="side-label">ESPACE VOYAGEUR</div>

            <nav>
                <?php foreach ($nav as $key => [$label, $icon]): ?>
                    <a class="nav-link <?= $page === $key ? 'active' : '' ?>" href="?page=<?= client_h($key) ?>">
                        <span class="nav-icon"><?= client_h($icon) ?></span>
                        <?= client_h($label) ?>
                        <?php if ($key === 'notifications' && !empty($GLOBALS['unread_count'])): ?>
                            <span class="nav-count"><?= (int)$GLOBALS['unread_count'] ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="sidebar-note">
                <span>✦</span>
                <p>Explorez la Côte d’Ivoire à votre rythme.</p>
            </div>
        </aside>

        <!-- Contenu principal -->
        <main class="main-content">
            <div class="page-heading">
                <div>
                    <div class="eyebrow">TOURISME IVOIRE · ESPACE CLIENT</div>
                    <h1><?= client_h($title) ?></h1>
                </div>
            </div>

            <?php if ($flash): ?>
                <div class="alert alert-<?= client_h($flash['type'] ?? 'info') ?>" role="status">
                    <?= client_h($flash['message'] ?? '') ?>
                </div>
            <?php endif; ?>

            <?= $content ?>
        </main>
    </div>

    <?php else: ?>
    <!-- ─── Espace public (non connecté) ─────────── -->
    <main class="auth-main">
        <?php if ($flash): ?>
            <div class="alert alert-<?= client_h($flash['type'] ?? 'info') ?>" role="status">
                <?= client_h($flash['message'] ?? '') ?>
            </div>
        <?php endif; ?>

        <?= $content ?>
    </main>
    <?php endif; ?>

</body>
</html>
<?php
}

/**
 * Retourne un badge HTML coloré selon le statut.
 */
function client_status_badge(string $status): string {
    $labels = [
        'demande'    => 'En attente',
        'confirmee'  => 'Confirmée',
        'annulee'    => 'Annulée',
        'terminee'   => 'Terminée',
        'ouvert'     => 'Ouvert',
        'en_cours'   => 'En cours',
        'resolu'     => 'Résolu',
    ];

    $goodStatuses = ['confirmee', 'terminee', 'resolu'];
    $mutedStatuses = ['annulee'];

    if (in_array($status, $goodStatuses, true)) {
        $class = 'good';
    } elseif (in_array($status, $mutedStatuses, true)) {
        $class = 'muted';
    } else {
        $class = 'pending';
    }

    $label = $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));

    return '<span class="badge ' . $class . '">' . client_h($label) . '</span>';
}