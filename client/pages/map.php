<?php
$args = ['page' => 1, 'per_page' => 50];
if (!empty($_GET['q'])) $args['q'] = trim((string)$_GET['q']);
$payload = ClientApi::data($api->request('GET', 'api/destinations?' . http_build_query($args)));
$items = is_array($payload['items'] ?? null) ? $payload['items'] : [];
$query = (string)($_GET['q'] ?? '');
?>

<section class="panel map-search">
  <p class="map-intro">
    La carte interactive mobile est adaptée ici en liste géolocalisée,
    avec ouverture de l’itinéraire dans le service de cartographie.
  </p>

  <form method="get" class="map-form">
    <input type="hidden" name="page" value="map">

    <div class="map-field">
      <label for="q">Filtrer les lieux</label>
      <input id="q" name="q" value="<?= client_h($query) ?>"
             placeholder="Destination ou localité" maxlength="120">
    </div>

    <button class="btn-filter" type="submit">Filtrer</button>
  </form>
</section>

<?php if (!$items): ?>
  <section class="panel map-empty">
    <div class="empty">
      <div class="empty-icon">◉</div>
      <h3>Aucun lieu trouvé</h3>
      <p>Essayez un autre nom de destination ou de localité.</p>
    </div>
  </section>
<?php else: ?>
  <section class="map-grid">
    <?php foreach ($items as $item): ?>
      <article class="map-card">
        <div class="map-card-body">
          <span class="map-card-eyebrow"><?= client_h($item['localite'] ?? '') ?></span>
          <h2 class="map-card-title"><?= client_h($item['nom'] ?? '') ?></h2>
          <p class="map-card-desc">
            <?= client_h(mb_strimwidth((string)($item['description'] ?? ''), 0, 130, '…')) ?>
          </p>
        </div>

        <div class="map-card-actions">
          <a class="btn-details" href="?page=destination&amp;id=<?= (int)$item['id'] ?>">
            Détails
          </a>

          <?php if (isset($item['latitude'], $item['longitude'])): ?>
            <a class="btn-map"
               target="_blank"
               rel="noopener noreferrer"
               href="https://www.google.com/maps/search/?api=1&amp;query=<?= rawurlencode((string)$item['latitude'] . ',' . (string)$item['longitude']) ?>">
              Voir sur la carte ↗
            </a>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </section>
<?php endif; ?>

<style>
  *,
  *::before,
  *::after {
    box-sizing: border-box;
  }

  .map-search {
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 14px;
    padding: 1rem 1.1rem;
    box-shadow: 0 4px 18px -6px rgba(234, 88, 12, 0.15);
    margin-bottom: 1rem;
    width: 100%;
    max-width: 100%;
    min-width: 0;
    overflow: hidden;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  .map-intro {
    font-size: 0.75rem;
    color: #8a7266;
    line-height: 1.5;
    margin: 0 0 0.85rem;
    overflow-wrap: break-word;
  }

  .map-form {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 0.75rem;
    align-items: end;
    width: 100%;
    max-width: 100%;
  }

  .map-field {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    min-width: 0;
  }

  .map-field label {
    font-size: 0.72rem;
    font-weight: 600;
    color: #1c1410;
  }

  .map-field input {
    width: 100%;
    min-width: 0;
    padding: 0.6rem 0.85rem;
    font-size: 0.82rem;
    font-family: inherit;
    color: #1c1410;
    background: #fff8f3;
    border: 1.5px solid #f1e4d8;
    border-radius: 10px;
    outline: none;
    transition: all 0.2s ease;
  }

  .map-field input::placeholder { color: #d8bfa8; }

  .map-field input:hover {
    border-color: #e8d0bd;
    background: #fff;
  }

  .map-field input:focus {
    border-color: #f97316;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.10);
  }

  .btn-filter {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.65rem 1.2rem;
    font-size: 0.82rem;
    font-weight: 600;
    font-family: inherit;
    color: #fff;
    background: linear-gradient(135deg, #fb923c, #ea580c);
    border: none;
    border-radius: 10px;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(249, 115, 22, 0.30);
    transition: all 0.2s ease;
    white-space: nowrap;
  }

  .btn-filter:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(234, 88, 12, 0.40);
  }

  .map-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 0.85rem;
    width: 100%;
    max-width: 100%;
    min-width: 0;
  }

  .map-card {
    display: flex;
    flex-direction: column;
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 14px;
    padding: 0.95rem 0.95rem 0.85rem;
    box-shadow: 0 4px 18px -6px rgba(234, 88, 12, 0.15);
    transition: all 0.25s ease;
    min-width: 0;
    overflow: hidden;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  .map-card:hover {
    border-color: #fcd9b5;
    box-shadow: 0 8px 24px -6px rgba(234, 88, 12, 0.25);
    transform: translateY(-2px);
  }

  .map-card-body {
    flex: 1;
    min-width: 0;
    margin-bottom: 0.85rem;
  }

  .map-card-eyebrow {
    display: inline-block;
    font-size: 0.62rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #f97316;
    margin-bottom: 0.3rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 100%;
  }

  .map-card-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: #1c1410;
    margin: 0 0 0.4rem;
    line-height: 1.25;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
  }

  .map-card-desc {
    font-size: 0.74rem;
    color: #8a7266;
    line-height: 1.5;
    margin: 0;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow-wrap: break-word;
    word-wrap: break-word;
  }

  .map-card-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.45rem;
    padding-top: 0.75rem;
    border-top: 1px dashed #f1e4d8;
    margin-top: auto;
  }

  .btn-details,
  .btn-map {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.5rem 0.75rem;
    font-size: 0.72rem;
    font-weight: 600;
    border-radius: 9px;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: center;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .btn-details {
    background: linear-gradient(135deg, #fb923c, #ea580c);
    color: #fff;
    border: none;
    box-shadow: 0 3px 10px rgba(249, 115, 22, 0.30);
  }

  .btn-details:hover {
    transform: translateY(-1px);
    box-shadow: 0 5px 14px rgba(234, 88, 12, 0.40);
  }

  .btn-map {
    background: transparent;
    color: #ea580c;
    border: 1.5px solid #f97316;
  }

  .btn-map:hover {
    background: rgba(249, 115, 22, 0.08);
    transform: translateY(-1px);
  }

  .map-empty {
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 14px;
    padding: 1.2rem 1rem;
    box-shadow: 0 4px 18px -6px rgba(234, 88, 12, 0.15);
    width: 100%;
    max-width: 100%;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  .empty {
    text-align: center;
    padding: 1rem 0.5rem;
  }

  .empty-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    margin-bottom: 0.6rem;
    background: rgba(249, 115, 22, 0.10);
    color: #ea580c;
    border-radius: 50%;
    font-size: 1.2rem;
  }

  .empty h3 {
    font-size: 0.9rem;
    font-weight: 700;
    color: #1c1410;
    margin: 0 0 0.3rem;
  }

  .empty p {
    font-size: 0.78rem;
    color: #8a7266;
    margin: 0;
    line-height: 1.4;
  }

  @media (max-width: 640px) {
    .map-search { padding: 0.85rem 0.9rem; }
    .map-form { grid-template-columns: 1fr; }
    .btn-filter { width: 100%; }
    .map-grid {
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 0.7rem;
    }
    .map-card { padding: 0.85rem 0.85rem 0.75rem; }
    .map-card-title { font-size: 0.88rem; }
    .map-card-desc { font-size: 0.7rem; }
  }

  @media (max-width: 380px) {
    .map-grid { grid-template-columns: 1fr; }
    .map-card-actions { grid-template-columns: 1fr; }
  }
</style>