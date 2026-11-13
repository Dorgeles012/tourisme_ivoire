<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/admin/bootstrap.php';

$user = admin_require_login();

// --- Méthode & CSRF ---
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    exit('Méthode non autorisée.');
}
admin_check_csrf();

// --- Entrées ---
$module = (string)($_POST['module'] ?? '');
$id     = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$action = (string)($_POST['action'] ?? '');

// --- Configuration des modules gérables via changement de statut ---
$configuration = [
    'users'          => ['table'=>'utilisateurs',        'column'=>'statut',             'values'=>['actif','suspendu','en_attente'],        'module'=>'users'],
    'regions'        => ['table'=>'regions',             'column'=>'statut',             'values'=>['actif','inactif'],                       'module'=>'geography'],
    'districts'      => ['table'=>'districts',           'column'=>'statut',             'values'=>['actif','inactif'],                       'module'=>'geography'],
    'localities'     => ['table'=>'localites',           'column'=>'statut',             'values'=>['actif','inactif'],                       'module'=>'geography'],
    'categories'     => ['table'=>'categories',          'column'=>'statut',             'values'=>['actif','inactif'],                       'module'=>'categories'],
    'destinations'   => ['table'=>'destinations',        'column'=>'statut_publication', 'values'=>['brouillon','publie','archive'],           'module'=>'destinations'],
    'heritage'       => ['table'=>'patrimoines',         'column'=>'statut',             'values'=>['brouillon','publie','archive'],           'module'=>'heritage'],
    'virtual_visits' => ['table'=>'visites_virtuelles',  'column'=>'statut',             'values'=>['actif','inactif'],                       'module'=>'virtual_visits'],
    'images'         => ['table'=>'images_destinations', 'column'=>'statut',             'values'=>['actif','inactif'],                       'module'=>'images'],
    'videos'         => ['table'=>'videos',              'column'=>'statut',             'values'=>['en_attente','publie','rejete','archive'], 'module'=>'videos'],
    'quizzes'        => ['table'=>'quiz',                'column'=>'statut',             'values'=>['brouillon','publie','archive'],           'module'=>'quizzes'],
    'badges'         => ['table'=>'badges',              'column'=>'statut',             'values'=>['actif','inactif'],                       'module'=>'badges'],
    'reservations'   => ['table'=>'reservations',        'column'=>'statut',             'values'=>['demande','confirmee','annulee','terminee'],'module'=>'reservations'],
    'reviews'        => ['table'=>'avis',                'column'=>'statut',             'values'=>['en_attente','publie','rejete'],           'module'=>'reviews'],
];

$successMessage = 'Modification enregistrée.';

/**
 * Vérifie les prérequis métier avant de changer le statut d'un élément.
 * Lève une exception en cas de blocage.
 */
function actions_validate_transition(string $module, string $action, int $id, array $user): void
{
    $pdo = db();

    // --- Utilisateurs : règles Sadmin ---
    if ($module === 'users') {
        $targetRoleQuery = $pdo->prepare('SELECT `role` FROM utilisateurs WHERE id=? AND deleted_at IS NULL');
        $targetRoleQuery->execute([$id]);
        $targetRoleCode  = $targetRoleQuery->fetchColumn();
        $targetRoleCodes = $targetRoleCode ? [$targetRoleCode] : [];
        $actorRoleCodes  = admin_role_codes((int)$user['id']);

        if (in_array('Sadmin', $targetRoleCodes, true) && !in_array('Sadmin', $actorRoleCodes, true)) {
            throw new RuntimeException('Seul un Sadmin peut modifier le statut d’un compte Sadmin.');
        }
        if ((int)$id === (int)$user['id'] && $action !== 'actif') {
            throw new RuntimeException('Vous ne pouvez pas suspendre votre propre compte depuis cette page.');
        }
    }

    // --- Destinations : source HTTP(S) vérifiable requise pour publication ---
    if ($module === 'destinations' && $action === 'publie') {
        $source = $pdo->prepare('SELECT source_reference FROM destinations WHERE id=? AND deleted_at IS NULL');
        $source->execute([$id]);
        $reference = $source->fetchColumn();
        $scheme    = is_string($reference) ? strtolower((string)parse_url($reference, PHP_URL_SCHEME)) : '';
        if (!$reference || !in_array($scheme, ['http','https'], true)) {
            throw new RuntimeException('La publication est bloquée : source HTTP(S) vérifiable manquante.');
        }
    }

    // --- Vidéos : autorisation de diffusion requise ---
    if ($module === 'videos' && $action === 'publie') {
        $rights = $pdo->prepare('SELECT autorisation_diffusion FROM videos WHERE id=? AND deleted_at IS NULL');
        $rights->execute([$id]);
        if (!$rights->fetchColumn()) {
            throw new RuntimeException('La publication est bloquée : autorisation de diffusion absente.');
        }
    }

    // --- Quiz : 10 questions, 4 propositions, 1 bonne réponse ---
    if ($module === 'quizzes' && $action === 'publie') {
        $valid = $pdo->prepare(
            "SELECT COUNT(*) AS questions, SUM(answer_count=4 AND correct_count=1) AS valid_questions
             FROM (
                 SELECT question.id,
                        COUNT(answer.id) AS answer_count,
                        SUM(answer.est_correcte=1) AS correct_count
                 FROM questions question
                 LEFT JOIN reponses answer ON answer.question_id=question.id
                 WHERE question.quiz_id=? AND question.statut='actif'
                 GROUP BY question.id
             ) checks"
        );
        $valid->execute([$id]);
        $counts = $valid->fetch();
        if (!$counts || (int)$counts['questions'] !== 10 || (int)$counts['valid_questions'] !== 10) {
            throw new RuntimeException('La publication est bloquée : le quiz doit avoir 10 questions avec 4 propositions et une seule bonne réponse.');
        }
    }

    // --- Visites virtuelles : contenu associé publié requis ---
    if ($module === 'virtual_visits' && $action === 'actif') {
        $publication = $pdo->prepare(
            "SELECT CASE WHEN v.patrimoine_id IS NOT NULL THEN p.statut ELSE d.statut_publication END
             FROM visites_virtuelles v
             LEFT JOIN patrimoines p ON p.id=v.patrimoine_id
             LEFT JOIN destinations d ON d.id=v.destination_id
             WHERE v.id=?"
        );
        $publication->execute([$id]);
        if ($publication->fetchColumn() !== 'publie') {
            throw new RuntimeException('La visite virtuelle ne peut être activée que si son contenu associé est publié.');
        }
    }
}

