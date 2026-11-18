<?php $rows = ClientApi::data($api->request('GET', 'api/reservations')); ?>

<section class="panel pay-panel">
  <div class="pay-notice">
    <div class="pay-notice-icon">ⓘ</div>
    <div class="pay-notice-body">
      <div class="pay-notice-title">Les paiements en ligne ne sont pas encore intégrés.</div>
      <p class="pay-notice-text">
        Les montants ci-dessous sont associés à vos réservations.
        Ils ne constituent pas une confirmation de paiement.
      </p>
    </div>
  </div>

  <header class="panel-head">
    <h2 class="section-title">Montants liés aux réservations</h2>
  </header>

  <?php if (!$rows): ?>
    <div class="empty">
      <div class="empty-icon">₣</div>
      <h3>Aucun montant enregistré</h3>
      <p>Une réservation avec un tarif défini apparaîtra ici.</p>
    </div>
  <?php else: ?>
    <div class="pay-cards">
      <?php foreach ($rows as $r): ?>
        <?php $isCancelled = ($r['statut'] ?? '') === 'annulee'; ?>
        <div class="pay-card">
          <div class="pay-card-head">
            <div class="pay-card-ref"><?= client_h($r['reference'] ?? '') ?></div>
            <span class="badge <?= $isCancelled ? 'muted' : 'pending' ?>">
              <?= $isCancelled ? 'Annulée' : 'En attente' ?>
            </span>
          </div>

          <div class="pay-card-route"><?= client_h($r['libelle_snapshot'] ?? '—') ?></div>

          <div class="pay-card-footer">
            <div class="pay-card-meta">
              <?= client_h(client_format_date($r['created_at'] ?? null)) ?>
            </div>
            <div class="pay-card-amount">
              <?= client_money($r['montant_total'] ?? null, $r['devise'] ?? null) ?>
            </div>
          </div>

          <div class="pay-card-status">
            <?= $isCancelled ? 'Réservation annulée' : 'Paiement non disponible' ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<style>
  *,
  *::before,
  *::after {
    box-sizing: border-box;
  }

  .pay-panel {
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 14px;
    padding: 1.1rem 1.1rem 0.9rem;
    box-shadow: 0 4px 18px -6px rgba(234, 88, 12, 0.15);
    width: 100%;
    max-width: 100%;
    min-width: 0;
    overflow: hidden;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  .pay-notice {
    display: flex;
    align-items: flex-start;
    gap: 0.6rem;
    padding: 0.75rem 0.85rem;
    margin-bottom: 1rem;
    background: #fff8f3;
    border: 1px solid #f1e4d8;
    border-left: 3px solid #f97316;
    border-radius: 10px;
    min-width: 0;
  }

  .pay-notice-icon {
    flex-shrink: 0;
    width: 22px;
    height: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(249, 115, 22, 0.12);
    color: #ea580c;
    border-radius: 50%;
    font-size: 0.75rem;
    font-weight: 700;
  }

  .pay-notice-body { min-width: 0; flex: 1; }

  .pay-notice-title {
    font-size: 0.78rem;
    font-weight: 700;
    color: #1c1410;
    margin-bottom: 0.15rem;
  }

  .pay-notice-text {
    font-size: 0.72rem;
    color: #8a7266;
    line-height: 1.4;
    margin: 0;
  }

  .pay-panel .panel-head {
    margin-bottom: 0.85rem;
    padding-bottom: 0.65rem;
    border-bottom: 1px solid #f3ebe4;
  }

  .pay-panel .section-title {
    font-size: 0.92rem;
    font-weight: 700;
    color: #1c1410;
    margin: 0;
  }

  .pay-cards {
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
    width: 100%;
    max-width: 100%;
  }

  .pay-card {
    padding: 0.85rem 0.9rem;
    background: #fff8f3;
    border: 1px solid #f1e4d8;
    border-radius: 12px;
    transition: all 0.2s ease;
    min-width: 0;
    overflow: hidden;
  }

  .pay-card:hover {
    background: #fff;
    border-color: #fcd9b5;
    box-shadow: 0 4px 12px -4px rgba(234, 88, 12, 0.20);
  }

  .pay-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.6rem;
    margin-bottom: 0.35rem;
  }

  .pay-card-ref {
    font-size: 0.78rem;
    font-weight: 700;
    color: #1c1410;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    min-width: 0;
  }

  .pay-card-route {
    font-size: 0.72rem;
    color: #8a7266;
    margin-bottom: 0.55rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .pay-card-footer {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 0.6rem;
    padding-top: 0.5rem;
    border-top: 1px dashed #f1e4d8;
  }

  .pay-card-meta {
    font-size: 0.68rem;
    color: #8a7266;
    white-space: nowrap;
  }

  .pay-card-amount {
    font-size: 0.95rem;
    font-weight: 800;
    color: #ea580c;
    letter-spacing: -0.01em;
    white-space: nowrap;
  }

  .pay-card-status {
    margin-top: 0.5rem;
    font-size: 0.65rem;
    font-weight: 600;
    color: #8a7266;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }

  .badge {
    display: inline-flex;
    align-items: center;
    padding: 0.18rem 0.5rem;
    font-size: 0.6rem;
    font-weight: 700;
    border-radius: 999px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    white-space: nowrap;
    flex-shrink: 0;
  }

  .badge.pending {
    background: rgba(249, 115, 22, 0.10);
    color: #ea580c;
  }

  .badge.muted {
    background: #f3f4f6;
    color: #6b7280;
  }

  .empty {
    text-align: center;
    padding: 1.1rem 0.75rem;
  }

  .empty-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    margin-bottom: 0.55rem;
    background: rgba(249, 115, 22, 0.10);
    color: #ea580c;
    border-radius: 50%;
    font-size: 1.1rem;
  }

  .empty h3 {
    font-size: 0.85rem;
    font-weight: 700;
    color: #1c1410;
    margin: 0 0 0.3rem;
  }

  .empty p {
    font-size: 0.75rem;
    color: #8a7266;
    margin: 0;
    line-height: 1.4;
  }

  @media (max-width: 520px) {
    .pay-panel { padding: 0.95rem 0.85rem 0.8rem; }
    .pay-card { padding: 0.75rem 0.8rem; }
    .pay-card-amount { font-size: 0.88rem; }
    .pay-card-meta { font-size: 0.65rem; }
  }
</style>