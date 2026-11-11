<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/admin/bootstrap.php';
$user = admin_require_role(['administrateur','editeur']);
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    exit('Méthode non autorisée.');
}
admin_check_csrf();
$module = (string)($_POST['module'] ?? '');
$id = filter_var($_POST['id'] ?? null,FILTER_VALIDATE_INT);
$action = (string)($_POST['action'] ?? '');

$configuration = [
    'users' => ['table'=>'utilisateurs','column'=>'statut','values'=>['actif','suspendu','en_attente'],'roles'=>['administrateur']],
    'regions' => ['table'=>'regions','column'=>'statut','values'=>['actif','inactif'],'roles'=>['administrateur']],
    'districts' => ['table'=>'districts','column'=>'statut','values'=>['actif','inactif'],'roles'=>['administrateur']],
    'localities' => ['table'=>'localites','column'=>'statut','values'=>['actif','inactif'],'roles'=>['administrateur']],
    'categories' => ['table'=>'categories','column'=>'statut','values'=>['actif','inactif'],'roles'=>['administrateur','editeur']],
    'destinations' => ['table'=>'destinations','column'=>'statut_publication','values'=>['brouillon','publie','archive'],'roles'=>['administrateur','editeur']],
    'images' => ['table'=>'images_destinations','column'=>'statut','values'=>['actif','inactif'],'roles'=>['administrateur','editeur']],
    'videos' => ['table'=>'videos','column'=>'statut','values'=>['en_attente','publie','rejete','archive'],'roles'=>['administrateur','editeur']],
    'quizzes' => ['table'=>'quiz','column'=>'statut','values'=>['brouillon','publie','archive'],'roles'=>['administrateur','editeur']],
    'badges' => ['table'=>'badges','column'=>'statut','values'=>['actif','inactif'],'roles'=>['administrateur','editeur']],
    'reservations' => ['table'=>'reservations','column'=>'statut','values'=>['demande','confirmee','annulee','terminee'],'roles'=>['administrateur']],
    'reviews' => ['table'=>'avis','column'=>'statut','values'=>['en_attente','publie','rejete'],'roles'=>['administrateur','editeur']],
];

try {
    if ($action === 'read_notification') {
        admin_require_role(['administrateur','editeur']);
        if (!$id || $id < 1) throw new InvalidArgumentException('Notification invalide.');
        db()->prepare('UPDATE notifications SET lu_at=COALESCE(lu_at,NOW()) WHERE id=?')->execute([$id]);
    } elseif ($action === 'delete_destination') {
        admin_require_role(['administrateur','editeur']);
        if (!$id || $id < 1) throw new InvalidArgumentException('Destination invalide.');
        db()->prepare("UPDATE destinations SET deleted_at=NOW() WHERE id=? AND deleted_at IS NULL")->execute([$id]);
    } else {
        $config = $configuration[$module] ?? null;
        if (!$config || !in_array($action,$config['values'],true) || !$id || $id < 1) {
            throw new InvalidArgumentException('Action ou élément invalide.');
        }
        admin_require_role($config['roles']);
        if ($module==='destinations' && $action==='publie') {
            $source = db()->prepare('SELECT source_reference FROM destinations WHERE id=? AND deleted_at IS NULL');
            $source->execute([$id]);
            $reference = $source->fetchColumn();
            $scheme = is_string($reference) ? strtolower((string)parse_url($reference,PHP_URL_SCHEME)) : '';
            if (!$reference || !in_array($scheme,['http','https'],true)) throw new RuntimeException('La publication est bloquée : source HTTP(S) vérifiable manquante.');
        }
        if ($module==='videos' && $action==='publie') {
            $rights = db()->prepare('SELECT autorisation_diffusion FROM videos WHERE id=? AND deleted_at IS NULL');
            $rights->execute([$id]);
            if (!$rights->fetchColumn()) throw new RuntimeException('La publication est bloquée : autorisation de diffusion absente.');
        }
        if ($module==='quizzes' && $action==='publie') {
            $valid = db()->prepare("SELECT COUNT(*) AS questions, SUM(answer_count=4 AND correct_count=1) AS valid_questions FROM (SELECT question.id,COUNT(answer.id) AS answer_count,SUM(answer.est_correcte=1) AS correct_count FROM questions question LEFT JOIN reponses answer ON answer.question_id=question.id WHERE question.quiz_id=? AND question.statut='actif' GROUP BY question.id) checks");
            $valid->execute([$id]);
            $counts = $valid->fetch();
            if (!$counts || (int)$counts['questions']!==10 || (int)$counts['valid_questions']!==10) {
                throw new RuntimeException('La publication est bloquée : le quiz doit avoir 10 questions avec 4 propositions et une seule bonne réponse.');
            }
        }
        $statement = db()->prepare("UPDATE {$config['table']} SET {$config['column']}=? WHERE id=?");
        $statement->execute([$action,$id]);
        if ($statement->rowCount() === 0) admin_flash('Aucun changement : l’élément est peut-être déjà dans cet état.');
    }
    admin_flash(admin_flash() ?? 'Modification enregistrée.');
} catch (Throwable $e) {
    error_log((string)$e);
    admin_flash('Échec de la modification. Vérifiez les données et vos permissions.');
}
header('Location: index.php?module=' . rawurlencode($module));
exit;
