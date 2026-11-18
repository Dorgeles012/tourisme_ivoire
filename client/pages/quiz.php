<?php
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id || $id < 1) throw new ClientApiException(404, 'Quiz introuvable.');
$quiz = ClientApi::data($api->request('GET', 'api/quizzes/' . $id));
?>

<section class="panel quiz-panel">
  <header class="panel-head">
    <span class="eyebrow">
      <?= client_h($quiz['theme'] ?? '') ?>
      <?php if (!empty($quiz['difficulte'])): ?> · <?= client_h($quiz['difficulte']) ?><?php endif; ?>
    </span>
    <h2 class="section-title"><?= client_h($quiz['titre'] ?? '') ?></h2>
    <?php if (!empty($quiz['description'])): ?>
      <p class="quiz-desc"><?= client_h($quiz['description']) ?></p>
    <?php endif; ?>
  </header>

  <form method="post" class="quiz-form">
    <?= client_csrf_field() ?>
    <input type="hidden" name="action" value="quiz_submit">
    <input type="hidden" name="quiz_id" value="<?= (int)$quiz['id'] ?>">

    <?php foreach (($quiz['questions'] ?? []) as $index => $question): ?>
      <fieldset class="quiz-question">
        <legend class="quiz-legend">
          <span class="quiz-num"><?= $index + 1 ?></span>
          <?= client_h($question['enonce'] ?? '') ?>
        </legend>
        <div class="quiz-answers">
          <?php foreach (($question['reponses'] ?? []) as $answer): ?>
            <label class="quiz-answer">
              <input type="radio"
                     name="reponses[<?= (int)$question['id'] ?>]"
                     value="<?= (int)$answer['id'] ?>"
                     required>
              <span class="quiz-radio-mark"></span>
              <span class="quiz-answer-label"><?= client_h($answer['libelle'] ?? '') ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </fieldset>
    <?php endforeach; ?>

    <div class="quiz-actions">
      <button class="btn-submit" type="submit">Valider mes réponses</button>
    </div>
  </form>
</section>

<style>
  *, *::before, *::after { box-sizing: border-box; }

  .quiz-panel {
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 14px;
    padding: 1.1rem 1.1rem 1rem;
    box-shadow: 0 4px 18px -6px rgba(234, 88, 12, 0.15);
    width: 100%;
    max-width: 100%;
    min-width: 0;
    overflow: hidden;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }

  .quiz-panel .panel-head {
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #f3ebe4;
  }

  .quiz-panel .eyebrow {
    display: inline-block;
    font-size: 0.6rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #ea580c;
    background: rgba(249, 115, 22, 0.10);
    padding: 0.2rem 0.55rem;
    border-radius: 999px;
    margin-bottom: 0.5rem;
  }

  .quiz-panel .section-title {
    font-size: 1rem;
    font-weight: 700;
    color: #1c1410;
    margin: 0 0 0.35rem;
    line-height: 1.3;
  }

  .quiz-panel .quiz-desc {
    font-size: 0.78rem;
    color: #8a7266;
    line-height: 1.5;
    margin: 0;
  }

  .quiz-form {
    display: flex;
    flex-direction: column;
    gap: 0.7rem;
  }

  .quiz-question {
    border: 1px solid #f1e4d8;
    background: #fff8f3;
    border-radius: 12px;
    padding: 0.85rem 0.9rem;
    margin: 0;
    min-width: 0;
    transition: border-color 0.2s ease, background 0.2s ease;
  }

  .quiz-question:focus-within {
    background: #fff;
    border-color: #fcd9b5;
    box-shadow: 0 4px 12px -4px rgba(234, 88, 12, 0.18);
  }

  .quiz-legend {
    display: flex;
    align-items: flex-start;
    gap: 0.55rem;
    font-size: 0.82rem;
    font-weight: 700;
    color: #1c1410;
    line-height: 1.4;
    margin-bottom: 0.7rem;
    padding: 0;
    width: 100%;
  }

  .quiz-num {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.4rem;
    height: 1.4rem;
    font-size: 0.68rem;
    font-weight: 700;
    color: #fff;
    background: #f97316;
    border-radius: 50%;
    margin-top: 0.05rem;
  }

  .quiz-answers {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
  }

  .quiz-answer {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.5rem 0.65rem;
    font-size: 0.76rem;
    color: #4a3830;
    background: #fff;
    border: 1px solid #f1e4d8;
    border-radius: 9px;
    cursor: pointer;
    transition: all 0.18s ease;
    line-height: 1.4;
  }

  .quiz-answer:hover {
    border-color: #fcd9b5;
    background: #fff8f3;
  }

  .quiz-answer input[type="radio"] {
    position: absolute;
    opacity: 0;
    pointer-events: none;
  }

  .quiz-radio-mark {
    flex-shrink: 0;
    position: relative;
    width: 16px;
    height: 16px;
    border: 2px solid #e2cdbc;
    border-radius: 50%;
    background: #fff;
    transition: all 0.18s ease;
  }

  .quiz-radio-mark::after {
    content: "";
    position: absolute;
    inset: 3px;
    border-radius: 50%;
    background: #f97316;
    transform: scale(0);
    transition: transform 0.18s ease;
  }

  .quiz-answer input[type="radio"]:checked + .quiz-radio-mark {
    border-color: #f97316;
  }

  .quiz-answer input[type="radio"]:checked + .quiz-radio-mark::after {
    transform: scale(1);
  }

  .quiz-answer input[type="radio"]:checked ~ .quiz-answer-label {
    color: #ea580c;
    font-weight: 600;
  }

  .quiz-answer:has(input[type="radio"]:checked) {
    border-color: #f97316;
    background: rgba(249, 115, 22, 0.06);
  }

  .quiz-answer input[type="radio"]:focus-visible + .quiz-radio-mark {
    outline: 2px solid #f97316;
    outline-offset: 2px;
  }

  .quiz-actions {
    margin-top: 0.4rem;
    padding-top: 0.85rem;
    border-top: 1px dashed #f1e4d8;
  }

  .btn-submit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    padding: 0.7rem 1.2rem;
    font-size: 0.8rem;
    font-weight: 700;
    font-family: inherit;
    letter-spacing: 0.01em;
    color: #fff;
    background: linear-gradient(135deg, #f97316, #ea580c);
    border: none;
    border-radius: 10px;
    cursor: pointer;
    box-shadow: 0 6px 16px -6px rgba(234, 88, 12, 0.5);
    transition: all 0.2s ease;
  }

  .btn-submit:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 20px -6px rgba(234, 88, 12, 0.6);
  }

  .btn-submit:active {
    transform: translateY(0);
  }

  .btn-submit:focus-visible {
    outline: 2px solid #ea580c;
    outline-offset: 2px;
  }

  @media (max-width: 520px) {
    .quiz-panel { padding: 0.95rem 0.85rem 0.85rem; }
    .quiz-question { padding: 0.7rem 0.75rem; }
    .quiz-legend { font-size: 0.78rem; }
    .quiz-answer { font-size: 0.72rem; padding: 0.45rem 0.55rem; }
    .btn-submit { font-size: 0.76rem; padding: 0.6rem 1rem; }
  }
</style>