/**
 * Traite le changement de statut d'un utilisateur (transaction + verrous).
 */
function actions_update_user_status(string $action, int $id, array $user): void
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $actorLock = $pdo->prepare("SELECT `role` FROM utilisateurs WHERE id=? AND statut='actif' AND deleted_at IS NULL FOR UPDATE");
        $actorLock->execute([(int)$user['id']]);
        if ($actorLock->fetchColumn() !== 'Sadmin') {
            throw new RuntimeException('Votre rôle ne permet plus cette opération.');
        }

        $targetLock = $pdo->prepare('SELECT `role`,statut FROM utilisateurs WHERE id=? AND deleted_at IS NULL FOR UPDATE');
        $targetLock->execute([$id]);
        $lockedUser = $targetLock->fetch();
        if (!$lockedUser) {
            throw new RuntimeException('Utilisateur introuvable.');
        }

        if ($lockedUser['role'] === 'Sadmin' && $lockedUser['statut'] === 'actif') {
            $admins = $pdo->query("SELECT id FROM utilisateurs WHERE `role`='Sadmin' AND statut='actif' AND deleted_at IS NULL FOR UPDATE")->fetchAll(PDO::FETCH_COLUMN);
            if (count($admins) <= 1) {
                throw new RuntimeException('Impossible de suspendre le dernier Sadmin actif.');
            }
        }

        $statement = $pdo->prepare("UPDATE utilisateurs SET statut=? WHERE id=? AND deleted_at IS NULL");
        $statement->execute([$action, $id]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

// --- Traitement ---
try {
    // Action spéciale : marquer une notification comme lue
    if ($action === 'read_notification') {
        admin_require_module('notifications', true);
        if (!$id || $id < 1) {
            throw new InvalidArgumentException('Notification invalide.');
        }
        db()->prepare('UPDATE notifications SET lu_at=COALESCE(lu_at,NOW()) WHERE id=?')->execute([$id]);
    }
    // Action spéciale : archivage (soft delete) d'une destination
    elseif ($action === 'delete_destination') {
        admin_require_module('destinations', true);
        if (!$id || $id < 1) {
            throw new InvalidArgumentException('Destination invalide.');
        }
        db()->prepare("UPDATE destinations SET deleted_at=NOW() WHERE id=? AND deleted_at IS NULL")->execute([$id]);
    }
    // Changement de statut standard
    else {
        $config = $configuration[$module] ?? null;
        if (!$config || !in_array($action, $config['values'], true) || !$id || $id < 1) {
            throw new InvalidArgumentException('Action ou élément invalide.');
        }

        admin_require_module($config['module'], true);

        // Validations métier
        actions_validate_transition($module, $action, (int)$id, $user);

        // Mise à jour (cas spécial utilisateurs avec transaction)
        if ($module === 'users' && $action !== 'actif') {
            actions_update_user_status($action, (int)$id, $user);
            $statement = null; // pas de rowCount vérifiable ici
        } else {
            // Table et colonne contrôlées par la whitelist $configuration
            $statement = db()->prepare("UPDATE {$config['table']} SET {$config['column']}=? WHERE id=?");
            $statement->execute([$action, $id]);
        }

        if ($statement !== null && $statement->rowCount() === 0) {
            $successMessage = 'Aucun changement : l’élément est peut-être déjà dans cet état.';
        }
    }

    admin_flash($successMessage);
} catch (Throwable $e) {
    error_log(sprintf(
        '[actions.php] module=%s action=%s id=%s user=%s error=%s',
        $module, $action, $id ?: 'null', $user['id'] ?? 'unknown', $e->getMessage()
    ));
    admin_flash('Échec de la modification. Vérifiez les données et vos permissions.');
}

header('Location: index.php?module=' . rawurlencode($module));
exit;