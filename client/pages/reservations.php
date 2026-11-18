<?php
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($id && $id > 0) {
    $r = ClientApi::data($api->request('GET', 'api/reservations/' . $id));
    ?>
<section class="panel res-detail">
  <a class="text-link back-link" href="?page=reservations">← Toutes mes réservations</a>

  <header class="res-head">
    <h2 class="section-title">
      Réservation <?= client_h($r['reference'] ?? '') ?>
      <?= client_status_badge((string)($r['statut'] ?? '')) ?>
    </h2>
  </header>

  <div class="res-stats">
    <div class="res-stat">
      <div class="stat-label">Itinéraire</div>
      <div class="stat-value-sm"><?= client_h($r['libelle_snapshot'] ?? '—') ?></div>
    </div>
    <div class="res-stat">
      <div class="stat-label">Date de départ</div>
      <div class="stat-value-sm"><?= client_h(client_format_date($r['depart_at'] ?? null)) ?></div>
    </div>
    <div class="res-stat">
      <div class="stat-label">Participants</div>
      <div class="stat-value-sm"><?= (int)($r['quantite'] ?? 1) ?></div>
    </div>
    <div class="res-stat">
      <div class="stat-label">Montant estimé</div>
      <div class="stat-value-sm accent"><?= client_money($r['montant_total'] ?? null, $r['devise'] ?? null) ?></div>
    </div>
  </div>

  <p class="res-note">
    Demande créée le <?= client_h(client_format_date($r['created_at'] ?? null)) ?>.
    La disponibilité et les détails sont confirmés par le transporteur.
  </p>

  <?php if (($r['statut'] ?? '') === 'demande'): ?>
    <form method="post" data-confirm="Annuler cette demande de réservation ?" class="res-actions">
      <?= client_csrf_field() ?>
      <input type="hidden" name="action" value="reservation_cancel">
      <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
      <button class="btn btn-outline" type="submit">Annuler la demande</button>
    </form>
  <?php endif; ?>
</section>

<style>
  *,
  *::before,
  *::after {
    box-sizing: border-box;
  }

  .res-detail {
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 14px;
    padding: 1.2rem 1.2rem 1rem;
    box-shadow: 0 4px 18px -6px rgba(234, 88, 12, 0.15);
    max-width: 720px;
    width: 100%;
    min-width: 0;
    overflow: hidden;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  .back-link {
    display: inline-block;
    font-size: 0.75rem;
    font-weight: 600;
    color: #f97316;
    text-decoration: none;
    margin-bottom: 0.85rem;
    transition: color 0.2s ease;
  }

  .back-link:hover { color: #ea580c; }

  .res-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding-bottom: 0.85rem;
    margin-bottom: 1rem;
    border-bottom: 1px solid #f3ebe4;
    flex-wrap: wrap;
  }

  .res-head .section-title {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    font-size: 1rem;
    font-weight: 700;
    color: #1c1410;
    margin: 0;
    flex-wrap: wrap;
  }

  .res-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.85rem;
    margin-bottom: 1rem;
    width: 100%;
    max-width: 100%;
  }

  .res-stat {
    padding: 0.75rem 0.85rem;
    background: #fff8f3;
    border: 1px solid #f1e4d8;
    border-radius: 10px;
    min-width: 0;
    overflow: hidden;
  }

  .stat-label {
    font-size: 0.65rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #8a7266;
    margin-bottom: 0.25rem;
  }

  .stat-value-sm {
    font-size: 0.85rem;
    font-weight: 700;
    color: #1c1410;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .stat-value-sm.accent { color: #ea580c; }

  .res-note {
    font-size: 0.75rem;
    color: #8a7266;
    line-height: 1.5;
    margin: 0 0 1rem;
  }

  .res-actions {
    margin-top: 0.5rem;
    padding-top: 0.85rem;
    border-top: 1px solid #f3ebe4;
  }

  .btn-outline {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.55rem 1.1rem;
    font-size: 0.78rem;
    font-weight: 600;
    font-family: inherit;
    color: #ea580c;
    background: transparent;
    border: 1.5px solid #f97316;
    border-radius: 9px;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .btn-outline:hover {
    background: rgba(249, 115, 22, 0.08);
    transform: translateY(-1px);
  }

  @media (max-width: 768px) {
    .res-stats {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }

  @media (max-width: 520px) {
    .res-detail { padding: 1rem 0.9rem 0.85rem; }
    .res-stats { grid-template-columns: 1fr; gap: 0.6rem; }
    .stat-value-sm { font-size: 0.8rem; }
  }
</style>

<?php
} else {
    $rows = ClientApi::data($api->request('GET', 'api/reservations'));
    ?>
<section class="panel res-list">
  <header class="panel-head">
    <h2 class="section-title">Toutes vos réservations</h2>
  </header>

  <?php if (!$rows): ?>
    <div class="empty">
      <div class="empty-icon">▤</div>
      <h3>Aucune réservation pour le moment</h3>
      <p>Vos demandes de transport apparaîtront dès leur enregistrement.</p>
      <a class="btn" href="?page=help">Comment réserver ?</a>
    </div>
  <?php else: ?>
    <div class="res-cards">
      <?php foreach ($rows as $r): ?>
        <a class="res-card" href="?page=reservations&amp;id=<?= (int)$r['id'] ?>">
          <div class="res-card-head">
            <div class="res-card-ref"><?= client_h($r['reference'] ?? '') ?></div>
            <?= client_status_badge((string)($r['statut'] ?? '')) ?>
          </div>

          <div class="res-card-route">
            <?= client_h($r['libelle_snapshot'] ?? '—') ?>
          </div>

          <div class="res-card-meta">
            <div class="res-card-meta-item">
              <span class="meta-label">Date</span>
              <span class="meta-value"><?= client_h(client_format_date($r['created_at'] ?? null)) ?></span>
            </div>
            <div class="res-card-meta-item">
              <span class="meta-label">Montant</span>
              <span class="meta-value accent"><?= client_money($r['montant_total'] ?? null, $r['devise'] ?? null) ?></span>
            </div>
          </div>

          <div class="res-card-foot">
            <span>Voir les détails →</span>
          </div>
        </a>
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

  .res-list {
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

  .res-list .panel-head {
    margin-bottom: 0.9rem;
    padding-bottom: 0.65rem;
    border-bottom: 1px solid #f3ebe4;
  }

  .res-list .section-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: #1c1410;
    margin: 0;
  }

  .res-cards {
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
    width: 100%;
    max-width: 100%;
  }

  .res-card {
    display: block;
    padding: 0.9rem 0.95rem;
    background: #fff8f3;
    border: 1px solid #f1e4d8;
    border-radius: 12px;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s ease;
    min-width: 0;
    overflow: hidden;
  }

  .res-card:hover {
    background: #fff;
    border-color: #fcd9b5;
    transform: translateY(-1px);
    box-shadow: 0 6px 16px -6px rgba(234, 88, 12, 0.25);
  }

  .res-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.6rem;
    margin-bottom: 0.35rem;
  }

  .res-card-ref {
    font-size: 0.78rem;
    font-weight: 700;
    color: #1c1410;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .res-card-route {
    font-size: 0.78rem;
    color: #8a7266;
    margin-bottom: 0.6rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .res-card-meta {
    display: flex;
    gap: 1.2rem;
    padding: 0.5rem 0;
    border-top: 1px dashed #f1e4d8;
    border-bottom: 1px dashed #f1e4d8;
    margin-bottom: 0.5rem;
    flex-wrap: wrap;
  }

  .res-card-meta-item {
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
    min-width: 0;
  }

  .meta-label {
    font-size: 0.6rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #8a7266;
  }

  .meta-value {
    font-size: 0.75rem;
    font-weight: 600;
    color: #1c1410;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .meta-value.accent { color: #ea580c; }

  .res-card-foot {
    font-size: 0.68rem;
    font-weight: 600;
    color: #f97316;
    text-align: right;
  }

  .empty {
    text-align: center;
    padding: 1.2rem 0.75rem;
  }

  .empty-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    margin-bottom: 0.6rem;
    background: rgba(249, 115, 22, 0.10);
    color: #ea580c;
    border-radius: 50%;
    font-size: 1.15rem;
  }

  .empty h3 {
    font-size: 0.88rem;
    font-weight: 700;
    color: #1c1410;
    margin: 0 0 0.3rem;
  }

  .empty p {
    font-size: 0.78rem;
    color: #8a7266;
    margin: 0 0 0.8rem;
    line-height: 1.4;
  }

  .empty .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.5rem 1rem;
    background: linear-gradient(135deg, #fb923c, #ea580c);
    color: #fff;
    font-size: 0.78rem;
    font-weight: 600;
    border: none;
    border-radius: 8px;
    text-decoration: none;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(249, 115, 22, 0.35);
    transition: all 0.2s ease;
  }

  .empty .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(234, 88, 12, 0.45);
  }

  @media (max-width: 520px) {
    .res-list { padding: 0.95rem 0.85rem 0.8rem; }
    .res-card { padding: 0.8rem 0.85rem; }
  }
</style>

<?php
}