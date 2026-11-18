<?php
$query = trim((string)($_GET['q'] ?? ''));
$args = ['page' => max(1, (int)($_GET['p'] ?? 1)), 'per_page' => 24];
if ($query !== '') $args['q'] = $query;
if (!empty($_GET['categorie'])) $args['categorie'] = trim((string)$_GET['categorie']);
$payload = ClientApi::data($api->request('GET', 'api/destinations?' . http_build_query($args)));
$items = is_array($payload['items'] ?? null) ? $payload['items'] : [];
$categories = ClientApi::data($api->request('GET', 'api/categories'));
$categories = is_array($categories) ? $categories : [];
$selectedCat = (string)($_GET['categorie'] ?? '');
?>

<section class="panel explore-search">
  <form method="get" class="search-form">
    <input type="hidden" name="page" value="explore">

    <div class="search-field">
      <label for="q">Rechercher</label>
      <input id="q" name="q" value="<?= client_h($query) ?>"
             maxlength="120"
             placeholder="Nom, région ou activité">
    </div>

    <div class="search-field">
      <label for="categorie">Catégorie</label>
      <select id="categorie" name="categorie">
        <option value="">Toutes</option>
        <?php foreach ($categories as $category): ?>
          <?php $catValue = (string)($category['slug'] ?? $category['id'] ?? ''); ?>
          <option value="<?= client_h($catValue) ?>" <?= $selectedCat === $catValue ? 'selected' : '' ?>>
            <?= client_h($category['nom'] ?? '') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <button class="btn-search" type="submit">Rechercher</button>
  </form>
</section>

<?php if (!$items): ?>
  <section class="panel explore-empty">
    <div class="empty">
      <div class="empty-icon">✦</div>
      <h3>Aucune destination trouvée</h3>
      <p>Essayez une autre recherche ou catégorie.</p>
    </div>
  </section>
<?php else: ?>
  <section class="explore-grid">
    <?php foreach ($items as $item): ?>
      <article class="explore-card">
        <?php if (!empty($item['image_principale'])): ?>
          <div class="explore-card-img">
            <img src="<?= client_h($item['image_principale']) ?>"
                 alt="<?= client_h($item['nom'] ?? '') ?>"
                 loading="lazy">
          </div>
        <?php endif; ?>

        <div class="explore-card-body">
          <span class="explore-eyebrow">
            <?= client_h($item['localite'] ?? '') ?> · <?= client_h($item['categorie'] ?? '') ?>
          </span>
          <h2 class="explore-title"><?= client_h($item['nom'] ?? '') ?></h2>
          <p class="explore-desc">
            <?= client_h(mb_strimwidth((string)($item['description'] ?? ''), 0, 140, '…')) ?>
          </p>
          <a class="explore-cta" href="?page=destination&amp;id=<?= (int)$item['id'] ?>">
            Découvrir →
          </a>
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

  .explore-search {
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

  .search-form {
    display: grid;
    grid-template-columns: 1fr 200px auto;
    gap: 0.75rem;
    align-items: end;
    width: 100%;
    max-width: 100%;
  }

  .search-field {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    min-width: 0;
  }

  .search-field label {
    font-size: 0.72rem;
    font-weight: 600;
    color: #1c1410;
  }

  .search-field input,
  .search-field select {
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

  .search-field input::placeholder { color: #d8bfa8; }

  .search-field input:hover,
  .search-field select:hover {
    border-color: #e8d0bd;
    background: #fff;
  }

  .search-field input:focus,
  .search-field select:focus {
    border-color: #f97316;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.10);
  }

  .btn-search {
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

  .btn-search:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(234, 88, 12, 0.40);
  }

  .explore-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 0.85rem;
    width: 100%;
    max-width: 100%;
    min-width: 0;
  }

  .explore-card {
    display: flex;
    flex-direction: column;
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 14px;
    overflow: hidden;
    transition: all 0.25s ease;
    min-width: 0;
  }

  .explore-card:hover {
    border-color: #fcd9b5;
    box-shadow: 0 8px 22px -8px rgba(234, 88, 12, 0.25);
    transform: translateY(-2px);
  }

  .explore-card-img {
    width: 100%;
    aspect-ratio: 16 / 10;
    background: #f1e4d8;
    overflow: hidden;
  }

  .explore-card-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.4s ease;
  }

  .explore-card:hover .explore-card-img img {
    transform: scale(1.05);
  }

  .explore-card-body {
    padding: 0.85rem 0.9rem 0.95rem;
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
  }

  .explore-eyebrow {
    font-size: 0.62rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #f97316;
    margin-bottom: 0.3rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .explore-title {
    font-size: 0.92rem;
    font-weight: 700;
    color: #1c1410;
    margin: 0 0 0.35rem;
    line-height: 1.25;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
  }

  .explore-desc {
    font-size: 0.72rem;
    color: #8a7266;
    line-height: 1.45;
    margin: 0 0 0.8rem;
    flex: 1;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
  }

  .explore-cta {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.5rem 0.9rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: #fff;
    background: linear-gradient(135deg, #fb923c, #ea580c);
    border-radius: 8px;
    text-decoration: none;
    box-shadow: 0 3px 10px rgba(249, 115, 22, 0.30);
    transition: all 0.2s ease;
    margin-top: auto;
  }

  .explore-cta:hover {
    transform: translateY(-1px);
    box-shadow: 0 5px 14px rgba(234, 88, 12, 0.40);
  }

  .explore-empty {
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

  @media (max-width: 900px) {
    .search-form {
      grid-template-columns: 1fr 1fr;
    }

    .btn-search {
      grid-column: 1 / -1;
    }
  }

  @media (max-width: 640px) {
    .search-form {
      grid-template-columns: 1fr;
    }

    .explore-grid {
      grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
      gap: 0.7rem;
    }

    .explore-card-body { padding: 0.7rem 0.75rem 0.8rem; }
    .explore-title { font-size: 0.85rem; }
    .explore-desc { font-size: 0.68rem; -webkit-line-clamp: 2; }
  }

  @media (max-width: 380px) {
    .explore-grid {
      grid-template-columns: 1fr;
    }
  }
</style>