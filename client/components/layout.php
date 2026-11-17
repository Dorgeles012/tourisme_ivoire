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
    $isAuthPage = in_array($page, ['login', 'register', 'forgot', 'reset'], true);
    ?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title><?= client_h($title) ?> · Tourisme Ivoire</title>
    <link rel="stylesheet" href="assets.php?file=portal.css">
    <?php if ($isAuthPage): ?>
    <style>
        .auth-main {
            position: fixed;
            inset: 0;
            z-index: 1;
            overflow-x: hidden;
            overflow-y: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 4rem 1rem 1rem;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            background:
                linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)),
                url('https://images.unsplash.com/photo-1509316785289-025f5b846b35?q=80&w=1920&auto=format&fit=crop') no-repeat center center / cover;
            background-attachment: fixed;
        }
        .auth-main::-webkit-scrollbar {
            display: none;
        }
        @media (max-width: 520px) {
            .auth-main {
                padding: 2rem 0.75rem 1rem;
            }
        }
        .auth-main.auth-login-main {
            overflow: hidden;
            padding: 1rem;
            align-items: center;
        }
        .auth-login-main .auth-card {
            margin: 0;
            padding: 1.5rem 1.2rem 1rem;
        }
        .auth-login-main .auth-header {
            margin-bottom: 0.75rem;
        }
        .auth-login-main .form-grid {
            gap: 0.6rem;
        }
        .auth-login-main .auth-switch {
            margin-top: 0.75rem;
            padding-top: 0.65rem;
        }
        @media (max-height: 620px) {
            .auth-main.auth-login-main {
                padding: 0.5rem;
            }
            .auth-login-main .auth-card {
                padding: 1rem;
            }
            .auth-login-main .auth-header {
                margin-bottom: 0.5rem;
            }
            .auth-login-main .form-grid {
                gap: 0.45rem;
            }
            .auth-login-main .auth-switch {
                margin-top: 0.5rem;
                padding-top: 0.5rem;
            }
        }
    </style>
    <?php endif; ?>
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
    <main class="auth-main<?= $page === 'login' ? ' auth-login-main' : '' ?>">
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