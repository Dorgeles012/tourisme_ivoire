<section class="panel">
  <div class="eyebrow">Personnaliser mon espace</div>
  <h2>Bienvenue sur Tourisme Ivoire !</h2>
  <p>Ajoutez une photo de profil pour personnaliser votre espace voyageur. Cette étape est facultative.</p>
  <form method="post" enctype="multipart/form-data" class="form-grid">
    <?= client_csrf_field() ?>
    <input type="hidden" name="action" value="avatar_upload">
    <label for="avatar">Photo de profil (JPEG, PNG ou WebP, 5 Mo maximum)</label>
    <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" required>
    <button class="btn" type="submit">Ajouter une photo</button>
  </form>
  <p><a class="btn-quiet" href="?page=dashboard">Ignorer pour le moment</a></p>
</section>
