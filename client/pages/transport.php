<?php
// ===== Recherche d'itinéraires =====
$depart  = trim((string)($_GET['depart'] ?? ''));
$arrivee = trim((string)($_GET['arrivee'] ?? ''));
$searched = isset($_GET['depart']) || isset($_GET['arrivee']);

$routes = [];
$error  = null;

if ($searched && ($depart !== '' && $arrivee !== '')) {
    try {
        $routes = ClientApi::data($api->request('GET',
            'api/itineraires?' . http_build_query(['depart' => $depart, 'arrivee' => $arrivee])
        ));
        $routes = is_array($routes) ? $routes : [];
    } catch (Throwable $e) {
        $error = readableError($e);
    }
} elseif ($searched) {
    $error = 'Indiquez le départ et l\'arrivée.';
}

// ===== Modes de transport =====
$transportsError = null;
try {
    $transports = ClientApi::data($api->request('GET', 'api/transports'));
    $transports = is_array($transports) ? $transports : [];
} catch (Throwable $e) {
    $transports = [];
    $transportsError = readableError($e);
}

// ===== Traitement réservation =====
$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reserve') {
    client_csrf_verify();
    $itineraireId = filter_var($_POST['itineraire_id'] ?? null, FILTER_VALIDATE_INT);
    $quantite     = max(1, (int)($_POST['quantite'] ?? 1));
    if ($itineraireId) {
        try {
            $result = ClientApi::data($api->request('POST', 'api/reservations', [
                'itineraire_id' => $itineraireId,
                'quantite'      => $quantite,
            ]));
            $ref = $result['reference'] ?? '';
            $flash = ['type' => 'success', 'text' => 'Demande envoyée : ' . $ref];
        } catch (Throwable $e) {
            $flash = ['type' => 'error', 'text' => readableError($e)];
        }
    }
}

// ===== Helper : couleur dispo =====
$availabilityMeta = static function (?string $dispo): array {
    $available = mb_strtolower((string)$dispo) === 'disponible';
    return [
        'available' => $available,
        'label'     => $available ? 'Disponible' : 'À confirmer',
        'color'     => $available ? '#2E7D32' : '#d4a017',
    ];
};
?>

<!-- ===== Header ===== -->
<header class="page-header">
  <?php if (!empty($user)): ?>
    <a class="page-action" href="?page=reservations" title="Historique des réservations">
      <span aria-hidden="true">🧾</span> Mes réservations
    </a>
  <?php endif; ?>
</header>

<?php if ($flash): ?>
  <div class="flash flash-<?= client_h($flash['type']) ?>" role="alert">
    <?= client_h($flash['text']) ?>
  </div>
<?php endif; ?>
<?php if ($transportsError !== null): ?>
  <div class="flash flash-error" role="alert">
    <?= client_h($transportsError) ?>
  </div>
<?php endif; ?>

<!-- ===== Formulaire de recherche ===== -->
<section class="transport-search">
  <header class="transport-search-head">
    <span class="transport-search-icon" aria-hidden="true">🚌</span>
    <h2 class="transport-search-title">Rechercher un itinéraire</h2>
  </header>

  <form method="get" class="transport-form">
    <input type="hidden" name="page" value="transport">

    <div class="transport-field">
      <label for="departInput">Départ</label>
      <div class="transport-input-wrap">
        <span class="transport-input-icon" aria-hidden="true">⭕</span>
        <input id="departInput" name="depart" type="text"
               value="<?= client_h($depart) ?>" maxlength="180" required
               placeholder="Ex : Abidjan">
      </div>
    </div>

    <div class="transport-field">
      <label for="arriveeInput">Arrivée</label>
      <div class="transport-input-wrap">
        <span class="transport-input-icon" aria-hidden="true">📍</span>
        <input id="arriveeInput" name="arrivee" type="text"
               value="<?= client_h($arrivee) ?>" maxlength="180" required
               placeholder="Ex : Yamoussoukro">
      </div>
    </div>

    <button type="submit" class="transport-submit">
       Comparer les options
    </button>
  </form>
</section>

<!-- ===== Chips des modes de transport ===== -->
<?php if ($transports): ?>
  <div class="transport-modes">
    <?php foreach ($transports as $t): ?>
      <span class="transport-chip"><?= client_h($t['nom'] ?? '') ?></span>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<!-- ===== Résultats ===== -->
<?php if ($searched && $error): ?>
  <section class="panel empty-panel" role="alert">
    <div class="empty">
      <div class="empty-icon">❌</div>
      <h3>Erreur</h3>
      <p data-client-alert data-client-alert-type="error"><?= client_h($error) ?></p>
    </div>
  </section>
