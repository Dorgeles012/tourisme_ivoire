<?php $profile = ClientApi::data($api->request('GET', 'api/auth/me')); ?>

<section class="panel profile-panel">
  <?php if (!empty($profile['avatar_url'])): ?><img src="<?= client_h($profile['avatar_url']) ?>" alt="Photo de profil" width="112" height="112" style="object-fit:cover;border-radius:50%"><br><?php endif; ?>
  <form method="post" enctype="multipart/form-data" class="form-grid">
    <?= client_csrf_field() ?><input type="hidden" name="action" value="avatar_upload">
    <div class="field full"><label for="avatar">Photo de profil (JPEG, PNG ou WebP, 5 Mo maximum)</label><input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" required></div>
    <div class="form-actions"><button class="btn btn-quiet" type="submit">Mettre à jour la photo</button></div>
  </form>
  <header class="panel-head">
    <h2 class="section-title">Informations personnelles</h2>
    <p class="row-meta">Ces informations sont associées à votre compte Tourisme Ivoire.</p>
  </header>

  <form method="post" class="form-grid">
    <?= client_csrf_field() ?>
    <input type="hidden" name="action" value="profile">

    <div class="field">
      <label for="nom">Nom</label>
      <div class="input-wrapper">
        <input id="nom" name="nom" type="text" required maxlength="100"
          autocomplete="family-name"
          value="<?= client_h($profile['nom'] ?? '') ?>"
          placeholder="Votre nom">
      </div>
    </div>

    <div class="field">
      <label for="prenom">Prénom</label>
      <div class="input-wrapper">
        <input id="prenom" name="prenom" type="text" maxlength="100"
          autocomplete="given-name"
          value="<?= client_h($profile['prenom'] ?? '') ?>"
          placeholder="Votre prénom">
      </div>
    </div>

    <div class="field full">
      <label for="email">Adresse e-mail</label>
      <div class="input-wrapper">
        <input id="email" type="email"
          value="<?= client_h($profile['email'] ?? '') ?>"
          disabled>
      </div>
      <small class="hint">L'e-mail ne peut pas être modifié.</small>
    </div>

    <div class="field full">
      <label for="telephone">Téléphone</label>
      <div class="input-wrapper">
        <input id="telephone" name="telephone" type="tel" maxlength="30"
          autocomplete="tel" inputmode="tel"
          value="<?= client_h($profile['telephone'] ?? '') ?>"
          placeholder="+225 XX XX XX XX">
      </div>
    </div>

    <div class="form-actions">
      <button class="btn" type="submit">Enregistrer les modifications</button>
    </div>
  </form>
</section>

<style>
  *,
  *::before,
  *::after {
    box-sizing: border-box;
  }

  .profile-panel {
    background: #ffffff;
    border: 1px solid #f1e4d8;
    border-radius: 14px;
    padding: 1.2rem 1.2rem 1rem;
    box-shadow: 0 4px 18px -6px rgba(234, 88, 12, 0.15);
    max-width: 620px;
    width: 100%;
    min-width: 0;
    overflow-x: hidden;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  .profile-panel .panel-head {
    margin-bottom: 1rem;
    padding-bottom: 0.8rem;
    border-bottom: 1px solid #f3ebe4;
  }

  .profile-panel .section-title {
    font-size: 1rem;
    font-weight: 700;
    color: #1c1410;
    margin: 0 0 0.25rem;
  }

  .profile-panel .row-meta {
    font-size: 0.78rem;
    color: #8a7266;
    margin: 0;
    line-height: 1.4;
  }

  .profile-panel .form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.85rem;
    width: 100%;
    max-width: 100%;
  }

  .profile-panel .field {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    min-width: 0;
  }

  .profile-panel .field.full {
    grid-column: 1 / -1;
  }

  .profile-panel .field label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #1c1410;
  }

  .profile-panel .input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    width: 100%;
    min-width: 0;
  }

  .profile-panel .input-wrapper input {
    width: 100%;
    min-width: 0;
    max-width: 100%;
    padding: 0.65rem 0.9rem;
    background: #fff8f3;
    border: 1.5px solid #f1e4d8;
    border-radius: 10px;
    font-size: 0.85rem;
    font-family: inherit;
    color: #1c1410;
    outline: none;
    transition: all 0.2s ease;
  }

  .profile-panel .input-wrapper input::placeholder {
    color: #d8bfa8;
  }

  .profile-panel .input-wrapper input:hover:not(:disabled) {
    border-color: #e8d0bd;
    background: #fff;
  }

  .profile-panel .input-wrapper input:focus {
    border-color: #f97316;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.10);
  }

  .profile-panel .input-wrapper input:disabled {
    background: #f9f6f3;
    color: #8a7266;
    cursor: not-allowed;
    border-style: dashed;
  }

  .profile-panel .hint {
    font-size: 0.68rem;
    color: #8a7266;
    margin-top: 0.1rem;
  }

  .profile-panel .form-actions {
    grid-column: 1 / -1;
    margin-top: 0.4rem;
  }

  .profile-panel .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.65rem 1.2rem;
    background: linear-gradient(135deg, #fb923c, #ea580c);
    color: #fff;
    font-size: 0.85rem;
    font-weight: 600;
    font-family: inherit;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(249, 115, 22, 0.35);
    transition: all 0.2s ease;
  }

  .profile-panel .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(234, 88, 12, 0.45);
  }

  .profile-panel .btn:active {
    transform: translateY(0);
  }

  @media (max-width: 520px) {
    .profile-panel {
      padding: 1rem 0.9rem 0.85rem;
    }

    .profile-panel .form-grid {
      grid-template-columns: 1fr;
    }

    .profile-panel .field.full {
      grid-column: auto;
    }
  }
</style>
