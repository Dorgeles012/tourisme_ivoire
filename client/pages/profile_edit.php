<?php
if (!$user) {
    header('Location: ?page=login');
    exit;
}

// ===== Chargement du profil =====
try {
    $profile = ClientApi::data($api->request('GET', 'api/auth/me'));
    $profile = is_array($profile) ? $profile : [];
} catch (Throwable $e) {
    echo '<section class="panel empty-panel"><div class="empty">'
       . '<div class="empty-icon">❌</div>'
       . '<h3>Erreur de chargement</h3>'
       . '<p data-client-alert data-client-alert-type="error">' . client_h(readableError($e)) . '</p>'
       . '<a class="btn-go" href="?page=profile">Retour</a>'
       . '</div></section>';
    return;
}

$flash      = null;
$avatarUrl  = '';
if (!empty($profile['avatar_url'])) {
    $raw = (string)$profile['avatar_url'];
    $avatarUrl = preg_match('#^https?://#i', $raw)
        ? $raw
        : rtrim((string)($api->baseUrl ?? ''), '/') . '/' . ltrim($raw, '/');
}

// ===== Traitement POST =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    client_csrf_verify();
    $action = (string)($_POST['action'] ?? '');

    // Upload avatar
    if ($action === 'upload_avatar' && !empty($_FILES['avatar']['tmp_name'])) {
        try {
            $api->request('POST', 'api/auth/avatar', [], [
                'avatar' => new CURLFile(
                    $_FILES['avatar']['tmp_name'],
                    $_FILES['avatar']['type'],
                    $_FILES['avatar']['name']
                ),
            ]);
            $flash = ['type' => 'success', 'text' => 'Photo de profil mise à jour.'];
            $profile = ClientApi::data($api->request('GET', 'api/auth/me'));
            $avatarUrl = !empty($profile['avatar_url']) ? (string)$profile['avatar_url'] : '';
        } catch (Throwable $e) {
            $flash = ['type' => 'error', 'text' => readableError($e)];
        }
    }

    // Mise à jour profil
    if ($action === 'update_profile') {
        try {
            $api->request('PATCH', 'api/auth/me', [
                'nom'       => trim((string)($_POST['nom'] ?? '')),
                'prenom'    => trim((string)($_POST['prenom'] ?? '')),
                'telephone' => trim((string)($_POST['telephone'] ?? '')),
            ]);
            $flash = ['type' => 'success', 'text' => 'Profil mis à jour avec succès.'];
            $profile = ClientApi::data($api->request('GET', 'api/auth/me'));
        } catch (Throwable $e) {
            $flash = ['type' => 'error', 'text' => readableError($e)];
        }
    }
}

$nom       = (string)($profile['nom'] ?? '');
$prenom    = (string)($profile['prenom'] ?? '');
$telephone = (string)($profile['telephone'] ?? '');
?>

