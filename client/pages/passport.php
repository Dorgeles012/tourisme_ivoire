<?php
if (!$user) {
    client_redirect('login');
}

$loadError = null;

try {
    $passport = ClientApi::data($api->request('GET', 'api/passport'));
    $badgesData = ClientApi::data($api->request('GET', 'api/passport/badges'));
    $visitsData = ClientApi::data($api->request('GET', 'api/passport/visits'));

    if (!is_array($passport)
        || !array_key_exists('niveau', $passport)
        || !array_key_exists('points_total', $passport)
    ) {
        throw new UnexpectedValueException('Les données du passeport sont incomplètes.');
    }

    $toList = static function (mixed $data, string $resource): array {
        if (!is_array($data)) {
            throw new UnexpectedValueException('La liste ' . $resource . ' reçue est invalide.');
        }
        foreach ($data as $item) {
            if (!is_array($item)) {
                throw new UnexpectedValueException('Un élément de la liste ' . $resource . ' est invalide.');
            }
        }
        return array_values($data);
    };

    $badges = $toList($badgesData, 'des badges');
    $visits = $toList($visitsData, 'des visites');
} catch (ClientApiException $e) {
    if ($e->status === 401 || $e->status === 403) {
        throw $e;
    }
    http_response_code($e->status >= 500 ? 503 : $e->status);
    $loadError = $e->getMessage();
} catch (UnexpectedValueException $e) {
    http_response_code(502);
    $loadError = $e->getMessage();
}

if ($loadError === null) {
    $numero = trim((string)($passport['numero'] ?? ''));
    $niveau = max(1, (int)$passport['niveau']);
    $points = max(0, (int)$passport['points_total']);
    $visitesNb = max(0, (int)($passport['visites'] ?? count($visits)));
    $badgesNb = max(0, (int)($passport['badges'] ?? 0));
}

