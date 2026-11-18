<?php
$settings = is_array($GLOBALS['client_settings'] ?? null) ? $GLOBALS['client_settings'] : [];
$access = is_array($settings['accessibilite_json'] ?? null) ? $settings['accessibilite_json'] : [];
?>
<section class="grid two-col">
  <article class="panel">
    <h2 class="section-title">Apparence</h2>
    <form method="post" class="form-grid">
      <?= client_csrf_field() ?>
      <input type="hidden" name="action" value="settings">
      <div class="field full">
        <label for="theme">Thème de l’application</label>
        <select id="theme" name="theme">
          <option value="systeme" <?= ($settings['theme'] ?? '') === 'systeme' ? 'selected' : '' ?>>Système</option>
          <option value="clair" <?= ($settings['theme'] ?? '') === 'clair' ? 'selected' : '' ?>>Clair</option>
          <option value="sombre" <?= ($settings['theme'] ?? '') === 'sombre' ? 'selected' : '' ?>>Sombre</option>
        </select>
      </div>
      <div class="field full">
        <label><input type="checkbox" name="contraste_eleve" <?= !empty($access['contraste_eleve']) ? 'checked' : '' ?>> Contraste renforcé</label>
      </div>
      <div class="field full">
        <label><input type="checkbox" name="texte_agrandi" <?= !empty($access['texte_agrandi']) ? 'checked' : '' ?>> Texte agrandi</label>
      </div>
      <div class="form-actions"><button class="btn" type="submit">Enregistrer</button></div>
    </form>
  </article>
  <article class="panel">
    <h2 class="section-title">Contenu & données</h2>
    <p>Enregistrez les destinations déjà publiées sur cet appareil.</p>
    <a class="btn" href="?page=offline_library">Bibliothèque hors ligne</a>
  </article>
</section>
<section class="panel">
  <h2 class="section-title">Fonctionnalités</h2>
  <div class="feature-grid">
    <article><h3>Notifications push</h3><p>Les notifications dans l’application sont prises en charge. Les notifications push nécessitent un fournisseur externe non configuré.</p></article>
    <article><h3>Traduction vocale</h3><p>La reconnaissance vocale nécessite un fournisseur de traduction configuré.</p></article>
    <article><h3>Diffusion en direct</h3><p>Aucun fournisseur de streaming n’est configuré ; la diffusion n’est pas disponible.</p></article>
  </div>
</section>
