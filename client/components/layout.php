<?php
declare(strict_types=1);

function client_layout(string $page, string $title, string $content, ?array $user = null): void {
    $flash = client_flash();
    $activePage = match ($page) {
        'profile_edit', 'profile_photo_setup' => 'profile',
        'destination' => 'explore',
        'video', 'video_studio' => 'videos',
        'offline_library' => 'settings',
        default => $page,
    };
    $settings = is_array($GLOBALS['client_settings'] ?? null) ? $GLOBALS['client_settings'] : [];
    $theme = in_array($settings['theme'] ?? '', ['systeme', 'clair', 'sombre'], true) ? $settings['theme'] : 'systeme';
    $accessibility = is_array($settings['accessibilite_json'] ?? null) ? $settings['accessibilite_json'] : [];
    $bodyClasses = ['portal', 'theme-' . $theme];
    if (!empty($accessibility['contraste_eleve'])) $bodyClasses[] = 'accessibility-high-contrast';
    if (!empty($accessibility['texte_agrandi'])) $bodyClasses[] = 'accessibility-large-text';

    $nav = [
        'dashboard'     => 'Accueil',
        'explore'       => 'Explorer',
        'map'           => 'Carte',
        'heritage'      => 'Patrimoine',
        'transport'     => 'Transport',
        'videos'        => 'Vidéos',
        'actualites'    => 'Actualités',
        'promotions'    => 'Promotions',
        'hebergements'  => 'Hébergements',
        'activites'     => 'Activités',
        'profile'       => 'Mon profil',
        'passport'      => 'Passeport',
        'reservations'  => 'Réservations',
        'favorites'     => 'Favoris',
        'notifications' => 'Notifications',
        'settings'      => 'Paramètres',
    ];
    $guestNav = [
        'dashboard' => 'Accueil',
        'explore' => 'Explorer',
        'videos' => 'Vidéos',
        'actualites' => 'Actualités',
        'promotions' => 'Promotions',
        'hebergements' => 'Hébergements',
        'activites' => 'Activités',
        'profile' => 'Mon profil',
    ];

    $isAuthPage = in_array($page, ['login', 'register', 'forgot', 'reset'], true);
    ?>
<!doctype html>
<html lang="fr" class="<?= !empty($accessibility['texte_agrandi']) ? 'accessibility-large-text' : '' ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title><?= client_h($title) ?> · Tourisme Ivoire</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php $clientAssetBase = rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/client/index.php'))), '/'); ?>
    <link rel="stylesheet" href="<?= client_h($clientAssetBase . '/assets.php?file=portal.css') ?>">
    <link rel="stylesheet" href="<?= client_h($clientAssetBase . '/assets.php?file=alerts.css') ?>">

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
            grid-template-columns: 240px minmax(0, 1fr);
            min-height: 100vh;
            padding-top: 68px;
        }

        .topbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 20;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            min-height: 68px;
            padding: 0.75rem clamp(1rem, 3vw, 2.5rem);
            background: var(--c-surface);
            border-bottom: 1px solid var(--c-border);
        }

        .topbar-brand,
        .topbar-user,
        .topbar-actions {
            display: flex;
            align-items: center;
        }

        .topbar-brand {
            gap: 0.7rem;
            color: var(--c-text);
            text-decoration: none;
        }

        .topbar-brand-copy {
            display: grid;
            gap: 0.1rem;
        }

        .topbar-brand-copy strong {
            font-size: 0.95rem;
        }

        .topbar-brand-copy small {
            color: var(--c-muted);
            font-size: 0.72rem;
        }

        .topbar-brand-mark,
        .topbar-avatar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: linear-gradient(135deg, #fb923c, #ea580c);
            color: #fff;
            font-size: 0.8rem;
            font-weight: 800;
        }

        .topbar-actions {
            gap: 0.7rem;
        }

        .topbar-link,
        .topbar-notification,
        .topbar-user {
            gap: 0.55rem;
            color: var(--c-muted);
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration: none;
        }

        .topbar-link {
            padding: 0.55rem 0.7rem;
            border-radius: 9px;
        }

        .topbar-link:hover,
        .topbar-notification:hover,
        .topbar-user:hover {
            color: var(--c-primary-hover);
        }

        .topbar-link:hover,
        .topbar-notification:hover {
            background: var(--c-primary-soft);
        }

        .topbar-notification {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }

        .topbar-notification svg {
            width: 20px;
            height: 20px;
            fill: none;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 1.8;
        }

        .topbar-user-label {
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .topbar-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            font-size: 0.75rem;
        }

        .topbar-button {
            padding: 0.6rem 0.85rem;
            border-radius: 9px;
            background: var(--c-primary);
            color: #fff;
            font-size: 0.82rem;
            font-weight: 700;
            text-decoration: none;
        }

        .topbar-button:hover {
            background: var(--c-primary-hover);
        }

        .sidebar {
            position: fixed;
            top: 68px;
            bottom: 0;
            left: 0;
            z-index: 19;
            width: 240px;
            height: auto;
            overflow-y: auto;
            background: var(--c-surface);
            border-right: 1px solid var(--c-border);
            padding: 1.5rem 1rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            scrollbar-width: thin;
            scrollbar-color: #d3b39c transparent;
        }

        .sidebar::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: #d3b39c;
            border-radius: 4px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
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
            grid-column: 2;
            margin: 0 auto;
            min-width: 0;
            padding: 0.5rem 2rem;
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

        .auth-login-main {
            overflow-x: hidden;
            overflow-y: auto;
            padding: 1rem;
        }

        .auth-login-main .auth-card {
            margin: 0;
        }

        @media (max-width: 900px) {
            .app-shell {
                grid-template-columns: 1fr;
                padding-top: 60px;
            }

            .sidebar {
                position: static;
                grid-column: 1;
                width: auto;
                height: auto;
                overflow: visible;
                border-right: 0;
                border-bottom: 1px solid var(--c-border);
                padding: 0.65rem;
            }

            .topbar {
                min-height: 60px;
                padding: 0.65rem 1rem;
            }

            .sidebar nav {
                flex-direction: row;
                overflow-x: auto;
            }

            .nav-link { white-space: nowrap; }
            .side-label,
            .sidebar-note { display: none; }

            .main-content {
                grid-column: 1;
                padding: 0.35rem 1rem;
            }
        }

        @media (max-width: 520px) {
            .page-heading h1 {
                font-size: 1.35rem;
            }

            .topbar-brand-copy small {
                display: none;
            }

            .topbar-user-label {
                max-width: 90px;
            }

            .topbar-actions {
                gap: 0.25rem;
            }

            .topbar-link {
                padding: 0.5rem;
            }

            .topbar-notification {
                width: 36px;
                height: 36px;
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

        html.accessibility-large-text { font-size: 118%; }

        body.portal.theme-dark {
            --c-bg: #171310;
            --c-surface: #241e19;
            --c-border: #4a3c32;
            --c-text: #fff6ef;
            --c-muted: #c9b9ae;
            --c-primary-soft: rgba(249, 115, 22, .18);
            color-scheme: dark;
        }

        @media (prefers-color-scheme: dark) {
            body.portal.theme-system {
                --c-bg: #171310;
                --c-surface: #241e19;
                --c-border: #4a3c32;
                --c-text: #fff6ef;
                --c-muted: #c9b9ae;
                --c-primary-soft: rgba(249, 115, 22, .18);
                color-scheme: dark;
            }
        }

        body.portal.theme-dark .sidebar,
        body.portal.theme-system .sidebar,
        body.portal.theme-dark .panel,
        body.portal.theme-system .panel,
        body.portal.theme-dark .feature-card,
        body.portal.theme-system .feature-card,
        body.portal.theme-dark .stat-card,
        body.portal.theme-system .stat-card,
        body.portal.theme-dark .explore-card,
        body.portal.theme-system .explore-card,
        body.portal.theme-dark .heritage-card,
        body.portal.theme-system .heritage-card,
        body.portal.theme-dark .virtual-panel,
        body.portal.theme-system .virtual-panel,
        body.portal.theme-dark .notif-card,
        body.portal.theme-system .notif-card,
        body.portal.theme-dark .visits-panel,
        body.portal.theme-system .visits-panel,
        body.portal.theme-dark .dest-detail,
        body.portal.theme-system .dest-detail,
        body.portal.theme-dark .fav-card,
        body.portal.theme-system .fav-card,
        body.portal.theme-dark .res-card,
        body.portal.theme-system .res-card,
        body.portal.theme-dark .badge-card,
        body.portal.theme-system .badge-card {
            background-color: var(--c-surface) !important;
            color: var(--c-text) !important;
            border-color: var(--c-border) !important;
        }

        body.portal.theme-dark .panel p,
        body.portal.theme-system .panel p,
        body.portal.theme-dark .panel h2,
        body.portal.theme-system .panel h2,
        body.portal.theme-dark .panel h3,
        body.portal.theme-system .panel h3,
        body.portal.theme-dark .row-meta,
        body.portal.theme-system .row-meta,
        body.portal.theme-dark .notif-content,
        body.portal.theme-system .notif-content,
        body.portal.theme-dark .dest-content,
        body.portal.theme-system .dest-content {
            color: var(--c-muted) !important;
        }

        body.portal.theme-dark input,
        body.portal.theme-dark select,
        body.portal.theme-dark textarea,
        body.portal.theme-system input,
        body.portal.theme-system select,
        body.portal.theme-system textarea {
            background: var(--c-surface);
            color: var(--c-text);
            border-color: var(--c-border);
        }

        body.portal.theme-dark .list-row,
        body.portal.theme-system .list-row,
        body.portal.theme-dark .visit-row,
        body.portal.theme-system .visit-row,
        body.portal.theme-dark .virtual-row,
        body.portal.theme-system .virtual-row {
            background: var(--c-bg);
            color: var(--c-text);
            border-color: var(--c-border);
        }

        body.portal.accessibility-high-contrast {
            --c-border: #46362b;
            --c-muted: #49362b;
            --c-primary-hover: #9a3500;
        }

        body.portal.theme-dark.accessibility-high-contrast {
            --c-border: #fff;
            --c-muted: #fff;
            --c-primary: #ffb070;
            --c-primary-hover: #ffd1ad;
            --c-text: #fff;
        }

        @media (prefers-color-scheme: dark) {
            body.portal.theme-system.accessibility-high-contrast {
                --c-border: #fff;
                --c-muted: #fff;
                --c-primary: #ffb070;
                --c-primary-hover: #ffd1ad;
                --c-text: #fff;
            }
        }

        body.portal.accessibility-high-contrast :focus-visible {
            outline: 3px solid currentColor;
            outline-offset: 2px;
        }
    </style>

    <script defer src="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.0/dist/sweetalert2.all.min.js"></script>
    <script defer src="<?= client_h($clientAssetBase . '/assets.php?file=alerts.js') ?>"></script>
    <script defer src="<?= client_h($clientAssetBase . '/assets.php?file=portal.js') ?>"></script>
</head>
<body class="<?= client_h(implode(' ', $bodyClasses)) ?>" data-content-sync="<?= $isAuthPage ? 'off' : 'on' ?>" data-notification-sync="<?= $user !== null ? 'on' : 'off' ?>">

    <?php if (!$isAuthPage): ?>
    <div class="app-shell">

        <?php
        $topbarName = trim((string)($user['prenom'] ?? '') . ' ' . (string)($user['nom'] ?? ''));
        if ($topbarName === '') $topbarName = (string)($user['email'] ?? 'Mon compte');
        $topbarInitial = mb_strtoupper(mb_substr($topbarName, 0, 1));
        ?>
        <header class="topbar">
            <a class="topbar-brand" href="?page=dashboard">
                <span class="topbar-brand-mark" aria-hidden="true">TI</span>
                <span class="topbar-brand-copy">
                    <strong>Tourisme Ivoire</strong>
                    <small>Espace client</small>
                </span>
            </a>
            <div class="topbar-actions">
                <?php if ($user): ?>
                    <a class="topbar-notification" href="?page=notifications" aria-label="Notifications">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path>
                            <path d="M10 21h4"></path>
                        </svg>
                    </a>
                    <a class="topbar-user" href="?page=profile">
                        <span class="topbar-avatar" aria-hidden="true"><?= client_h($topbarInitial) ?></span>
                        <span class="topbar-user-label"><?= client_h($topbarName) ?></span>
                    </a>
                <?php else: ?>
                    <a class="topbar-link" href="?page=login">Connexion</a>
                    <a class="topbar-button" href="?page=register">Créer un compte</a>
                <?php endif; ?>
            </div>
        </header>

        <aside class="sidebar" id="portal-nav">
            <div class="side-label"><?= $user ? 'Espace voyageur' : 'Tourisme Ivoire' ?></div>

            <nav>
                <?php foreach ($user ? $nav : $guestNav as $key => $label): ?>
                    <a class="nav-link <?= $activePage === $key ? 'active' : '' ?>" href="?page=<?= client_h($key) ?>">
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

            <?php if ($user): ?>
                <form method="post" action="?page=dashboard" class="logout-form">
                    <?= client_csrf_field() ?>
                    <input type="hidden" name="action" value="logout">
                    <button class="btn-logout" type="submit">Déconnexion</button>
                </form>
            <?php else: ?>
                <a class="nav-link" href="?page=login">Connexion</a>
                <a class="nav-link" href="?page=register">Créer un compte</a>
            <?php endif; ?>
        </aside>

        <main class="main-content">
            <div class="page-heading">
                <div class="eyebrow">Tourisme Ivoire · Espace client</div>
                <h1><?= client_h($title) ?></h1>
            </div>

            <?php if ($flash): ?>
                <div hidden data-client-swal-message data-client-swal-type="<?= client_h($flash['type'] ?? 'info') ?>"><?= client_h($flash['message'] ?? '') ?></div>
            <?php endif; ?>

            <?= $content ?>
        </main>
    </div>

    <?php else: ?>
    <main class="auth-main<?= $page === 'login' ? ' auth-login-main' : '' ?>">
        <?php if ($flash): ?>
            <div hidden data-client-swal-message data-client-swal-type="<?= client_h($flash['type'] ?? 'info') ?>"><?= client_h($flash['message'] ?? '') ?></div>
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