<style>
  /* ===== Reset global (1 seule fois) ===== */
  html, body {
    overflow-x: hidden;
    max-width: 100vw;
  }
  *, *::before, *::after { box-sizing: border-box; }

  body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    font-size: 13px;
    line-height: 1.5;
    color: #1c1410;
  }

  /* ===== Header ===== */
  .page-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.85rem;
  }
  .back-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #f7f1eb;
    color: #5a4a40;
    text-decoration: none;
    font-size: 0.85rem;
    transition: background 0.15s ease, color 0.15s ease;
    flex-shrink: 0;
  }
  .back-link:hover { background: #fff8f3; color: #ea580c; }
  .page-title {
    font-size: 1.15rem;
    font-weight: 900;
    color: #1c1410;
    margin: 0;
    letter-spacing: -0.4px;
  }

  /* ===== Flash ===== */
  .flash {
    padding: 0.6rem 0.85rem;
    border-radius: 8px;
    font-size: 0.72rem;
    font-weight: 600;
    margin-bottom: 0.85rem;
  }
  .flash-success {
    background: rgba(46, 125, 50, 0.10);
    border: 1px solid rgba(46, 125, 50, 0.3);
    color: #2E7D32;
  }
  .flash-error {
    background: rgba(192, 57, 43, 0.08);
    border: 1px solid rgba(192, 57, 43, 0.25);
    color: #c0392b;
  }

  /* ===== Hero édition ===== */
  .edit-hero {
    display: flex;
    gap: 0.85rem;
    align-items: flex-start;
    padding: 1rem;
    background: linear-gradient(135deg, rgba(249, 115, 22, 0.12), rgba(249, 115, 22, 0.03));
    border: 1px solid rgba(249, 115, 22, 0.18);
    border-radius: 14px;
    margin-bottom: 1.15rem;
  }
  .edit-hero-avatar {
    flex-shrink: 0;
    width: 52px;
    height: 52px;
    border-radius: 50%;
    overflow: hidden;
    background: linear-gradient(135deg, #f97316, #c0392b);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 3px 10px -2px rgba(234, 88, 12, 0.25);
  }
  .edit-hero-avatar img { width: 100%; height: 100%; object-fit: cover; }
  .edit-hero-initials {
    color: #fff;
    font-size: 1.15rem;
    font-weight: 900;
  }
  .edit-hero-text { flex: 1; min-width: 0; }
  .edit-hero-title {
    font-size: 0.85rem;
    font-weight: 800;
    color: #1c1410;
    margin: 0 0 0.15rem;
  }
  .edit-hero-subtitle {
    font-size: 0.68rem;
    color: #8a7266;
    margin: 0 0 0.5rem;
    line-height: 1.35;
  }

  .edit-avatar-form { margin: 0; }
  .edit-avatar-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.35rem 0.65rem;
    font-size: 0.7rem;
    font-weight: 700;
    color: #ea580c;
    background: transparent;
    border: none;
    border-radius: 7px;
    cursor: pointer;
    transition: background 0.15s ease;
  }
  .edit-avatar-btn:hover { background: rgba(249, 115, 22, 0.10); }

  /* ===== Formulaire ===== */
  .edit-form {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
    max-width: 520px;
  }
  .edit-field { display: flex; flex-direction: column; gap: 0.3rem; }
  .edit-field label {
    font-size: 0.7rem;
    font-weight: 700;
    color: #4a3f38;
    letter-spacing: 0.02em;
  }
  .edit-input-wrap {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0 0.75rem;
    background: #f7f1eb;
    border: 1.5px solid transparent;
    border-radius: 10px;
    transition: background 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
  }
  .edit-input-wrap:focus-within {
    background: #fff;
    border-color: rgba(249, 115, 22, 0.8);
    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.10);
  }
  .edit-input-icon { font-size: 0.85rem; opacity: 0.7; }
  .edit-input-wrap input {
    flex: 1;
    padding: 0.7rem 0;
    font-family: inherit;
    font-size: 0.78rem;
    color: #1c1410;
    background: transparent;
    border: none;
    outline: none;
    min-width: 0;
  }
  .edit-input-wrap input::placeholder { color: #b8a598; }

  .edit-submit {
    margin-top: 0.75rem;
    padding: 0.75rem 0.9rem;
    font-family: inherit;
    font-size: 0.78rem;
    font-weight: 700;
    color: #fff;
    background: linear-gradient(135deg, #fb923c, #ea580c);
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    box-shadow: 0 4px 12px -3px rgba(234, 88, 12, 0.4);
  }
  .edit-submit:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px -3px rgba(234, 88, 12, 0.5);
  }

  /* ===== États vides ===== */
  .panel, .empty-panel {
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 12px;
    padding: 1.15rem 0.85rem;
    box-shadow: 0 2px 12px -4px rgba(234, 88, 12, 0.1);
  }
  .empty { text-align: center; padding: 0.85rem 0.6rem; }
  .empty-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    margin-bottom: 0.5rem;
    background: rgba(249, 115, 22, 0.10);
    color: #ea580c;
    border-radius: 50%;
    font-size: 1.1rem;
  }
  .empty h3 {
    font-size: 0.82rem;
    font-weight: 700;
    color: #1c1410;
    margin: 0 0 0.25rem;
  }
  .empty p {
    font-size: 0.7rem;
    color: #8a7266;
    margin: 0;
    line-height: 1.4;
  }
  .btn-go {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.5rem 1rem;
    font-size: 0.72rem;
    font-weight: 700;
    color: #fff;
    background: linear-gradient(135deg, #fb923c, #ea580c);
    border-radius: 8px;
    text-decoration: none;
    box-shadow: 0 3px 10px -2px rgba(249, 115, 22, 0.3);
    transition: transform 0.15s ease;
    margin-top: 0.4rem;
  }
  .btn-go:hover { transform: translateY(-1px); }

  /* ===== Responsive ===== */
  @media (max-width: 520px) {
    .page-title { font-size: 1rem; }
    .edit-hero { flex-direction: column; }
    .edit-hero-avatar { width: 46px; height: 46px; }
    .edit-hero-initials { font-size: 1rem; }
  }
  @media (prefers-reduced-motion: reduce) {
    *, .edit-input-wrap, .edit-submit, .back-link { transition: none !important; }
  }
</style>

<header class="page-header">
  <a class="back-link" href="?page=profile" aria-label="Retour">←</a>
  <h1 class="page-title">Modifier mon profil</h1>
</header>

<?php if ($flash): ?>
  <div class="flash flash-<?= client_h($flash['type']) ?>" role="alert">
    <?= client_h($flash['text']) ?>
  </div>
<?php endif; ?>

<!-- ===== Bandeau header avec avatar + upload ===== -->
<section class="edit-hero">
  <div class="edit-hero-avatar">
    <?php if ($avatarUrl !== ''): ?>
      <img src="<?= client_h($avatarUrl) ?>" alt="Photo de profil">
    <?php else: ?>
      <span class="edit-hero-initials"><?= client_h(mb_strtoupper(mb_substr($prenom ?: $nom, 0, 2))) ?></span>
    <?php endif; ?>
  </div>
  <div class="edit-hero-text">
    <h2 class="edit-hero-title">Informations personnelles</h2>
    <p class="edit-hero-subtitle">Ces informations apparaîtront sur votre profil.</p>

    <form method="post" enctype="multipart/form-data" class="edit-avatar-form" id="avatarForm">
      <?= client_csrf_field() ?>
      <input type="hidden" name="action" value="upload_avatar">
      <label for="avatarInput" class="edit-avatar-btn">
        📷 Modifier la photo
      </label>
      <input type="file" id="avatarInput" name="avatar"
             accept="image/*" hidden
             data-avatar-autosubmit>
    </form>
  </div>
</section>

<!-- ===== Formulaire principal ===== -->
<form method="post" class="edit-form" id="editForm">
  <?= client_csrf_field() ?>
  <input type="hidden" name="action" value="update_profile">

  <div class="edit-field">
    <label for="nomInput">Nom</label>
    <div class="edit-input-wrap">
      <span class="edit-input-icon" aria-hidden="true">👤</span>
      <input id="nomInput" name="nom" type="text" value="<?= client_h($nom) ?>"
             placeholder="Votre nom" maxlength="100">
    </div>
  </div>

  <div class="edit-field">
    <label for="prenomInput">Prénom</label>
    <div class="edit-input-wrap">
      <span class="edit-input-icon" aria-hidden="true">🪪</span>
      <input id="prenomInput" name="prenom" type="text" value="<?= client_h($prenom) ?>"
             placeholder="Votre prénom" maxlength="100">
    </div>
  </div>

  <div class="edit-field">
    <label for="phoneInput">Téléphone</label>
    <div class="edit-input-wrap">
      <span class="edit-input-icon" aria-hidden="true">📞</span>
      <input id="phoneInput" name="telephone" type="tel" value="<?= client_h($telephone) ?>"
             placeholder="+225 XX XX XX XX XX" maxlength="30">
    </div>
  </div>

  <button type="submit" class="edit-submit"
          data-confirm-click="Enregistrer les modifications de votre profil ?">
     Enregistrer les modifications
  </button>
</form>