<?php elseif ($searched && !$routes): ?>
  <section class="panel empty-panel">
    <div class="empty">
      <div class="empty-icon">🛣️</div>
      <h3>Aucun itinéraire trouvé</h3>
      <p>Aucun itinéraire vérifié ne correspond à cette recherche. Les tarifs et horaires ne sont pas inventés.</p>
    </div>
  </section>
<?php elseif ($routes): ?>
  <section class="routes-list">
    <?php foreach ($routes as $route): ?>
      <?php
        $meta      = $availabilityMeta($route['disponibilite'] ?? null);
        $price     = $route['tarif'] ?? null;
        $duration  = $route['duree_minutes'] ?? null;
        $routeId   = (int)($route['id'] ?? 0);
        $transport = (string)($route['transport'] ?? '');
        $carrier   = (string)($route['transporteur'] ?? '');
        $depLib    = (string)($route['depart_libelle'] ?? '');
        $arrLib    = (string)($route['arrivee_libelle'] ?? '');
        $devise    = (string)($route['devise'] ?? '');
      ?>
      <article class="route-card" style="--avail-color: <?= $meta['color'] ?>;">
        <header class="route-head">
          <h2 class="route-title"><?= client_h($depLib) ?> → <?= client_h($arrLib) ?></h2>
          <span class="route-badge"><?= $meta['label'] ?></span>
        </header>

        <div class="route-meta">
          <span class="route-meta-item">
            <span aria-hidden="true">🚌</span>
            <?= client_h($transport) ?>
            <?php if ($carrier !== ''): ?>
              · <?= client_h($carrier) ?>
            <?php endif; ?>
          </span>
          <span class="route-meta-item">
            <span aria-hidden="true">💰</span>
            <?= $price === null ? 'Tarif non communiqué' : client_h((string)$price) . ' ' . client_h($devise) ?>
          </span>
          <?php if ($duration !== null): ?>
            <span class="route-meta-item">
              <span aria-hidden="true">⏱️</span>
              <?= (int)$duration ?> min
            </span>
          <?php endif; ?>
        </div>

        <?php if (!empty($user)): ?>
          <form method="post" class="route-reserve-form"
                data-confirm="Confirmer la réservation ? Voulez-vous envoyer une demande pour ce trajet ?">
            <?= client_csrf_field() ?>
            <input type="hidden" name="action" value="reserve">
            <input type="hidden" name="itineraire_id" value="<?= $routeId ?>">
            <input type="hidden" name="quantite" value="1">
            <button type="submit" class="route-reserve-btn">
              📤 Envoyer une demande de réservation
            </button>
            <small class="route-reserve-note">La demande reste soumise à confirmation du transporteur.</small>
          </form>
        <?php else: ?>
          <a class="route-login-btn" href="?page=login">
            🔒 Connectez-vous pour réserver
          </a>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
  </section>
<?php endif; ?>