$isBadgeEarned = static function (array $badge): bool {
    return !empty($badge['obtenu_at'])
        || filter_var($badge['obtenu'] ?? false, FILTER_VALIDATE_BOOLEAN);
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

  /* ===== Lien retour ===== */
  .passport-back {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    margin-bottom: 0.85rem;
    color: var(--c-primary-hover, #ea580c);
    font-size: 0.72rem;
    font-weight: 650;
    text-decoration: none;
    transition: color 0.15s ease;
  }
  .passport-back:hover { text-decoration: underline; }

  /* ===== Hero gradient ===== */
  .passport-hero {
    position: relative;
    isolation: isolate;
    overflow: hidden;
    margin-bottom: 1rem;
    padding: 1.25rem 1.15rem 1.1rem;
    border-radius: 14px;
    background: linear-gradient(135deg, #f97316, #ea580c 62%, #c2410c);
    color: #fff;
    box-shadow: 0 6px 20px -10px rgba(234, 88, 12, 0.35);
  }
  .passport-hero::after {
    position: absolute;
    z-index: -1;
    right: -2rem;
    top: -3rem;
    width: 10rem;
    height: 10rem;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.08);
    content: "";
  }
  .passport-number {
    margin: 0 0 0.25rem;
    color: rgba(255, 255, 255, 0.78);
    font-size: 0.65rem;
    letter-spacing: 0.08em;
  }
  .passport-level {
    margin: 0;
    color: #fff;
    font-size: clamp(1.2rem, 3vw, 1.6rem);
    font-weight: 800;
    letter-spacing: -0.4px;
  }
  .passport-points {
    margin: 0.3rem 0 0.85rem;
    color: rgba(255, 255, 255, 0.9);
    font-size: 0.82rem;
  }
  .passport-stats {
    display: flex;
    flex-wrap: wrap;
    gap: 1.25rem;
  }
  .passport-stat {
    display: grid;
    gap: 0.1rem;
  }
  .passport-stat-value {
    font-size: 1rem;
    font-weight: 800;
    line-height: 1.1;
  }
  .passport-stat-label {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.65rem;
  }

  /* ===== Sections ===== */
  .passport-section { margin: 0 0 1rem; }
  .passport-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.6rem;
    margin-bottom: 0.55rem;
    padding-bottom: 0.45rem;
    border-bottom: 1px solid var(--c-border, #f1e4d8);
  }
  .passport-section-title {
    margin: 0;
    color: var(--c-text, #1c1410);
    font-size: 0.85rem;
    font-weight: 800;
    letter-spacing: -0.2px;
  }
  .passport-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.4rem;
    padding: 0.1rem 0.45rem;
    border-radius: 999px;
    background: var(--c-primary-soft, rgba(249, 115, 22, 0.1));
    color: var(--c-primary-hover, #ea580c);
    font-size: 0.62rem;
    font-weight: 700;
  }

  /* ===== Badges ===== */
  .passport-badges {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(min(100%, 160px), 1fr));
    gap: 0.5rem;
  }
  .passport-badge {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    min-width: 0;
    padding: 0.65rem 0.7rem;
    border: 1px solid var(--c-border, #f1e4d8);
    border-radius: 10px;
    background: var(--c-surface, #fff);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
  }
  .passport-badge.is-earned {
    border-color: rgba(212, 175, 55, 0.55);
    background: rgba(212, 175, 55, 0.08);
    box-shadow: 0 2px 10px -4px rgba(212, 175, 55, 0.2);
  }
  .passport-badge-icon { flex: 0 0 auto; font-size: 1rem; }
  .passport-badge-copy { display: grid; min-width: 0; gap: 0.1rem; }
  .passport-badge-name {
    overflow: hidden;
    color: var(--c-text, #1c1410);
    font-size: 0.72rem;
    font-weight: 700;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .passport-badge-points {
    color: var(--c-muted, #806d63);
    font-size: 0.62rem;
  }

  /* ===== Visites ===== */
  .passport-visits { display: grid; gap: 0.45rem; }
  .passport-visit {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    min-width: 0;
    padding: 0.6rem 0.7rem;
    border: 1px solid var(--c-border, #f1e4d8);
    border-radius: 10px;
    background: var(--c-surface, #fff);
    color: inherit;
    text-decoration: none;
    transition: border-color 0.15s ease, transform 0.15s ease, box-shadow 0.15s ease;
  }
  .passport-visit:hover {
    border-color: #f6bd8b;
    transform: translateX(2px);
    box-shadow: 0 4px 14px -4px rgba(234, 88, 12, 0.18);
  }
  .passport-visit-icon {
    display: grid;
    place-items: center;
    flex: 0 0 2rem;
    width: 2rem;
    height: 2rem;
    border-radius: 8px;
    background: var(--c-primary-soft, rgba(249, 115, 22, 0.1));
    font-size: 0.85rem;
  }
  .passport-visit-copy {
    display: grid;
    flex: 1;
    min-width: 0;
    gap: 0.1rem;
  }
  .passport-visit-name,
  .passport-visit-location {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .passport-visit-name {
    font-size: 0.75rem;
    font-weight: 700;
  }
  .passport-visit-location,
  .passport-visit-date {
    color: var(--c-muted, #806d63);
    font-size: 0.62rem;
  }
  .passport-visit-date { flex: 0 0 auto; }

  /* ===== États vides / erreur ===== */
  .passport-empty,
  .passport-error {
    padding: 1rem;
    border: 1px solid var(--c-border, #f1e4d8);
    border-radius: 10px;
    background: var(--c-surface, #fff);
    font-size: 0.72rem;
    line-height: 1.5;
  }
  .passport-empty { color: var(--c-muted, #806d63); }
  .passport-error {
    border-color: #fecaca;
    background: #fff7f7;
    color: #991b1b;
  }
  .passport-error h2 {
    margin: 0 0 0.35rem;
    font-size: 0.85rem;
  }
  .passport-error p {
    margin: 0 0 0.7rem;
    font-size: 0.72rem;
  }
  .passport-retry {
    display: inline-flex;
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
    background: var(--c-primary, #f97316);
    color: #fff;
    font-size: 0.7rem;
    font-weight: 700;
    text-decoration: none;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    box-shadow: 0 3px 10px -2px rgba(249, 115, 22, 0.3);
  }
  .passport-retry:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px -2px rgba(234, 88, 12, 0.4);
  }

  /* ===== Responsive ===== */
  @media (max-width: 520px) {
    .passport-hero { padding: 1rem 0.9rem 0.9rem; }
    .passport-visit { flex-wrap: wrap; }
    .passport-visit-date { margin-left: 2.65rem; }
  }
  @media (prefers-reduced-motion: reduce) {
    *, .passport-visit, .passport-badge, .passport-retry { transition: none !important; }
  }
</style>


<?php if ($loadError !== null): ?>
  <section class="passport-error" role="alert">
    <h2>Le passeport n’a pas pu être chargé</h2>
    <p data-client-alert data-client-alert-type="error"><?= client_h($loadError) ?></p>
    <a class="passport-retry" href="?page=passport">Réessayer</a>
  </section>
<?php else: ?>
  <section class="passport-hero" aria-labelledby="passportLevel">
    <?php if ($numero !== ''): ?>
      <p class="passport-number">Passeport <?= client_h($numero) ?></p>
    <?php endif; ?>
    <h2 class="passport-level" id="passportLevel">Niveau <?= $niveau ?></h2>
    <p class="passport-points"><?= number_format($points, 0, ',', ' ') ?> points</p>
    <div class="passport-stats">
      <div class="passport-stat">
        <span class="passport-stat-value"><?= $visitesNb ?></span>
        <span class="passport-stat-label">Visites</span>
      </div>
      <div class="passport-stat">
        <span class="passport-stat-value"><?= $badgesNb ?></span>
        <span class="passport-stat-label">Badges obtenus</span>
      </div>
    </div>
  </section>

  <section class="passport-section" aria-labelledby="passportBadgesTitle">
    <header class="passport-section-header">
      <h2 class="passport-section-title" id="passportBadgesTitle">Badges</h2>
      <span class="passport-count"><?= count($badges) ?></span>
    </header>
    <?php if ($badges): ?>
      <div class="passport-badges">
        <?php foreach ($badges as $badge): ?>
          <?php
            $earned = $isBadgeEarned($badge);
            $badgeName = trim((string)($badge['nom'] ?? ''));
            if ($badgeName === '') $badgeName = 'Badge';
            $badgePoints = max(0, (int)($badge['points'] ?? 0));
          ?>
          <article class="passport-badge <?= $earned ? 'is-earned' : '' ?>">
            <span class="passport-badge-icon" aria-hidden="true"><?= $earned ? '🏅' : '🔒' ?></span>
            <span class="passport-badge-copy">
              <span class="passport-badge-name"><?= client_h($badgeName) ?></span>
              <span class="passport-badge-points"><?= $badgePoints ?> points</span>
            </span>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="passport-empty">Aucun badge n’est disponible pour le moment.</p>
    <?php endif; ?>
  </section>

  <section class="passport-section" aria-labelledby="passportVisitsTitle">
    <header class="passport-section-header">
      <h2 class="passport-section-title" id="passportVisitsTitle">Historique des visites</h2>
      <span class="passport-count"><?= count($visits) ?></span>
    </header>
    <?php if ($visits): ?>
      <div class="passport-visits">
        <?php foreach ($visits as $visit): ?>
          <?php
            $destinationId = filter_var($visit['destination_id'] ?? null, FILTER_VALIDATE_INT);
            $visitName = trim((string)($visit['nom'] ?? ''));
            if ($visitName === '') $visitName = 'Destination visitée';
            $hasLink = $destinationId !== false && $destinationId !== null && $destinationId > 0;
          ?>
          <?php if ($hasLink): ?>
            <a class="passport-visit" href="?page=destination&amp;id=<?= $destinationId ?>">
          <?php else: ?>
            <article class="passport-visit">
          <?php endif; ?>
              <span class="passport-visit-icon" aria-hidden="true">📍</span>
              <span class="passport-visit-copy">
                <span class="passport-visit-name"><?= client_h($visitName) ?></span>
                <span class="passport-visit-location"><?= client_h($visit['localite'] ?? '') ?></span>
              </span>
              <span class="passport-visit-date"><?= client_h($visit['visite_le'] ?? '') ?></span>
          <?php if ($hasLink): ?>
            </a>
          <?php else: ?>
            </article>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="passport-empty">Aucune visite enregistrée. Consultez une destination et indiquez que vous l’avez visitée.</p>
    <?php endif; ?>
  </section>
<?php endif; ?>