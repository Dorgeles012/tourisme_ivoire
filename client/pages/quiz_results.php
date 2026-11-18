<?php
$results = ClientApi::data($api->request('GET', 'api/quizzes/results'));
$last = $_SESSION['last_quiz_result'] ?? null;
unset($_SESSION['last_quiz_result']);
?>

<?php if (is_array($last)): ?>
  <section class="panel quiz-last">
    <header class="quiz-last-head">
      <span class="quiz-last-eyebrow">Votre dernier résultat</span>
      <div class="quiz-last-score">
        <span class="quiz-last-number"><?= (int)($last['score'] ?? 0) ?></span>
        <span class="quiz-last-unit">points</span>
      </div>
      <p class="quiz-last-summary">
        <?= (int)($last['bonnes_reponses'] ?? 0) ?> bonne(s) réponse(s)
        sur <?= (int)($last['total_questions'] ?? 0) ?>
      </p>
    </header>

    <?php if (!empty($last['corrections'])): ?>
      <div class="quiz-corrections">
        <?php foreach ($last['corrections'] as $correction): ?>
          <div class="quiz-correction">
            <div class="quiz-correction-question">
              <?= client_h($correction['question'] ?? '') ?>
            </div>
            <div class="quiz-correction-answers">
              <div class="quiz-answer user">
                <span class="quiz-answer-label">Votre réponse</span>
                <span class="quiz-answer-value"><?= client_h($correction['votre_reponse'] ?? '') ?></span>
              </div>
              <div class="quiz-answer correct">
                <span class="quiz-answer-label">Bonne réponse</span>
                <span class="quiz-answer-value"><?= client_h($correction['bonne_reponse'] ?? '') ?></span>
              </div>
            </div>
            <?php if (!empty($correction['explication'])): ?>
              <div class="quiz-correction-explanation">
                <?= client_h($correction['explication']) ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
<?php endif; ?>

<section class="panel quiz-history">
  <header class="panel-head">
    <h2 class="section-title">Historique des quiz</h2>
  </header>

  <?php if (is_array($results) && $results): ?>
    <div class="quiz-list">
      <?php foreach ($results as $result): ?>
        <div class="quiz-row">
          <div class="quiz-row-content">
            <div class="quiz-row-title"><?= client_h($result['quiz'] ?? '') ?></div>
            <div class="quiz-row-meta"><?= client_h($result['termine_at'] ?? '') ?></div>
          </div>
          <div class="quiz-row-score">
            <span class="quiz-score-ratio">
              <?= (int)$result['bonnes_reponses'] ?>/<?= (int)$result['total_questions'] ?>
            </span>
            <span class="quiz-score-points"><?= (int)$result['score'] ?> pts</span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="empty">
      <div class="empty-icon">◷</div>
      <h3>Aucun résultat pour le moment</h3>
      <p>Vos scores apparaîtront ici après avoir terminé un quiz.</p>
    </div>
  <?php endif; ?>

  <div class="quiz-history-foot">
    <a class="quiz-cta" href="?page=quizzes">Découvrir les quiz →</a>
  </div>
</section>

