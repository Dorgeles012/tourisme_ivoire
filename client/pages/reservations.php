<?php
if (!$user) {
    header('Location: ?page=login');
    exit;
}

try {
    $reservations = ClientApi::data($api->request('GET', 'api/reservations'));
    $reservations = is_array($reservations) ? $reservations : [];
} catch (Throwable $e) {
    echo '<section class="panel empty-panel" role="alert"><div class="empty">'
       . '<div class="empty-icon">❌</div>'
       . '<h3>Erreur de chargement</h3>'
       . '<p data-client-alert data-client-alert-type="error">' . client_h(readableError($e)) . '</p>'
       . '<a class="btn-go" href="?page=reservations">Réessayer</a>'
       . '</div></section>';
    return;
}

// ===== Helper couleur statut =====
$statusColor = static function (?string $status): string {
    return match (mb_strtolower((string)$status)) {
        'confirmee', 'confirmé', 'confirmée' => '#2E7D32',
        'annulee', 'annulé', 'annulée'       => '#c0392b',
        default                              => '#d4a017',
    };
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

  /* ===== Header ===== */
  .page-header {
    display: flex;
    align-items: flex-start;
    gap: 0.6rem;
    margin-bottom: 0.85rem;
  }
  .back-link {
    flex-shrink: 0;
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
    margin-top: 0.2rem;
  }
  .back-link:hover { background: #fff8f3; color: #ea580c; }
  .page-header-text { flex: 1; min-width: 0; }
  .page-title {
    font-size: 1.15rem;
    font-weight: 900;
    color: #1c1410;
    margin: 0 0 0.15rem;
    letter-spacing: -0.3px;
  }
  .page-subtitle {
    font-size: 0.68rem;
    color: #8a7266;
    margin: 0;
  }

  /* ===== Liste réservations ===== */
  .reservations-list {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
  }
  .reservation-card {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    padding: 0.7rem 0.8rem;
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 10px;
    box-shadow: 0 2px 10px -4px rgba(234, 88, 12, 0.10);
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
  }
  .reservation-card:hover {
    border-color: #fcd9b5;
    box-shadow: 0 4px 14px -4px rgba(234, 88, 12, 0.18);
  }
  .reservation-icon {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    background: color-mix(in srgb, var(--status-color) 10%, transparent);
    border-radius: 8px;
    font-size: 0.95rem;
  }
  .reservation-body {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
  }
  .reservation-title {
    font-size: 0.8rem;
    font-weight: 700;
    color: #1c1410;
    margin: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .reservation-meta {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.65rem;
    color: #8a7266;
  }
  .reservation-status {
    padding: 0.08rem 0.4rem;
    font-size: 0.58rem;
    font-weight: 800;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    color: var(--status-color);
    background: color-mix(in srgb, var(--status-color) 10%, transparent);
    border-radius: 4px;
  }
  .reservation-ref {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .reservation-amount {
    flex-shrink: 0;
    text-align: right;
  }
  .reservation-amount-pending {
    font-size: 0.65rem;
    font-weight: 600;
    color: #8a7266;
    font-style: italic;
  }
  .reservation-amount-value {
    font-size: 0.75rem;
    font-weight: 800;
    color: #1c1410;
  }

  /* ===== États vides ===== */
  .panel, .empty-panel {
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 12px;
    padding: 1.15rem 0.85rem;
    box-shadow: 0 2px 12px -4px rgba(234, 88, 12, 0.10);
  }
  .empty { text-align: center; padding: 0.75rem 0.4rem; }
  .empty-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    margin-bottom: 0.5rem;
    background: rgba(249, 115, 22, 0.10);
    border-radius: 50%;
    font-size: 1.15rem;
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
    margin: 0 0 0.75rem;
    line-height: 1.4;
  }
  .btn-go {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.5rem 0.9rem;
    font-size: 0.72rem;
    font-weight: 700;
    color: #fff;
    background: linear-gradient(135deg, #fb923c, #ea580c);
    border-radius: 8px;
    text-decoration: none;
    box-shadow: 0 3px 10px -2px rgba(249, 115, 22, 0.3);
    transition: transform 0.15s ease;
  }
  .btn-go:hover { transform: translateY(-1px); }

  /* ===== Responsive ===== */
  @media (max-width: 520px) {
    .page-title { font-size: 1rem; }
    .reservation-card { padding: 0.6rem 0.7rem; gap: 0.55rem; }
    .reservation-icon { width: 32px; height: 32px; font-size: 0.85rem; }
    .reservation-title { font-size: 0.75rem; }
  }
  @media (prefers-reduced-motion: reduce) {
    *, .reservation-card, .back-link { transition: none !important; }
  }
</style>

<header class="page-header">
  <div class="page-header-text">
    <p class="page-subtitle"><?= count($reservations) ?> demande<?= count($reservations) > 1 ? 's' : '' ?></p>
  </div>
</header>

<?php if (!$reservations): ?>
  <section class="panel empty-panel">
    <div class="empty">
      <div class="empty-icon">🧾</div>
      <h3>Aucune demande de réservation</h3>
      <p>Vos demandes de réservation apparaîtront ici après leur envoi.</p>
      <a class="btn-go" href="?page=transport">Rechercher un trajet</a>
    </div>
  </section>
<?php else: ?>
  <section class="reservations-list">
    <?php foreach ($reservations as $booking): ?>
      <?php
        $statut    = (string)($booking['statut'] ?? '');
        $color     = $statusColor($statut);
        $reference = (string)($booking['reference'] ?? '');
        $libelle   = (string)($booking['libelle_snapshot'] ?? $reference);
        $montant   = $booking['montant_total'] ?? null;
        $devise    = (string)($booking['devise'] ?? '');
      ?>
      <article class="reservation-card" style="--status-color: <?= $color ?>;">
        <span class="reservation-icon" aria-hidden="true">🎫</span>
        <div class="reservation-body">
          <h2 class="reservation-title"><?= client_h($libelle) ?></h2>
          <div class="reservation-meta">
            <span class="reservation-status"><?= client_h($statut) ?></span>
            <span class="reservation-ref"><?= client_h($reference) ?></span>
          </div>
        </div>
        <div class="reservation-amount">
          <?php if ($montant === null): ?>
            <span class="reservation-amount-pending">À confirmer</span>
          <?php else: ?>
            <span class="reservation-amount-value"><?= client_h((string)$montant) ?> <?= client_h($devise) ?></span>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </section>
<?php endif; ?>