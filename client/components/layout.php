<?php
declare(strict_types=1);

function client_layout(string $page, string $title, string $content, ?array $user = null): void {
    $flash = client_flash();

    $nav = [
        'dashboard'     => 'Accueil',
        'explore'       => 'Explorer',
        'map'           => 'Carte',
        'heritage'      => 'Patrimoine',
        'transport'     => 'Transport',
        'quizzes'       => 'Quiz culturels',
        'quiz_results'  => 'Résultats des quiz',
        'videos'        => 'Vidéos',
        'profile'       => 'Mon profil',
        'passport'      => 'Passeport',
        'reservations'  => 'Réservations',
        'favorites'     => 'Favoris',
        'history'       => 'Historique',
        'payments'      => 'Paiements',
        'notifications' => 'Notifications',
        'messages'      => 'Assistance',
        'settings'      => 'Paramètres',
        'help'          => 'Aide & FAQ',
    ];

    $isAuthPage = in_array($page, ['login', 'register', 'forgot', 'reset'], true);
    ?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title><?= client_h($title) ?> · Tourisme Ivoire</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets.php?file=portal.css">

    <style>
        :root {
            --c-primary: #f97316;
            --c-primary-hover: #ea580c;
            --c-primary-soft: rgba(249, 115, 22, 0.10);
            --c-bg: #faf6f2;
            --c-surface: #ffffff;
            --c-border: #f1e4d8;
            --c-text: #1c1410;
            --c-muted: #8a7266;
            --c-radius: 12px;
            --c-shadow: 0 4px 18px -6px rgba(234, 88, 12, 0.12);
            --c-font: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        body.portal {
            font-family: var(--c-font);
            background: var(--c-bg);
            color: var(--c-text);
            margin: 0;
            min-height: 100vh;
        }

        .app-shell {
            display: grid;
            grid-template-columns: 240px 1fr;
            min-height: 100vh;
        }

        .sidebar {
            background: var(--c-surface);
            border-right: 1px solid var(--c-border);
            padding: 1.5rem 1rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .side-label {
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            color: var(--c-muted);
            text-transform: uppercase;
            padding: 0 0.5rem;
        }

        .sidebar nav {
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.6rem 0.75rem;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--c-muted);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.2s ease;
        }

        .nav-link:hover {
            background: var(--c-primary-soft);
            color: var(--c-primary-hover);
        }

        .nav-link.active {
            background: linear-gradient(135deg, #fb923c, #ea580c);
            color: #fff;
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.35);
        }

        .nav-count {
            margin-left: auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            background: var(--c-primary);
            color: #fff;
            font-size: 0.65rem;
            font-weight: 700;
            border-radius: 999px;
        }

        .nav-link.active .nav-count {
            background: rgba(255, 255, 255, 0.3);
        }

        .sidebar-note {
            margin-top: auto;
            padding: 0.85rem;
            background: var(--c-primary-soft);
            border-radius: 10px;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            font-size: 0.75rem;
            color: var(--c-muted);
            line-height: 1.4;
        }

        .sidebar-note span {
            color: var(--c-primary);
            font-size: 1rem;
        }

        .sidebar-note p { margin: 0; }

        .logout-form {
            margin-top: 0.5rem;
        }

        .btn-logout {
            width: 100%;
            padding: 0.6rem 0.75rem;
            font-size: 0.78rem;
            font-weight: 600;
            font-family: inherit;
            color: var(--c-muted);
            background: transparent;
            border: 1px solid var(--c-border);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-logout:hover {
            color: var(--c-primary-hover);
            border-color: var(--c-primary);
            background: var(--c-primary-soft);
        }

        .main-content {
            padding: 1.75rem 2rem;
            max-width: 1400px;
            width: 100%;
        }

        .page-heading {
            margin-bottom: 1.5rem;
        }

        .eyebrow {
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            color: var(--c-primary);
            text-transform: uppercase;
            margin-bottom: 0.35rem;
        }

        .page-heading h1 {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--c-text);
            margin: 0;
            letter-spacing: -0.02em;
        }

        .alert {
            padding: 0.85rem 1rem;
            border-radius: 10px;
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
            border-left: 4px solid var(--c-primary);
            background: var(--c-primary-soft);
            color: var(--c-primary-hover);
        }

        .alert-success {
            border-color: #10b981;
            background: #ecfdf5;
            color: #047857;
        }

        .alert-error {
            border-color: #ef4444;
            background: #fef2f2;
            color: #b91c1c;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.2rem 0.55rem;
            font-size: 0.65rem;
            font-weight: 700;
            border-radius: 999px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            white-space: nowrap;
        }

        .badge.good {
            background: #ecfdf5;
            color: #047857;
        }

        .badge.pending {
            background: var(--c-primary-soft);
            color: var(--c-primary-hover);
        }

        .badge.muted {
            background: #f3f4f6;
            color: #6b7280;
        }

        .auth-main {
            position: fixed;
            inset: 0;
            z-index: 1;
            overflow-x: hidden;
            overflow-y: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
            font-family: var(--c-font);
            margin: 0;
            padding: 2rem 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background:
                linear-gradient(rgba(0, 0, 0, 0.45), rgba(0, 0, 0, 0.45)),
                url('https://images.unsplash.com/photo-1509316785289-025f5b846b35?q=80&w=1920&auto=format&fit=crop') no-repeat center center / cover;
            background-attachment: fixed;
        }

        .auth-main::-webkit-scrollbar { display: none; }

        .auth-login-main {
            overflow: hidden;
            padding: 1rem;
        }

        .auth-login-main .auth-card {
            margin: 0;
        }

        @media (max-width: 900px) {
            .app-shell {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: fixed;
                inset: 0 auto 0 0;
                width: 260px;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                z-index: 100;
                box-shadow: 4px 0 24px rgba(0, 0, 0, 0.1);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-content {
                padding: 1.25rem 1rem;
            }
        }

        @media (max-width: 520px) {
            .page-heading h1 {
                font-size: 1.35rem;
            }

            .auth-main {
                padding: 1rem 0.75rem;
            }
        }

        @media (max-height: 620px) {
            .auth-login-main {
                padding: 0.5rem;
            }
        }
    </style>

    <script defer src="assets.php?file=portal.js"></script>
</head>
<body class="portal">

    <?php if ($user): ?>
    <div class="app-shell">

        <aside class="sidebar" id="portal-nav">
            <div class="side-label">Espace voyageur</div>

            <nav>
                <?php foreach ($nav as $key => $label): ?>
                    <a class="nav-link <?= $page === $key ? 'active' : '' ?>" href="?page=<?= client_h($key) ?>">
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

            <form method="post" action="?page=dashboard" class="logout-form">
                <?= client_csrf_field() ?>
                <input type="hidden" name="action" value="logout">
                <button class="btn-logout" type="submit">Déconnexion</button>
            </form>
        </aside>

        <main class="main-content">
            <div class="page-heading">
                <div class="eyebrow">Tourisme Ivoire · Espace client</div>
                <h1><?= client_h($title) ?></h1>
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
