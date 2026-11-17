<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/admin/bootstrap.php';

$user = admin_require_login();

 
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    exit('Méthode non autorisée.');
}
admin_check_csrf();

 
$module = (string)($_POST['module'] ?? '');
$id     = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$action = (string)($_POST['action'] ?? '');

 
$configuration = [
    'users'          => ['table'=>'utilisateurs',        'column'=>'statut',             'values'=>['actif','suspendu','en_attente'],        'module'=>'users'],
    'regions'        => ['table'=>'regions',             'column'=>'statut',             'values'=>['actif','inactif'],                       'module'=>'geography'],
    'districts'      => ['table'=>'districts',           'column'=>'statut',             'values'=>['actif','inactif'],                       'module'=>'geography'],
    'localities'     => ['table'=>'localites',           'column'=>'statut',             'values'=>['actif','inactif'],                       'module'=>'geography'],
    'categories'     => ['table'=>'categories',          'column'=>'statut',             'values'=>['actif','inactif'],                       'module'=>'categories'],
    'destinations'   => ['table'=>'destinations',        'column'=>'statut_publication', 'values'=>['brouillon','publie','archive'],           'module'=>'destinations'],
    'heritage'       => ['table'=>'patrimoines',         'column'=>'statut',             'values'=>['brouillon','publie','archive'],           'module'=>'heritage'],
    'virtual_visits' => ['table'=>'visites_virtuelles',  'column'=>'statut',             'values'=>['actif','inactif'],                       'module'=>'virtual_visits'],
    'images'         => ['table'=>'medias',             'column'=>'statut',             'values'=>['actif','inactif'],                       'module'=>'images'],
    'videos'         => ['table'=>'videos',              'column'=>'statut',             'values'=>['en_attente','publie','rejete','archive'], 'module'=>'videos'],
    'quizzes'        => ['table'=>'quiz',                'column'=>'statut',             'values'=>['brouillon','publie','archive'],           'module'=>'quizzes'],
    'badges'         => ['table'=>'badges',              'column'=>'statut',             'values'=>['actif','inactif'],                       'module'=>'badges'],
    'reservations'   => ['table'=>'reservations',        'column'=>'statut',             'values'=>['demande','confirmee','annulee','terminee'],'module'=>'reservations'],
    'reviews'        => ['table'=>'avis',                'column'=>'statut',             'values'=>['en_attente','publie','rejete'],           'module'=>'reviews'],
];

$successMessage = 'Modification enregistrée.';

 



