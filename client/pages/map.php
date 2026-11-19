<?php
// ===== Chargement des destinations =====
$payload = ClientApi::data($api->request('GET', 'api/destinations?page=1&per_page=12'));
$items   = is_array($payload['items'] ?? null) ? $payload['items'] : [];

// Helper : construit l'URL Google Maps
$gmapsUrl = static function (array $item): string {
    $query = trim((string)($item['nom'] ?? '') . ', ' . (string)($item['localite'] ?? '') . ', Côte d’Ivoire');
    return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($query);
};
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

  /* ===== Bandeau info ===== */
  .panel {
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 12px;
    padding: 0.75rem 0.85rem;
    box-shadow: 0 2px 12px -4px rgba(234, 88, 12, 0.10);
    margin-bottom: 0.85rem;
  }
  .map-info-row {
    display: flex;
    gap: 0.6rem;
    align-items: flex-start;
  }
  .map-info-icon {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: rgba(249, 115, 22, 0.10);
    color: #ea580c;
    font-size: 0.9rem;
  }
  .map-info-text { min-width: 0; }
  .map-info-text strong {
    display: block;
    font-size: 0.75rem;
    font-weight: 800;
    color: #1c1410;
    margin-bottom: 0.15rem;
  }
  .map-info-text p {
    font-size: 0.68rem;
    color: #8a7266;
    line-height: 1.45;
    margin: 0;
  }

  /* ===== Grille des cartes ===== */
  .map-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
    gap: 0.7rem;
    width: 100%;
    min-width: 0;
  }
  .map-card {
    display: flex;
    flex-direction: column;
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 12px;
    padding: 0.8rem 0.8rem 0.7rem;
    box-shadow: 0 2px 12px -4px rgba(234, 88, 12, 0.12);
    transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
    min-width: 0;
    overflow: hidden;
  }
  .map-card:hover {
    border-color: #fcd9b5;
    box-shadow: 0 5px 18px -4px rgba(234, 88, 12, 0.2);
    transform: translateY(-2px);
  }
  .map-card-head {
    display: flex;
    gap: 0.5rem;
    align-items: flex-start;
    flex: 1;
    margin-bottom: 0.7rem;
    min-width: 0;
  }
  .map-card-pin {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(249, 115, 22, 0.10);
    color: #ea580c;
    font-size: 0.9rem;
  }
  .map-card-headtext { flex: 1; min-width: 0; }
  .map-card-title {
    font-size: 0.82rem;
    font-weight: 700;
    color: #1c1410;
    margin: 0 0 0.1rem;
    line-height: 1.25;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
  }
  .map-card-locality {
    font-size: 0.6rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #f97316;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    display: block;
  }

  /* ===== Actions ===== */
  .map-card-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.4rem;
    padding-top: 0.6rem;
    border-top: 1px dashed #f1e4d8;
    margin-top: auto;
  }
  .btn-details,
  .btn-map {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
    padding: 0.45rem 0.65rem;
    font-size: 0.68rem;
    font-weight: 700;
    border-radius: 8px;
    text-decoration: none;
    cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
    text-align: center;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-family: inherit;
  }
  .btn-details {
    background: linear-gradient(135deg, #fb923c, #ea580c);
    color: #fff;
    border: none;
    box-shadow: 0 2px 8px -2px rgba(249, 115, 22, 0.3);
  }
  .btn-details:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px -2px rgba(234, 88, 12, 0.4);
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

  /* ===== État vide ===== */
  .map-empty { padding: 1rem 0.8rem; }
  .empty { text-align: center; padding: 0.75rem 0.4rem; }
  .empty-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    margin-bottom: 0.5rem;
    background: rgba(249, 115, 22, 0.10);
    color: #ea580c;
    border-radius: 50%;
    font-size: 1.05rem;
  }
  .empty h3 {
    font-size: 0.82rem;
    font-weight: 700;
    color: #1c1410;
    margin: 0 0 0.2rem;
  }
  .empty p {
    font-size: 0.7rem;
    color: #8a7266;
    margin: 0;
    line-height: 1.4;
  }

  /* ===== Responsive ===== */
  @media (max-width: 640px) {
    .map-grid {
      grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
      gap: 0.55rem;
    }
    .map-card { padding: 0.7rem 0.7rem 0.6rem; }
    .map-card-title { font-size: 0.75rem; }
  }
  @media (max-width: 380px) {
    .map-grid { grid-template-columns: 1fr; }
    .map-card-actions { grid-template-columns: 1fr; }
  }
  @media (prefers-reduced-motion: reduce) {
    *, .map-card, .btn-details, .btn-map { transition: none !important; }
  }
</style>

<!-- ===== Bandeau informatif ===== -->
<section class="panel map-info">
  <div class="map-info-row">
    <span class="map-info-icon" aria-hidden="true">ℹ️</span>
    <div class="map-info-text">
      <strong>Carte externe</strong>
      <p>La recherche ouvre votre application de cartes. Les coordonnées manquantes ne sont pas inventées.</p>
    </div>
  </div>
</section>

<!-- ===== Liste des destinations ===== -->
<?php if (!$items): ?>
  <section class="panel map-empty">
    <div class="empty">
      <div class="empty-icon">◉</div>
      <h3>Aucune destination publiée</h3>
      <p>Aucune destination publiée à afficher sur la carte.</p>
    </div>
  </section>
<?php else: ?>
  <section class="map-grid" aria-label="Destinations cartographiables">
    <?php foreach ($items as $item): ?>
      <article class="map-card">
        <div class="map-card-head">
          <span class="map-card-pin" aria-hidden="true">📍</span>
          <div class="map-card-headtext">
            <h2 class="map-card-title"><?= client_h($item['nom'] ?? '') ?></h2>
            <span class="map-card-locality"><?= client_h($item['localite'] ?? '') ?></span>
          </div>
        </div>

        <div class="map-card-actions">
          <a class="btn-details"
             href="?page=destination&amp;id=<?= (int)$item['id'] ?>">
            Détails
          </a>
          <a class="btn-map"
             target="_blank"
             rel="noopener noreferrer"
             title="Ouvrir la carte"
             href="<?= client_h($gmapsUrl($item)) ?>">
            <span aria-hidden="true">🗺️</span> Ouvrir
          </a>
        </div>
      </article>
    <?php endforeach; ?>
  </section>
<?php endif; ?>