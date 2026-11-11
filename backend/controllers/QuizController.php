<?php
declare(strict_types=1);
final class QuizController {
    public static function index(): never {
        $stmt = db()->prepare("SELECT q.id,q.titre,q.slug,q.description,q.theme,q.difficulte,COUNT(DISTINCT question.id) AS nombre_questions FROM quiz q LEFT JOIN questions question ON question.quiz_id=q.id AND question.statut='actif' WHERE q.statut='publie' GROUP BY q.id ORDER BY q.created_at DESC LIMIT 100");
        $stmt->execute();
        json_response(['data' => $stmt->fetchAll()]);
    }

    public static function show(int $id): never {
        $stmt = db()->prepare("SELECT id,titre,slug,description,theme,difficulte FROM quiz WHERE id=? AND statut='publie' LIMIT 1");
        $stmt->execute([$id]);
        $quiz = $stmt->fetch();
        if (!$quiz) api_error('not_found', 'Quiz introuvable.', 404);
        $questions = db()->prepare("SELECT id,enonce,ordre,points FROM questions WHERE quiz_id=? AND statut='actif' ORDER BY ordre");
        $questions->execute([$id]);
        $quiz['questions'] = $questions->fetchAll();
        $answers = db()->prepare('SELECT id,question_id,libelle,ordre FROM reponses WHERE question_id=? ORDER BY ordre');
        foreach ($quiz['questions'] as &$question) {
            $answers->execute([$question['id']]);
            $question['reponses'] = $answers->fetchAll();
        }
        unset($question);
        json_response(['data' => $quiz]);
    }

    public static function submit(int $quizId): never {
        $userId = AuthMiddleware::userId();
        $data = request_json();
        $selected = $data['reponses'] ?? null;
        if (!is_array($selected) || count($selected) < 1 || count($selected) > 100) {
            api_error('validation_error', 'La liste des réponses est invalide.', 422);
        }
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $quizCheck = $pdo->prepare("SELECT id FROM quiz WHERE id=? AND statut='publie' FOR UPDATE");
            $quizCheck->execute([$quizId]);
            if (!$quizCheck->fetchColumn()) {
                $pdo->rollBack();
                api_error('not_found', 'Quiz introuvable.', 404);
            }
            $query = $pdo->prepare("SELECT question.id AS question_id,question.points,question.enonce,question.explication,reponse.id AS reponse_id,reponse.libelle AS votre_reponse,reponse.est_correcte,(SELECT bonne.libelle FROM reponses bonne WHERE bonne.question_id=question.id AND bonne.est_correcte=1 LIMIT 1) AS bonne_reponse FROM questions question JOIN reponses reponse ON reponse.question_id=question.id WHERE question.quiz_id=? AND question.statut='actif' AND reponse.id=?");
            $score = 0;
            $correct = 0;
            $total = 0;
            $answersToSave = [];
            $corrections = [];
            $seenQuestions = [];
            foreach ($selected as $answerId) {
                $answerId = filter_var($answerId, FILTER_VALIDATE_INT);
                if (!$answerId || $answerId < 1) {
                    $pdo->rollBack();
                    api_error('validation_error', 'Chaque réponse doit être un identifiant valide.', 422);
                }
                $query->execute([$quizId,$answerId]);
                $answer = $query->fetch();
                if (!$answer || isset($seenQuestions[$answer['question_id']])) {
                    $pdo->rollBack();
                    api_error('validation_error', 'Réponse inconnue ou question répondue plusieurs fois.', 422);
                }
                $seenQuestions[$answer['question_id']] = true;
                $isCorrect = (bool)$answer['est_correcte'];
                $correct += $isCorrect ? 1 : 0;
                $score += $isCorrect ? (int)$answer['points'] : 0;
                $answersToSave[] = [(int)$answer['question_id'],(int)$answer['reponse_id'],$isCorrect];
                $corrections[] = ['question' => $answer['enonce'],'votre_reponse' => $answer['votre_reponse'],'bonne_reponse' => $answer['bonne_reponse'],'correct' => $isCorrect,'explication' => $answer['explication']];
                $total++;
            }
            $count = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE quiz_id=? AND statut='actif'");
            $count->execute([$quizId]);
            $expected = (int)$count->fetchColumn();
            if ($expected === 0 || $total !== $expected) {
                $pdo->rollBack();
                api_error('validation_error', 'Toutes les questions actives doivent recevoir une réponse.', 422);
            }
            $participation = $pdo->prepare('INSERT INTO participations_quiz(utilisateur_id,quiz_id,score,bonnes_reponses,total_questions,commence_at,termine_at) VALUES(?,?,?,?,?,NOW(),NOW())');
            $participation->execute([$userId,$quizId,$score,$correct,$total]);
            $participationId = (int)$pdo->lastInsertId();
            $save = $pdo->prepare('INSERT INTO participations_quiz_reponses(participation_id,question_id,reponse_id,est_correcte) VALUES(?,?,?,?)');
            foreach ($answersToSave as [$questionId,$answerId,$isCorrect]) $save->execute([$participationId,$questionId,$answerId,$isCorrect ? 1 : 0]);
            if ($score > 0) {
                $pdo->prepare('UPDATE passeports SET niveau=1+FLOOR((points_total+?)/100),points_total=points_total+? WHERE utilisateur_id=?')->execute([$score,$score,$userId]);
                $pdo->prepare("INSERT INTO historique_points(utilisateur_id,points_delta,motif,reference_type,reference_id) VALUES(?,?,'Quiz terminé','participation_quiz',?)")->execute([$userId,$score,$participationId]);
            }
            BadgeService::evaluate($pdo,$userId);
            $pdo->commit();
            json_response(['data' => ['participation_id' => $participationId,'score' => $score,'bonnes_reponses' => $correct,'total_questions' => $total,'corrections' => $corrections]]);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public static function results(): never {
        $stmt = db()->prepare('SELECT p.id,p.score,p.bonnes_reponses,p.total_questions,p.commence_at,p.termine_at,q.id AS quiz_id,q.titre AS quiz FROM participations_quiz p JOIN quiz q ON q.id=p.quiz_id WHERE p.utilisateur_id=? ORDER BY p.created_at DESC LIMIT 100');
        $stmt->execute([AuthMiddleware::userId()]);
        json_response(['data' => $stmt->fetchAll()]);
    }
}
