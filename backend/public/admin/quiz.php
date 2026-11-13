<?php
declare(strict_types=1);
require_once dirname(__DIR__,2) . '/admin/bootstrap.php';
admin_require_module('quizzes',true);
$id = filter_var($_GET['id'] ?? null,FILTER_VALIDATE_INT);
$quiz = ['titre'=>'','slug'=>'','description'=>'','theme'=>'culture','difficulte'=>'debutant','statut'=>'brouillon'];
$questions = [];
$error = '';
if ($id) {
    $stmt = db()->prepare('SELECT * FROM quiz WHERE id=?');
    $stmt->execute([$id]);
    $quiz = $stmt->fetch();
    if (!$quiz) { http_response_code(404); exit('Quiz introuvable.'); }
    $stmt = db()->prepare('SELECT id,enonce,explication,ordre FROM questions WHERE quiz_id=? ORDER BY ordre');
    $stmt->execute([$id]);
    foreach ($stmt->fetchAll() as $question) {
        $answers = db()->prepare('SELECT libelle,est_correcte FROM reponses WHERE question_id=? ORDER BY ordre');
        $answers->execute([$question['id']]);
        $question['reponses'] = $answers->fetchAll();
        $questions[] = $question;
    }
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_check_csrf();
    $quiz['titre'] = trim((string)($_POST['titre'] ?? ''));
    $quiz['slug'] = strtolower(trim((string)($_POST['slug'] ?? '')));
    $quiz['description'] = trim((string)($_POST['description'] ?? ''));
    $quiz['theme'] = (string)($_POST['theme'] ?? '');
    $quiz['difficulte'] = (string)($_POST['difficulte'] ?? '');
    $quiz['statut'] = (string)($_POST['statut'] ?? 'brouillon');
    $themes = ['histoire','geographie','gastronomie','traditions','musique','patrimoine','faune_flore','tourisme','culture'];
    $difficulty = ['debutant','intermediaire','avance'];
    $validStatus = ['brouillon','publie','archive'];
    $submitted = $_POST['questions'] ?? [];
    $preparedQuestions = [];
    if (is_array($submitted)) {
        foreach ($submitted as $row) {
            if (!is_array($row)) continue;
            $enonce = trim((string)($row['enonce'] ?? ''));
            $explanation = trim((string)($row['explication'] ?? ''));
            $answers = $row['reponses'] ?? [];
            $correct = filter_var($row['correcte'] ?? null,FILTER_VALIDATE_INT);
            $hasAny = $enonce!=='' || $explanation!=='' || (is_array($answers) && count(array_filter($answers,static fn($value)=>trim((string)$value)!==''))>0);
            if (!$hasAny) continue;
            if ($enonce==='' || !is_array($answers) || count($answers)!==4 || count(array_filter($answers,static fn($value)=>trim((string)$value)!==''))!==4 || $correct===false || $correct<0 || $correct>3) {
                $error='Chaque question commencée doit avoir un énoncé, une explication, quatre propositions et une seule réponse correcte.';
                break;
            }
            $preparedQuestions[]=['enonce'=>$enonce,'explication'=>$explanation,'reponses'=>array_map(static fn($value)=>trim((string)$value),$answers),'correcte'=>$correct];
        }
    } else {
        $error='Format des questions invalide.';
    }
    if ($error==='' && ($quiz['titre']==='' || mb_strlen($quiz['titre'])>180 || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/',$quiz['slug']) || !in_array($quiz['theme'],$themes,true) || !in_array($quiz['difficulte'],$difficulty,true) || !in_array($quiz['statut'],$validStatus,true))) {
        $error='Titre, slug, thème, difficulté ou statut invalide.';
    }
    if ($error==='' && $quiz['statut']==='publie' && count($preparedQuestions)!==10) {
        $error='Un quiz publié doit contenir exactement 10 questions complètes.';
    }
    if ($error==='') {
        $pdo=db();
        $pdo->beginTransaction();
        try {
            if ($id) {
                $attempts=$pdo->prepare('SELECT COUNT(*) FROM participations_quiz WHERE quiz_id=?');
                $attempts->execute([$id]);
                if ((int)$attempts->fetchColumn()>0) throw new RuntimeException('Ce quiz possède déjà des résultats; ses questions ne peuvent plus être remplacées.');
                $pdo->prepare('UPDATE quiz SET titre=?,slug=?,description=?,theme=?,difficulte=?,statut=? WHERE id=?')
                    ->execute([$quiz['titre'],$quiz['slug'],$quiz['description']?:null,$quiz['theme'],$quiz['difficulte'],$quiz['statut'],$id]);
                $pdo->prepare('DELETE FROM questions WHERE quiz_id=?')->execute([$id]);
            } else {
                $pdo->prepare('INSERT INTO quiz(titre,slug,description,theme,difficulte,statut) VALUES(?,?,?,?,?,?)')
                    ->execute([$quiz['titre'],$quiz['slug'],$quiz['description']?:null,$quiz['theme'],$quiz['difficulte'],$quiz['statut']]);
                $id=(int)$pdo->lastInsertId();
            }
            $questionInsert=$pdo->prepare("INSERT INTO questions(quiz_id,enonce,explication,ordre,points,statut) VALUES(?,?,?,?,1,'actif')");
            $answerInsert=$pdo->prepare('INSERT INTO reponses(question_id,libelle,est_correcte,ordre) VALUES(?,?,?,?)');
            foreach ($preparedQuestions as $index=>$question) {
                $questionInsert->execute([$id,$question['enonce'],$question['explication']?:null,$index+1]);
                $questionId=(int)$pdo->lastInsertId();
                foreach ($question['reponses'] as $answerIndex=>$answer) $answerInsert->execute([$questionId,$answer,$answerIndex===$question['correcte']?1:0,$answerIndex+1]);
            }
            $pdo->commit();
            admin_flash('Quiz et questions enregistrés.');
            header('Location: index.php?module=quizzes');
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log((string)$e);
            $error=$e instanceof InvalidArgumentException ? $e->getMessage() : 'Échec de l’enregistrement du quiz.';
        }
    }
}
while (count($questions)<10) $questions[]=['enonce'=>'','explication'=>'','reponses'=>[]];
$csrf=admin_csrf();
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Éditeur de quiz — Administration</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #e67e22;
      --primary-dark: #d35400;
      --bg-body: #f4f6f9;
      --border-color: #e9ecef;
      --sidebar-width: 260px;   /* ⚠️ À ajuster selon la largeur réelle de votre _sidebar.php */
      --topbar-height: 64px;    /* ⚠️ À ajuster selon la hauteur réelle de votre topbar */
    }
    * { font-family: 'Inter', sans-serif; box-sizing: border-box; }
    body { background: var(--bg-body); min-height: 100vh; margin: 0; }

    /* ===== LAYOUT FIXE ===== */
    .sidebar {
      position: fixed !important;
      top: 0 !important; left: 0 !important; bottom: 0 !important;
      height: 100vh !important; overflow-y: auto; z-index: 1000;
    }
    .topbar {
      position: fixed; top: 0; left: var(--sidebar-width); right: 0;
      height: var(--topbar-height);
      background: #fff; border-bottom: 1px solid var(--border-color);
      padding: 0 1.75rem;
      display: flex; justify-content: space-between; align-items: center;
      z-index: 900; box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }
    .topbar strong { font-weight: 700; letter-spacing: 0.5px; color: #1a1d29; font-size: 0.95rem; }
    .topbar a {
      color: #4a5568; text-decoration: none; font-size: 0.875rem; font-weight: 500;
      padding: 0.45rem 1rem; border-radius: 8px; border: 1px solid var(--border-color);
      background: #fff; transition: all 0.2s;
    }
    .topbar a:hover { background: #f8f9fb; border-color: #d0d5dd; color: #1a1d29; }

    /* Contenu */
    .content {
      margin-left: var(--sidebar-width);
      padding: calc(var(--topbar-height) + 2rem) 2.5rem 2rem;
      min-height: 100vh;
    }
    .content-inner { max-width: 900px; margin: 0 auto; }
    .content h1 { font-size: 1.75rem; font-weight: 700; color: #1a1d29; margin-bottom: 0.25rem; }
    .muted { color: #8892a6; font-size: 0.9rem; margin-bottom: 1.5rem; }

    /* Panel / Form card */
    .panel {
      background: #fff; border-radius: 14px; padding: 2rem 2.25rem;
      border: 1px solid var(--border-color);
      box-shadow: 0 4px 16px rgba(0,0,0,0.04);
    }
    .panel label {
      display: block; font-size: 0.82rem; font-weight: 600;
      color: #34495e; margin-bottom: 0.35rem; margin-top: 1rem;
    }
    .panel label:first-of-type { margin-top: 0; }
    .panel input, .panel select, .panel textarea {
      width: 100%; border: 1px solid var(--border-color); border-radius: 10px;
      padding: 0.65rem 0.95rem; font-size: 0.9rem; color: #1a1d29;
      background: #fafbfc; transition: all 0.2s; font-family: inherit;
    }
    .panel input:focus, .panel select:focus, .panel textarea:focus {
      outline: none; border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(230,126,34,0.15); background: #fff;
    }
    .panel textarea { resize: vertical; line-height: 1.55; }

    /* Grille principale */
    .editor-grid {
      display: grid; grid-template-columns: 1fr 1fr; gap: 0.25rem 1.25rem;
    }
    .editor-grid .wide { grid-column: 1 / -1; }
    @media (max-width: 720px) { .editor-grid { grid-template-columns: 1fr; } }

    /* Hint */
    .hint { font-size: 0.75rem; color: #8892a6; margin-top: 0.3rem; }

    /* Section quiz (questions) */
    .questions-header {
      display: flex; justify-content: space-between; align-items: center;
      margin: 2.5rem 0 1rem;
    }
    .questions-header h2 { font-size: 1.15rem; font-weight: 700; color: #1a1d29; margin: 0; }
    .questions-header .counter {
      font-size: 0.78rem; font-weight: 600; color: var(--primary);
      background: #fffaf5; border: 1px solid #f5cba7; padding: 0.3rem 0.75rem;
      border-radius: 50px;
    }

    /* Carte question */
    .question-card {
      background: #fff; border-radius: 14px; padding: 1.5rem 1.75rem;
      border: 1px solid var(--border-color);
      box-shadow: 0 2px 8px rgba(0,0,0,0.03);
      margin-bottom: 1.25rem; position: relative;
    }
    .question-card .question-num {
      display: inline-flex; align-items: center; justify-content: center;
      width: 32px; height: 32px; border-radius: 50%;
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      color: #fff; font-weight: 700; font-size: 0.85rem;
      margin-bottom: 0.75rem;
      box-shadow: 0 4px 10px rgba(230,126,34,0.3);
    }
    .question-card label {
      display: block; font-size: 0.8rem; font-weight: 600;
      color: #34495e; margin-bottom: 0.3rem; margin-top: 0.85rem;
    }
    .question-card label:first-of-type { margin-top: 0; }
    .question-card input[type="text"], .question-card input:not([type]), .question-card textarea {
      width: 100%; border: 1px solid var(--border-color); border-radius: 10px;
      padding: 0.6rem 0.9rem; font-size: 0.875rem; color: #1a1d29;
      background: #fafbfc; transition: all 0.2s; font-family: inherit;
    }
    .question-card input:focus, .question-card textarea:focus {
      outline: none; border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(230,126,34,0.12); background: #fff;
    }
    .question-card textarea { resize: vertical; line-height: 1.5; }

    /* Propositions */
    .answers-grid {
      display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem 1rem;
      margin-top: 0.85rem;
    }
    @media (max-width: 720px) { .answers-grid { grid-template-columns: 1fr; } }
    .answer-item {
      display: flex; align-items: center; gap: 0.55rem;
      background: #fafbfc; border: 1px solid var(--border-color);
      border-radius: 10px; padding: 0.35rem 0.55rem;
      transition: all 0.2s;
    }
    .answer-item:focus-within {
      border-color: var(--primary); background: #fff;
      box-shadow: 0 0 0 3px rgba(230,126,34,0.12);
    }
    .answer-item input[type="text"], .answer-item input:not([type]) {
      border: none; background: transparent; box-shadow: none; padding: 0.35rem 0.4rem;
    }
    .answer-item input:focus { box-shadow: none; }
    .answer-item .answer-radio {
      display: inline-flex; align-items: center; gap: 0.3rem;
      font-size: 0.75rem; color: #8892a6; font-weight: 500;
      white-space: nowrap; cursor: pointer; padding-right: 0.4rem;
      border-left: 1px dashed var(--border-color); padding-left: 0.55rem;
    }
    .answer-item .answer-radio input[type="radio"] {
      width: 16px; height: 16px; accent-color: var(--primary); cursor: pointer;
    }
    .answer-item:has(input[type="radio"]:checked) {
      border-color: #27ae60; background: #e8f8f0;
    }
    .answer-item:has(input[type="radio"]:checked) .answer-radio { color: #1e8449; font-weight: 600; }

    /* Info box */
    .info-box {
      background: #fffaf5; border: 1px solid #f5cba7; border-left: 4px solid var(--primary);
      color: #7e5109; border-radius: 10px; padding: 0.9rem 1.1rem;
      font-size: 0.85rem; line-height: 1.55; margin: 1.25rem 0;
    }

    /* Buttons */
    .button {
      display: inline-block; text-decoration: none; border-radius: 10px;
      padding: 0.7rem 1.5rem; font-size: 0.9rem; font-weight: 600;
      border: 1px solid var(--border-color); background: #fff; color: #4a5568;
      transition: all 0.2s; cursor: pointer;
    }
    .button:hover { background: #f8f9fb; border-color: #d0d5dd; color: #1a1d29; }
    .button.primary {
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      color: #fff; border: none; box-shadow: 0 4px 12px rgba(230,126,34,0.3);
    }
    .button.primary:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(230,126,34,0.4); }

    /* Notice */
    .notice { padding: 1rem 1.25rem; border-radius: 10px; font-size: 0.875rem; margin-bottom: 1.5rem; font-weight: 500; }
    .notice.error { background: #fdecea; color: #c0392b; border-left: 4px solid #e74c3c; }

    /* Responsive */
    @media (max-width: 992px) {
      .sidebar { position: static !important; height: auto !important; width: 100% !important; }
      .topbar { position: static; left: 0; height: auto; padding: 0.85rem 1rem; }
      .content { margin-left: 0; padding: 1.5rem 1rem; }
      .panel { padding: 1.5rem; }
    }
  </style>
</head>
<body class="admin-page">
<?php require __DIR__ . '/_sidebar.php'; ?>

<header class="topbar">
  <strong>TOURISME IVOIRE · ADMIN</strong>
  <a href="index.php?module=quizzes">← Retour aux quiz</a>
</header>

<main class="content">
  <div class="content-inner">
    <h1><?= $id?'Modifier':'Créer' ?> un quiz</h1>
    <p class="muted">Un quiz publié doit contenir exactement 10 questions complètes. Chaque question a 4 propositions dont une seule correcte.</p>

    <?php if ($error!==''): ?>
      <div class="notice error"><?= admin_escape($error) ?></div>
    <?php endif; ?>

    <form class="panel" method="post">
      <input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>">

      <div class="editor-grid">
        <div class="wide">
          <label for="titre">Titre</label>
          <input id="titre" name="titre" maxlength="180" required value="<?= admin_escape($quiz['titre']) ?>" placeholder="Ex : Quiz sur les cultures ivoiriennes">
        </div>

        <div>
          <label for="slug">Slug</label>
          <input id="slug" name="slug" required value="<?= admin_escape($quiz['slug']) ?>" placeholder="quiz-cultures-ivoiriennes">
          <div class="hint">Lettres minuscules, chiffres et tirets uniquement.</div>
        </div>

        <div>
          <label for="statut">Publication</label>
          <select id="statut" name="statut">
            <?php foreach (['brouillon','publie','archive'] as $value): ?>
              <option value="<?= $value ?>" <?= $quiz['statut']===$value?'selected':'' ?>>
                <?= admin_escape(ucfirst($value)) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="wide">
          <label for="description">Description</label>
          <textarea id="description" name="description" rows="3" placeholder="Présentez le thème et l'objectif du quiz…"><?= admin_escape($quiz['description']) ?></textarea>
        </div>

        <div>
          <label for="theme">Thème</label>
          <select id="theme" name="theme">
            <?php foreach (['histoire','geographie','gastronomie','traditions','musique','patrimoine','faune_flore','tourisme','culture'] as $value): ?>
              <option value="<?= $value ?>" <?= $quiz['theme']===$value?'selected':'' ?>><?= admin_escape(ucfirst(str_replace('_',' ', $value))) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label for="difficulte">Difficulté</label>
          <select id="difficulte" name="difficulte">
            <?php foreach (['debutant','intermediaire','avance'] as $value): ?>
              <option value="<?= $value ?>" <?= $quiz['difficulte']===$value?'selected':'' ?>><?= admin_escape(ucfirst($value)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="info-box">
        ℹ️ <strong>Rappel :</strong> pour publier un quiz, il faut exactement <strong>10 questions complètes</strong> avec 4 propositions chacune et une seule réponse correcte.
      </div>

      <div class="questions-header">
        <h2>Questions</h2>
        <span class="counter">10 questions à remplir</span>
      </div>

      <?php for ($index=0;$index<10;$index++): $question=$questions[$index]; ?>
        <div class="question-card">
          <div class="question-num"><?= $index+1 ?></div>

          <label for="enonce_<?= $index ?>">Énoncé</label>
          <textarea id="enonce_<?= $index ?>" name="questions[<?= $index ?>][enonce]" rows="2" placeholder="Posez la question…"><?= admin_escape($question['enonce']) ?></textarea>

          <label for="explication_<?= $index ?>">Explication</label>
          <textarea id="explication_<?= $index ?>" name="questions[<?= $index ?>][explication]" rows="2" placeholder="Expliquez la bonne réponse…"><?= admin_escape($question['explication']) ?></textarea>

          <label>Propositions <span class="text-muted" style="font-weight:400">(cochez la bonne réponse)</span></label>
          <div class="answers-grid">
            <?php for ($answer=0;$answer<4;$answer++): $choice=$question['reponses'][$answer]??['libelle'=>'','est_correcte'=>0]; ?>
              <div class="answer-item">
                <input
                  type="text"
                  name="questions[<?= $index ?>][reponses][<?= $answer ?>]"
                  value="<?= admin_escape($choice['libelle']) ?>"
                  placeholder="Proposition <?= $answer+1 ?>"
                  aria-label="Proposition <?= $answer+1 ?> de la question <?= $index+1 ?>"
                >
                <label class="answer-radio">
                  <input type="radio" name="questions[<?= $index ?>][correcte]" value="<?= $answer ?>" <?= !empty($choice['est_correcte'])?'checked':'' ?>>
                  Correcte
                </label>
              </div>
            <?php endfor; ?>
          </div>
        </div>
      <?php endfor; ?>

      <div style="margin-top:1.75rem; display:flex; gap:0.75rem;">
        <button class="button primary" type="submit">Enregistrer le quiz</button>
        <a class="button" href="index.php?module=quizzes">Annuler</a>
      </div>
    </form>
  </div>
</main>
</body>
</html>