function actions_validate_transition(string $module, string $action, int $id, array $user): void
{
    $pdo = db();

     
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

     
    if ($module === 'destinations' && $action === 'publie') {
        $source = $pdo->prepare('SELECT source_reference FROM destinations WHERE id=? AND deleted_at IS NULL');
        $source->execute([$id]);
        $reference = $source->fetchColumn();
        $scheme    = is_string($reference) ? strtolower((string)parse_url($reference, PHP_URL_SCHEME)) : '';
        if (!$reference || !in_array($scheme, ['http','https'], true)) {
            throw new RuntimeException('La publication est bloquée : source HTTP(S) vérifiable manquante.');
        }
    }

     
    if ($module === 'videos' && $action === 'publie') {
        $rights = $pdo->prepare('SELECT autorisation_diffusion FROM videos WHERE id=? AND deleted_at IS NULL');
        $rights->execute([$id]);
        if (!$rights->fetchColumn()) {
            throw new RuntimeException('La publication est bloquée : autorisation de diffusion absente.');
        }
    }

     
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

function actions_update_media_status(string $status, int $id): void
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $query = $pdo->prepare('SELECT destination_id,profil_utilisateur_id,chemin_fichier,est_principale FROM medias WHERE id=? FOR UPDATE');
        $query->execute([$id]);
        $media = $query->fetch();
        if (!$media) throw new RuntimeException('Média introuvable.');

        $relative = ltrim(str_replace('\\','/',(string)$media['chemin_fichier']),'/');
        if (!str_starts_with($relative,'uploads/')) throw new RuntimeException('Chemin du média invalide.');
        $script = str_replace('\\','/',(string)($_SERVER['SCRIPT_NAME'] ?? '/admin/actions.php'));
        $base = dirname(dirname($script));
        $base = $base === '/' || $base === '.' ? '' : rtrim($base,'/');
        $url = $base . '/' . $relative;
        $urls = array_values(array_unique([$url,'/'.$relative,$relative]));
        $placeholders = implode(',',array_fill(0,count($urls),'?'));

        $pdo->prepare('UPDATE medias SET statut=?,est_principale=IF(?=\'actif\',est_principale,0) WHERE id=?')->execute([$status,$status,$id]);
        if ($status === 'inactif') {
            $pdo->prepare('UPDATE destinations SET image_principale=NULL WHERE image_principale IN (' . $placeholders . ')')->execute($urls);
            if ($media['profil_utilisateur_id']!==null) {
                $avatarPaths=[$relative,'/'.$relative];$avatarMarks=implode(',',array_fill(0,count($avatarPaths),'?'));
                $pdo->prepare('UPDATE utilisateurs SET avatar_url=NULL WHERE id=? AND avatar_url IN (' . $avatarMarks . ')')->execute(array_merge([(int)$media['profil_utilisateur_id']],$avatarPaths));
            }
        } elseif ($media['destination_id'] !== null && !empty($media['est_principale'])) {
            $destinationId = (int)$media['destination_id'];
            $pdo->prepare('UPDATE medias SET est_principale=0 WHERE destination_id=? AND id<>?')->execute([$destinationId,$id]);
            $pdo->prepare('UPDATE destinations SET image_principale=? WHERE id=?')->execute([$url,$destinationId]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

 
try {
     
    if ($action === 'read_notification') {
        admin_require_module('notifications', true);
        if (!$id || $id < 1) {
            throw new InvalidArgumentException('Notification invalide.');
        }
        db()->prepare('UPDATE notifications SET lu_at=COALESCE(lu_at,NOW()) WHERE id=?')->execute([$id]);
    }
     
    elseif ($action === 'delete_destination') {
        admin_require_module('destinations', true);
        if (!$id || $id < 1) {
            throw new InvalidArgumentException('Destination invalide.');
        }
        db()->prepare("UPDATE destinations SET deleted_at=NOW() WHERE id=? AND deleted_at IS NULL")->execute([$id]);
    }
     
    else {
        $config = $configuration[$module] ?? null;
        if (!$config || !in_array($action, $config['values'], true) || !$id || $id < 1) {
            throw new InvalidArgumentException('Action ou élément invalide.');
        }

        admin_require_module($config['module'], true);

         
        actions_validate_transition($module, $action, (int)$id, $user);

         
        if ($module === 'images') {
            actions_update_media_status($action, (int)$id);
            $statement = null;
        } elseif ($module === 'users' && $action !== 'actif') {
            actions_update_user_status($action, (int)$id, $user);
            $statement = null;  
        } elseif ($module === 'reservations') {
            $pdo = db();
            $pdo->beginTransaction();
            try {
                $current = $pdo->prepare('SELECT utilisateur_id,reference,statut FROM reservations WHERE id=? FOR UPDATE');
                $current->execute([$id]);
                $reservation = $current->fetch();
                if (!$reservation) throw new RuntimeException('Réservation introuvable.');
                $pdo->prepare('UPDATE reservations SET statut=? WHERE id=?')->execute([$action, $id]);
                $pdo->prepare('UPDATE details_reservations SET statut=? WHERE reservation_id=?')->execute([$action, $id]);
                if ($reservation['statut'] !== $action) {
                    $pdo->prepare('INSERT INTO notifications(utilisateur_id,titre,contenu,type,cible_url) VALUES(?,?,?,?,?)')->execute([(int)$reservation['utilisateur_id'], 'Mise à jour de votre réservation', 'La réservation ' . $reservation['reference'] . ' est maintenant au statut « ' . $action . ' ».', 'reservation', 'reservations']);
                }
                $pdo->commit();
                $statement = null;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                throw $e;
            }
        } else {
             
            $statement = db()->prepare("UPDATE {$config['table']} SET {$config['column']}=? WHERE id=?");
            $statement->execute([$action, $id]);
        }

        if ($statement !== null && $statement->rowCount() === 0) {
            $successMessage = 'Aucun changement : l’élément est peut-être déjà dans cet état.';
        }
    }

    admin_flash(
        $successMessage,
        str_starts_with($successMessage, 'Aucun changement') ? 'info' : 'success'
    );
} catch (Throwable $e) {
    error_log(sprintf(
        '[actions.php] module=%s action=%s id=%s user=%s error=%s',
        $module, $action, $id ?: 'null', $user['id'] ?? 'unknown', $e->getMessage()
    ));
    admin_flash('Échec de la modification. Vérifiez les données et vos permissions.', 'error');
}

header('Location: index.php?module=' . rawurlencode($module));
exit;
