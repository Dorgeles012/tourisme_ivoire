<?php
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id || $id < 1) throw new ClientApiException(404, 'Destination introuvable.');
$destination = ClientApi::data($api->request('GET', 'api/destinations/' . $id));
if (!is_array($destination)) throw new ClientApiException(404, 'Destination introuvable.');
$images = is_array($destination['images'] ?? null) ? $destination['images'] : [];
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

  /* ===== Conteneur principal ===== */
  .dest-detail {
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 12px;
    padding: 0.9rem 0.9rem 0.85rem;
    box-shadow: 0 2px 12px -4px rgba(234, 88, 12, 0.12);
    width: 100%;
    max-width: 720px;
    min-width: 0;
    overflow: hidden;
  }

  /* ===== Retour ===== */
  .dest-back {
    display: inline-block;
    font-size: 0.7rem;
    font-weight: 600;
    color: #f97316;
    text-decoration: none;
    margin-bottom: 0.6rem;
    transition: color 0.15s ease;
  }
  .dest-back:hover { color: #ea580c; }

  /* ===== Hero ===== */
  .dest-hero {
    width: 100%;
    aspect-ratio: 16 / 9;
    border-radius: 10px;
    overflow: hidden;
    background: #f1e4d8;
    margin-bottom: 0.85rem;
  }
  .dest-hero img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
  }

  /* ===== En-tête ===== */
  .dest-head {
    margin-bottom: 0.75rem;
    padding-bottom: 0.6rem;
    border-bottom: 1px solid #f3ebe4;
  }
  .dest-eyebrow {
    display: inline-block;
    font-size: 0.6rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #f97316;
    margin-bottom: 0.25rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 100%;
  }
  .dest-title {
    font-size: 1.15rem;
    font-weight: 800;
    color: #1c1410;
    margin: 0;
    letter-spacing: -0.02em;
    line-height: 1.25;
    overflow-wrap: break-word;
    word-wrap: break-word;
  }

  /* ===== Contenu ===== */
  .dest-content,
  .dest-section {
    margin-bottom: 0.85rem;
  }
  .dest-content p,
  .dest-section p {
    font-size: 0.75rem;
    color: #1c1410;
    line-height: 1.55;
    margin: 0;
    overflow-wrap: break-word;
    word-wrap: break-word;
  }
  .dest-section h3 {
    font-size: 0.75rem;
    font-weight: 700;
    color: #ea580c;
    margin: 0 0 0.3rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }

  /* ===== Galerie ===== */
  .dest-gallery {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 0.5rem;
    margin-bottom: 0.85rem;
    width: 100%;
    max-width: 100%;
  }
  .dest-figure {
    margin: 0;
    border-radius: 8px;
    overflow: hidden;
    background: #f1e4d8;
    border: 1px solid #f1e4d8;
  }
  .dest-figure img {
    width: 100%;
    aspect-ratio: 1;
    object-fit: cover;
    display: block;
    transition: transform 0.35s ease;
  }
  .dest-figure:hover img { transform: scale(1.05); }
  .dest-figure figcaption {
    padding: 0.3rem 0.4rem;
    font-size: 0.58rem;
    color: #8a7266;
    text-align: center;
    background: #fff;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  /* ===== Carte ===== */
  .dest-map {
    margin-bottom: 0.85rem;
  }
  .dest-map-link {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.5rem 0.8rem;
    font-size: 0.7rem;
    font-weight: 600;
    color: #f97316;
    background: #fff8f3;
    border: 1.5px solid #f1e4d8;
    border-radius: 8px;
    text-decoration: none;
    transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
    max-width: 100%;
  }
  .dest-map-link:hover {
    background: #fff;
    border-color: #f97316;
    color: #ea580c;
  }

  /* ===== Actions ===== */
  .dest-actions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.5rem;
    margin-top: 0.85rem;
    padding-top: 0.85rem;
    border-top: 1px solid #f3ebe4;
  }
  .dest-form {
    margin: 0;
    width: 100%;
    min-width: 0;
  }
  .btn-primary,
  .btn-secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    padding: 0.55rem 0.8rem;
    font-size: 0.72rem;
    font-weight: 600;
    font-family: inherit;
    border-radius: 8px;
    cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
  }
  .btn-primary {
    background: linear-gradient(135deg, #fb923c, #ea580c);
    color: #fff;
    border: none;
    box-shadow: 0 3px 10px -2px rgba(249, 115, 22, 0.3);
  }
  .btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 5px 14px -2px rgba(234, 88, 12, 0.4);
  }
  .btn-secondary {
    background: transparent;
    color: #ea580c;
    border: 1.5px solid #f97316;
  }
  .btn-secondary:hover {
    background: rgba(249, 115, 22, 0.08);
    transform: translateY(-1px);
  }

  /* ===== Responsive ===== */
  @media (max-width: 640px) {
    .dest-detail { padding: 0.75rem 0.7rem 0.7rem; }
    .dest-title { font-size: 1rem; }
    .dest-content p,
    .dest-section p { font-size: 0.72rem; }
    .dest-gallery { grid-template-columns: repeat(auto-fill, minmax(115px, 1fr)); }
  }
  @media (max-width: 420px) {
    .dest-actions { grid-template-columns: 1fr; }
    .dest-gallery { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  }
  @media (prefers-reduced-motion: reduce) {
    *, .dest-figure img, .btn-primary, .btn-secondary { transition: none !important; }
  }
</style>

<article class="panel dest-detail">

  <a class="dest-back" href="?page=explore">← Retour à l'exploration</a>

  <?php if (!empty($destination['image_principale'])): ?>
    <div class="dest-hero">
      <img src="<?= client_h($destination['image_principale']) ?>"
           alt="<?= client_h($destination['nom'] ?? '') ?>">
    </div>
  <?php endif; ?>

  <header class="dest-head">
    <span class="dest-eyebrow">
      <?= client_h($destination['localite'] ?? '') ?> · <?= client_h($destination['categorie'] ?? '') ?>
    </span>
    <h2 class="dest-title"><?= client_h($destination['nom'] ?? '') ?></h2>
  </header>

  <div class="dest-content">
    <p><?= nl2br(client_h($destination['description'] ?? '')) ?></p>
  </div>

  <?php if (!empty($destination['histoire'])): ?>
    <section class="dest-section">
      <h3>Histoire</h3>
      <p><?= nl2br(client_h($destination['histoire'])) ?></p>
    </section>
  <?php endif; ?>

  <?php if (!empty($destination['conseils_visite'])): ?>
    <section class="dest-section">
      <h3>Conseils de visite</h3>
      <p><?= nl2br(client_h($destination['conseils_visite'])) ?></p>
    </section>
  <?php endif; ?>

  <?php if (!empty($destination['informations_pratiques'])): ?>
    <section class="dest-section">
      <h3>Informations pratiques</h3>
      <p><?= nl2br(client_h($destination['informations_pratiques'])) ?></p>
    </section>
  <?php endif; ?>

  <?php if ($images): ?>
    <section class="dest-gallery">
      <?php foreach ($images as $image): ?>
        <?php if (empty($image['url'])) continue; ?>
        <figure class="dest-figure">
          <img src="<?= client_h($image['url']) ?>"
               alt="<?= client_h($image['texte_alternatif'] ?? $destination['nom'] ?? '') ?>"
               loading="lazy">
          <?php if (!empty($image['credit'])): ?>
            <figcaption><?= client_h($image['credit']) ?></figcaption>
          <?php endif; ?>
        </figure>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>

  <div class="dest-map">
    <a class="dest-map-link"
       target="_blank"
       rel="noopener noreferrer"
       href="https://www.google.com/maps/search/?api=1&amp;query=<?= rawurlencode((string)($destination['nom'] ?? '') . ', ' . (string)($destination['localite'] ?? '') . ', Côte d’Ivoire') ?>">
      📍 Ouvrir l’itinéraire sur la carte
    </a>
  </div>

  <div class="dest-actions">
    <?php if ($user): ?>
      <form method="post" class="dest-form">
        <?= client_csrf_field() ?>
        <input type="hidden" name="action" value="favorite_add">
        <input type="hidden" name="destination_id" value="<?= (int)$destination['id'] ?>">
        <button class="btn-primary" type="submit">Ajouter aux favoris</button>
      </form>

      <form method="post" class="dest-form">
        <?= client_csrf_field() ?>
        <input type="hidden" name="action" value="visit_record">
        <input type="hidden" name="destination_id" value="<?= (int)$destination['id'] ?>">
        <button class="btn-secondary" type="submit">J’ai visité ce lieu</button>
      </form>
    <?php else: ?>
      <a class="btn-primary" href="?page=login">Connectez-vous pour enregistrer vos favoris et visites</a>
    <?php endif; ?>
  </div>

</article>