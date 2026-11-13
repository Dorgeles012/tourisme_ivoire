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
<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Éditeur de quiz — Administration</title><link rel="stylesheet" href="style.css"></head>
<body class="admin-page">
<?php require __DIR__ . '/_sidebar.php'; ?><header class="topbar"><strong>TOURISME IVOIRE · ADMIN</strong><a href="index.php?module=quizzes">Retour aux quiz</a></header><main class="content editor"><h1><?= $id?'Modifier':'Créer' ?> un quiz</h1>
<?php if ($error!==''): ?><div class="notice error"><?= admin_escape($error) ?></div><?php endif; ?>
<form class="panel" method="post"><input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>">
<label>Titre</label><input name="titre" maxlength="180" required value="<?= admin_escape($quiz['titre']) ?>">
<label>Slug</label><input name="slug" required value="<?= admin_escape($quiz['slug']) ?>">
<label>Description</label><textarea name="description" rows="3"><?= admin_escape($quiz['description']) ?></textarea>
<div class="editor-grid"><div><label>Thème</label><select name="theme"><?php foreach (['histoire','geographie','gastronomie','traditions','musique','patrimoine','faune_flore','tourisme','culture'] as $value): ?><option value="<?= $value ?>" <?= $quiz['theme']===$value?'selected':'' ?>><?= $value ?></option><?php endforeach; ?></select></div>
<div><label>Difficulté</label><select name="difficulte"><?php foreach (['debutant','intermediaire','avance'] as $value): ?><option value="<?= $value ?>" <?= $quiz['difficulte']===$value?'selected':'' ?>><?= $value ?></option><?php endforeach; ?></select></div></div>
<label>Publication</label><select name="statut"><?php foreach (['brouillon','publie','archive'] as $value): ?><option value="<?= $value ?>" <?= $quiz['statut']===$value?'selected':'' ?>><?= $value ?></option><?php endforeach; ?></select>
<?php for ($index=0;$index<10;$index++): $question=$questions[$index]; ?><fieldset class="panel" style="margin-top:16px"><legend>Question <?= $index+1 ?></legend>
<label>Énoncé</label><textarea name="questions[<?= $index ?>][enonce]" rows="2"><?= admin_escape($question['enonce']) ?></textarea>
<label>Explication</label><textarea name="questions[<?= $index ?>][explication]" rows="2"><?= admin_escape($question['explication']) ?></textarea>
<?php for ($answer=0;$answer<4;$answer++): $choice=$question['reponses'][$answer]??['libelle'=>'','est_correcte'=>0]; ?><label>Proposition <?= $answer+1 ?></label><div class="inline-form"><input name="questions[<?= $index ?>][reponses][<?= $answer ?>]" value="<?= admin_escape($choice['libelle']) ?>"><label style="white-space:nowrap;margin:0"><input type="radio" name="questions[<?= $index ?>][correcte]" value="<?= $answer ?>" <?= !empty($choice['est_correcte'])?'checked':'' ?> style="width:auto"> Correcte</label></div><?php endfor; ?>
</fieldset><?php endfor; ?>
<p><button class="button primary" type="submit">Enregistrer le quiz</button></p></form></main></body></html>