<style>
  *,
  *::before,
  *::after {
    box-sizing: border-box;
  }

  .quiz-last,
  .quiz-history {
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 14px;
    padding: 1.1rem 1.1rem 0.95rem;
    box-shadow: 0 4px 18px -6px rgba(234, 88, 12, 0.15);
    width: 100%;
    max-width: 100%;
    min-width: 0;
    overflow: hidden;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  .quiz-last {
    margin-bottom: 1rem;
    background: linear-gradient(135deg, #fff 0%, #fff8f3 100%);
  }

  .quiz-last-head {
    text-align: center;
    padding-bottom: 1rem;
    margin-bottom: 1rem;
    border-bottom: 1px dashed #f1e4d8;
  }

  .quiz-last-eyebrow {
    display: inline-block;
    font-size: 0.62rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: #f97316;
    margin-bottom: 0.5rem;
  }

  .quiz-last-score {
    display: inline-flex;
    align-items: baseline;
    gap: 0.35rem;
    margin-bottom: 0.3rem;
  }

  .quiz-last-number {
    font-size: 2.4rem;
    font-weight: 800;
    line-height: 1;
    background: linear-gradient(135deg, #fb923c, #ea580c);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }

  .quiz-last-unit {
    font-size: 0.85rem;
    font-weight: 600;
    color: #8a7266;
  }

  .quiz-last-summary {
    font-size: 0.78rem;
    color: #8a7266;
    margin: 0;
  }

  .quiz-corrections {
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
  }

  .quiz-correction {
    padding: 0.8rem 0.85rem;
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 10px;
    min-width: 0;
    overflow: hidden;
  }

  .quiz-correction-question {
    font-size: 0.8rem;
    font-weight: 700;
    color: #1c1410;
    margin-bottom: 0.55rem;
    overflow-wrap: break-word;
    word-wrap: break-word;
  }

  .quiz-correction-answers {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
    margin-bottom: 0.55rem;
  }

  .quiz-answer {
    padding: 0.5rem 0.6rem;
    border-radius: 8px;
    min-width: 0;
    overflow: hidden;
  }

  .quiz-answer.user {
    background: #fef2f2;
    border: 1px solid #fecaca;
  }

  .quiz-answer.correct {
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
  }

  .quiz-answer-label {
    display: block;
    font-size: 0.6rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.15rem;
  }

  .quiz-answer.user .quiz-answer-label { color: #b91c1c; }
  .quiz-answer.correct .quiz-answer-label { color: #047857; }

  .quiz-answer-value {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: #1c1410;
    overflow-wrap: break-word;
    word-wrap: break-word;
  }

  .quiz-correction-explanation {
    font-size: 0.7rem;
    color: #8a7266;
    line-height: 1.5;
    padding-top: 0.55rem;
    border-top: 1px dashed #f1e4d8;
    overflow-wrap: break-word;
    word-wrap: break-word;
  }

  .quiz-history .panel-head {
    margin-bottom: 0.85rem;
    padding-bottom: 0.65rem;
    border-bottom: 1px solid #f3ebe4;
  }

  .quiz-history .section-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: #1c1410;
    margin: 0;
  }

  .quiz-list {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    width: 100%;
    max-width: 100%;
  }

  .quiz-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.7rem;
    padding: 0.65rem 0.8rem;
    background: #fff8f3;
    border: 1px solid transparent;
    border-radius: 10px;
    transition: all 0.2s ease;
    min-width: 0;
  }

  .quiz-row:hover {
    background: #fff;
    border-color: #fcd9b5;
    box-shadow: 0 4px 12px -4px rgba(234, 88, 12, 0.20);
  }

  .quiz-row-content {
    min-width: 0;
    flex: 1;
    overflow: hidden;
  }

  .quiz-row-title {
    font-size: 0.82rem;
    font-weight: 700;
    color: #1c1410;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .quiz-row-meta {
    font-size: 0.68rem;
    color: #8a7266;
    margin-top: 0.1rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .quiz-row-score {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.1rem;
    flex-shrink: 0;
  }

  .quiz-score-ratio {
    font-size: 0.75rem;
    font-weight: 700;
    color: #1c1410;
    white-space: nowrap;
  }

  .quiz-score-points {
    font-size: 0.68rem;
    font-weight: 600;
    color: #ea580c;
    white-space: nowrap;
  }

  .quiz-history-foot {
    margin-top: 0.85rem;
    padding-top: 0.75rem;
    border-top: 1px solid #f3ebe4;
    text-align: center;
  }

  .quiz-cta {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.55rem 1.1rem;
    font-size: 0.78rem;
    font-weight: 600;
    color: #fff;
    background: linear-gradient(135deg, #fb923c, #ea580c);
    border-radius: 9px;
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(249, 115, 22, 0.30);
    transition: all 0.2s ease;
  }

  .quiz-cta:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(234, 88, 12, 0.40);
  }

  .empty {
    text-align: center;
    padding: 1.1rem 0.75rem;
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
    margin: 0;
    line-height: 1.4;
  }

  @media (max-width: 520px) {
    .quiz-last,
    .quiz-history { padding: 0.95rem 0.85rem 0.8rem; }
    .quiz-last-number { font-size: 2rem; }
    .quiz-correction-answers { grid-template-columns: 1fr; }
    .quiz-row { padding: 0.55rem 0.7rem; }
    .quiz-row-title { font-size: 0.78rem; }
    .quiz-row-meta { font-size: 0.65rem; }
    .quiz-score-ratio { font-size: 0.72rem; }
    .quiz-score-points { font-size: 0.65rem; }
  }
</style>