<style>
  *, *::before, *::after { box-sizing: border-box; }

  /* ===== Header ===== */
  .page-header {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    margin-bottom: 1rem;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  .back-link {
    flex-shrink: 0;
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
    margin-top: 0.3rem;
  }

  .back-link:hover { background: #fff8f3; color: #ea580c; }

  .page-header-text { flex: 1; min-width: 0; }

  .page-title {
    font-size: 1.4rem;
    font-weight: 900;
    color: #1c1410;
    margin: 0 0 0.2rem;
    letter-spacing: -0.4px;
  }

  .page-subtitle {
    font-size: 0.78rem;
    color: #8a7266;
    margin: 0;
  }

  .page-action {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.55rem 0.9rem;
    font-size: 0.78rem;
    font-weight: 700;
    color: #ea580c;
    background: rgba(249, 115, 22, 0.08);
    border: 1px solid rgba(249, 115, 22, 0.25);
    border-radius: 999px;
    text-decoration: none;
    white-space: nowrap;
    transition: all 0.2s ease;
  }

  .page-action:hover {
    background: rgba(249, 115, 22, 0.15);
    border-color: rgba(249, 115, 22, 0.45);
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

  /* ===== Formulaire ===== */
  .transport-search {
    padding: 1.25rem;
    background: linear-gradient(135deg, rgba(249,115,22,0.07), rgba(22,160,133,0.05));
    border: 1px solid rgba(249,115,22,0.15);
    border-radius: 16px;
    margin-bottom: 1.25rem;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  .transport-search-head {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    margin-bottom: 1rem;
  }

  .transport-search-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px; height: 36px;
    background: rgba(249,115,22,0.12);
    border-radius: 10px;
    font-size: 1rem;
  }

  .transport-search-title {
    font-size: 1rem;
    font-weight: 800;
    color: #1c1410;
    margin: 0;
  }

  .transport-form {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
  }

  .transport-field { display: flex; flex-direction: column; gap: 0.35rem; }

  .transport-field label {
    font-size: 0.75rem;
    font-weight: 700;
    color: #4a3f38;
    letter-spacing: 0.02em;
  }

  .transport-input-wrap {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0 0.9rem;
    background: #fff;
    border: 1.5px solid #f1e4d8;
    border-radius: 12px;
    transition: all 0.2s ease;
  }

  .transport-input-wrap:focus-within {
    border-color: #f97316;
    box-shadow: 0 0 0 3px rgba(249,115,22,0.10);
  }

  .transport-input-icon { font-size: 0.95rem; opacity: 0.7; }

  .transport-input-wrap input {
    flex: 1;
    padding: 0.85rem 0;
    font-family: inherit;
    font-size: 0.88rem;
    color: #1c1410;
    background: transparent;
    border: none;
    outline: none;
    min-width: 0;
  }

  .transport-input-wrap input::placeholder { color: #b8a598; }

  .transport-submit {
    margin-top: 0.35rem;
    padding: 0.85rem 1rem;
    font-family: inherit;
    font-size: 0.85rem;
    font-weight: 700;
    color: #fff;
    background: linear-gradient(135deg, #fb923c, #ea580c);
    border: none;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 6px 16px -6px rgba(234,88,12,0.5);
  }

  .transport-submit:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 20px -6px rgba(234,88,12,0.6);
  }

  /* ===== Chips modes ===== */
  .transport-modes {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    margin-bottom: 1rem;
  }

  .transport-chip {
    display: inline-flex;
    padding: 0.35rem 0.75rem;
    font-size: 0.72rem;
    font-weight: 700;
    color: #0e7d6b;
    background: rgba(22,160,133,0.08);
    border: 1px solid rgba(22,160,133,0.2);
    border-radius: 999px;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  /* ===== Cartes itinéraires ===== */
  .routes-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  .route-card {
    padding: 1rem;
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 14px;
    box-shadow: 0 4px 18px -6px rgba(234,88,12,0.15);
    transition: all 0.2s ease;
  }

  .route-card:hover {
    border-color: #fcd9b5;
    box-shadow: 0 8px 22px -6px rgba(234,88,12,0.22);
  }

  .route-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.7rem;
  }

  .route-title {
    font-size: 1rem;
    font-weight: 800;
    color: #1c1410;
    margin: 0;
    line-height: 1.3;
  }

  .route-badge {
    flex-shrink: 0;
    padding: 0.2rem 0.55rem;
    font-size: 0.65rem;
    font-weight: 800;
    letter-spacing: 0.03em;
    color: var(--avail-color);
    background: color-mix(in srgb, var(--avail-color) 10%, transparent);
    border-radius: 6px;
    white-space: nowrap;
  }

  .route-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.85rem;
    margin-bottom: 1rem;
  }

  .route-meta-item {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    font-size: 0.75rem;
    color: #5a4a40;
  }

  .route-reserve-form {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    margin: 0;
    padding-top: 0.75rem;
    border-top: 1px dashed #f1e4d8;
  }

  .route-reserve-btn,
  .route-login-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    width: 100%;
    padding: 0.75rem 1rem;
    font-family: inherit;
    font-size: 0.85rem;
    font-weight: 700;
    color: #fff;
    background: linear-gradient(135deg, #fb923c, #ea580c);
    border: none;
    border-radius: 10px;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(249,115,22,0.28);
  }

  .route-reserve-btn:hover,
  .route-login-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(234,88,12,0.4);
  }

  .route-reserve-note {
    font-size: 0.68rem;
    color: #8a7266;
    text-align: center;
    line-height: 1.35;
  }

  /* ===== États vides ===== */
  .panel, .empty-panel {
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 14px;
    padding: 1.5rem 1rem;
    box-shadow: 0 4px 18px -6px rgba(234,88,12,0.15);
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  .empty { text-align: center; padding: 1rem 0.5rem; }

  .empty-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 52px; height: 52px;
    margin-bottom: 0.65rem;
    background: rgba(249,115,22,0.10);
    border-radius: 50%;
    font-size: 1.5rem;
  }

  .empty h3 { font-size: 0.95rem; font-weight: 700; color: #1c1410; margin: 0 0 0.3rem; }
  .empty p  { font-size: 0.78rem; color: #8a7266; margin: 0; line-height: 1.4; }

  /* ===== Responsive ===== */
  @media (min-width: 640px) {
    .transport-form {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0.85rem;
      align-items: end;
    }
    .transport-submit {
      grid-column: 1 / -1;
    }
  }

  @media (max-width: 520px) {
    .page-title { font-size: 1.2rem; }
    .page-action { display: none; }
    .route-title { font-size: 0.92rem; }
    .route-meta { gap: 0.6rem; }
    .route-meta-item { font-size: 0.72rem; }
  }

  @media (prefers-reduced-motion: reduce) {
    *, .route-card, .transport-submit, .route-reserve-btn, .back-link { transition: none !important; }
  }
</style>