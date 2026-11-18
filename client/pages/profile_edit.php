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
       . '<p>' . client_h(readableError($e)) . '</p>'
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
            // Recharger pour avoir la nouvelle URL
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
             onchange="document.getElementById('avatarForm').submit();">
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
          onclick="return confirm('Enregistrer les modifications de votre profil ?');">
    💾 Enregistrer les modifications
  </button>
</form>

<style>
  *, *::before, *::after { box-sizing: border-box; }

  .page-header {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    margin-bottom: 1rem;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  .back-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px; height: 38px;
    border-radius: 50%;
    background: #f7f1eb;
    color: #5a4a40;
    text-decoration: none;
    font-size: 1rem;
    transition: all 0.2s ease;
    flex-shrink: 0;
  }

  .back-link:hover { background: #fff8f3; color: #ea580c; }

  .page-title {
    font-size: 1.4rem;
    font-weight: 900;
    color: #1c1410;
    margin: 0;
    letter-spacing: -0.4px;
  }

  /* ===== Flash ===== */
  .flash {
    padding: 0.75rem 1rem;
    border-radius: 10px;
    font-size: 0.82rem;
    font-weight: 600;
    margin-bottom: 1rem;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  .flash-success {
    background: rgba(46,125,50,0.10);
    border: 1px solid rgba(46,125,50,0.3);
    color: #2E7D32;
  }

  .flash-error {
    background: rgba(192,57,43,0.08);
    border: 1px solid rgba(192,57,43,0.25);
    color: #c0392b;
  }

  /* ===== Hero édition ===== */
  .edit-hero {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
    padding: 1.25rem;
    background: linear-gradient(135deg, rgba(249,115,22,0.14), rgba(249,115,22,0.04));
    border: 1px solid rgba(249,115,22,0.18);
    border-radius: 16px;
    margin-bottom: 1.5rem;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  .edit-hero-avatar {
    flex-shrink: 0;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    overflow: hidden;
    background: linear-gradient(135deg, #f97316, #c0392b);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(234,88,12,0.25);
  }

  .edit-hero-avatar img { width: 100%; height: 100%; object-fit: cover; }

  .edit-hero-initials {
    color: #fff;
    font-size: 1.35rem;
    font-weight: 900;
  }

  .edit-hero-text { flex: 1; min-width: 0; }

  .edit-hero-title {
    font-size: 0.95rem;
    font-weight: 800;
    color: #1c1410;
    margin: 0 0 0.2rem;
  }

  .edit-hero-subtitle {
    font-size: 0.75rem;
    color: #8a7266;
    margin: 0 0 0.65rem;
    line-height: 1.35;
  }

  .edit-avatar-form { margin: 0; }

  .edit-avatar-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.4rem 0.75rem;
    font-size: 0.78rem;
    font-weight: 700;
    color: #ea580c;
    background: transparent;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.2s ease;
  }

  .edit-avatar-btn:hover { background: rgba(249,115,22,0.10); }

  /* ===== Formulaire ===== */
  .edit-form {
    display: flex;
    flex-direction: column;
    gap: 1.1rem;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    max-width: 560px;
  }

  .edit-field { display: flex; flex-direction: column; gap: 0.4rem; }

  .edit-field label {
    font-size: 0.78rem;
    font-weight: 700;
    color: #4a3f38;
    letter-spacing: 0.02em;
  }

  .edit-input-wrap {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0 0.9rem;
    background: #f7f1eb;
    border: 1.5px solid transparent;
    border-radius: 12px;
    transition: all 0.2s ease;
  }

  .edit-input-wrap:focus-within {
    background: #fff;
    border-color: rgba(249,115,22,0.8);
    box-shadow: 0 0 0 3px rgba(249,115,22,0.10);
  }

  .edit-input-icon { font-size: 1rem; opacity: 0.7; }

  .edit-input-wrap input {
    flex: 1;
    padding: 0.9rem 0;
    font-family: inherit;
    font-size: 0.9rem;
    color: #1c1410;
    background: transparent;
    border: none;
    outline: none;
    min-width: 0;
  }

  .edit-input-wrap input::placeholder { color: #b8a598; }

  .edit-submit {
    margin-top: 1rem;
    padding: 0.95rem 1rem;
    font-family: inherit;
    font-size: 0.88rem;
    font-weight: 700;
    color: #fff;
    background: linear-gradient(135deg, #fb923c, #ea580c);
    border: none;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 6px 16px -6px rgba(234,88,12,0.5);
  }

  .edit-submit:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 20px -6px rgba(234,88,12,0.6);
  }

  /* ===== Responsive ===== */
  @media (max-width: 520px) {
    .page-title { font-size: 1.2rem; }
    .edit-hero { flex-direction: column; }
    .edit-hero-avatar { width: 52px; height: 52px; }
    .edit-hero-initials { font-size: 1.15rem; }
  }

  @media (prefers-reduced-motion: reduce) {
    *, .edit-input-wrap, .edit-submit, .back-link { transition: none !important; }
  }
</style>