<?php
declare(strict_types=1);
final class AuthController {
    public static function register(): never {
        $data = request_json();
        $name = trim((string)($data['nom'] ?? ''));
        $firstName = trim((string)($data['prenom'] ?? ''));
        $phone = trim((string)($data['telephone'] ?? ''));
        $email = strtolower(trim((string)($data['email'] ?? '')));
        $password = (string)($data['mot_de_passe'] ?? '');
        if ($name === '' || mb_strlen($name) > 100 || mb_strlen($firstName) > 100 || mb_strlen($phone) > 30 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190 || strlen($password) < 10) {
            api_error('validation_error', 'Nom requis; prénom et téléphone limités respectivement à 100 et 30 caractères, courriel valide et mot de passe de 10 caractères minimum requis.', 422);
        }
        $pdo = db();
        $stmt = $pdo->prepare('SELECT id FROM utilisateurs WHERE email = ? AND deleted_at IS NULL');
        $stmt->execute([$email]);
        if ($stmt->fetch()) api_error('email_exists', 'Cette adresse courriel est déjà utilisée.', 409);
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO utilisateurs(nom,prenom,telephone,email,`role`,mot_de_passe_hash,langue_id) VALUES(?,?,?,?, 'Client',?,(SELECT id FROM langues WHERE code='fr' AND actif=1 LIMIT 1))");
            $stmt->execute([$name, $firstName === '' ? null : $firstName, $phone === '' ? null : $phone, $email, password_hash($password, PASSWORD_DEFAULT)]);
            $id = (int)$pdo->lastInsertId();
            $passport = $pdo->prepare('INSERT INTO passeports(utilisateur_id,numero,date_emission) VALUES(?,?,CURRENT_DATE)');
            $passport->execute([$id, 'TI-' . strtoupper(bin2hex(random_bytes(6)))]);
            $settings = $pdo->prepare('INSERT INTO parametres_utilisateurs(utilisateur_id) VALUES(?)');
            $settings->execute([$id]);
            $token = self::issueToken($id);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
        json_response(['data' => ['id' => $id, 'nom' => $name, 'prenom' => $firstName ?: null, 'telephone' => $phone ?: null, 'email' => $email, 'role' => 'Client'], 'token' => $token], 201);
    }
    public static function login(): never {
        $data = request_json();
        $email = strtolower(trim((string)($data['email'] ?? '')));
        $password = (string)($data['mot_de_passe'] ?? '');
        $emailHash = hash('sha256', $email);
        $ipHash = hash('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        $limits = db()->prepare("SELECT SUM(email_hash=? AND reussie=0) AS email_failures,SUM(ip_hash=? AND reussie=0) AS ip_failures FROM tentatives_auth WHERE created_at>=DATE_SUB(NOW(),INTERVAL 15 MINUTE)");
        $limits->execute([$emailHash, $ipHash]);
        $counts = $limits->fetch() ?: ['email_failures' => 0, 'ip_failures' => 0];
        if ((int)$counts['email_failures'] >= 5 || (int)$counts['ip_failures'] >= 30) {
            api_error('too_many_attempts', 'Trop de tentatives. Réessayez plus tard.', 429);
        }
        $stmt = db()->prepare('SELECT id,nom,prenom,email,mot_de_passe_hash,statut FROM utilisateurs WHERE email=? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$email]); $user = $stmt->fetch();
        $valid = $user && $user['statut'] === 'actif' && password_verify($password, $user['mot_de_passe_hash']);
        $attempt = db()->prepare('INSERT INTO tentatives_auth(email_hash,ip_hash,reussie) VALUES(?,?,?)');
        $attempt->execute([$emailHash, $ipHash, $valid ? 1 : 0]);
        if (!$valid) api_error('invalid_credentials', 'Adresse courriel ou mot de passe incorrect.', 401);
        db()->prepare('UPDATE utilisateurs SET last_login_at=NOW() WHERE id=?')->execute([$user['id']]);
        unset($user['mot_de_passe_hash'], $user['statut']);
        $user['role'] = self::roleCode((int)$user['id']);
        json_response(['data' => $user, 'token' => self::issueToken((int)$user['id'])]);
    }
    public static function me(): never {
        $id = AuthMiddleware::userId();
        $stmt = db()->prepare('SELECT id,nom,prenom,email,telephone,avatar_url,created_at FROM utilisateurs WHERE id=? AND deleted_at IS NULL');
        $stmt->execute([$id]); $user = $stmt->fetch();
        if (!$user) api_error('not_found', 'Compte introuvable.', 404);
        $user['role'] = self::roleCode($id);
        json_response(['data' => $user]);
    }

    public static function uploadAvatar(): never {
        $id = AuthMiddleware::userId();
        $upload = $_FILES['avatar'] ?? null;
        if (!is_array($upload) || !isset($upload['error'], $upload['tmp_name'], $upload['size'])) {
            api_error('validation_error', 'Sélectionnez une image de profil.', 422);
        }
        if ((int)$upload['error'] !== UPLOAD_ERR_OK) {
            api_error('upload_error', 'Le téléversement de l’image a échoué.', 422);
        }
        if ((int)$upload['size'] < 1 || (int)$upload['size'] > 5 * 1024 * 1024 || !is_uploaded_file($upload['tmp_name'])) {
            api_error('validation_error', 'L’image doit faire au maximum 5 Mo.', 422);
        }
        $image = @getimagesize($upload['tmp_name']);
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($upload['tmp_name']);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!$image || !isset($extensions[$mime]) || ($image['mime'] ?? '') !== $mime) {
            api_error('validation_error', 'Formats acceptés : JPEG, PNG ou WebP.', 422);
        }
        $width = (int)($image[0] ?? 0);
        $height = (int)($image[1] ?? 0);
        if ($width < 1 || $height < 1 || $width > 8000 || $height > 8000 || $width * $height > 25_000_000) {
            api_error('validation_error', 'La résolution de l’image est trop élevée.', 422);
        }
        $directory = dirname(__DIR__) . '/public/uploads/avatars';
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new RuntimeException('Impossible de préparer le stockage des avatars.');
        }
        $filename = bin2hex(random_bytes(20)) . '.' . $extensions[$mime];
        if (!move_uploaded_file($upload['tmp_name'], $directory . '/' . $filename)) {
            throw new RuntimeException('Impossible d’enregistrer l’avatar.');
        }
        $url = 'uploads/avatars/' . $filename;
        db()->prepare('UPDATE utilisateurs SET avatar_url=? WHERE id=? AND deleted_at IS NULL')->execute([$url, $id]);
        json_response(['data' => ['avatar_url' => $url]]);
    }

    public static function updateMe(): never {
        $id = AuthMiddleware::userId();
        $data = request_json();
        $name = trim((string)($data['nom'] ?? ''));
        $firstName = trim((string)($data['prenom'] ?? ''));
        $phone = trim((string)($data['telephone'] ?? ''));
        if ($name === '' || mb_strlen($name) > 100 || mb_strlen($firstName) > 100 || mb_strlen($phone) > 30) {
            api_error('validation_error', 'Nom requis; prénom et téléphone limités à 100 et 30 caractères.', 422);
        }
        $stmt = db()->prepare('UPDATE utilisateurs SET nom=?,prenom=?,telephone=? WHERE id=? AND deleted_at IS NULL');
        $stmt->execute([$name,$firstName === '' ? null : $firstName,$phone === '' ? null : $phone,$id]);
        BadgeService::evaluate(db(),$id);
        self::me();
    }

    public static function forgotPassword(): never {
        $data = request_json();
        $email = strtolower(trim((string)($data['email'] ?? '')));
        $delivery = null;
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $pdo = db();
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("SELECT id,email FROM utilisateurs WHERE email=? AND statut='actif' AND deleted_at IS NULL LIMIT 1 FOR UPDATE");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                if ($user) {
                    $limit = $pdo->prepare('SELECT COUNT(*) FROM historique_mot_de_passe WHERE utilisateur_id=? AND created_at>=DATE_SUB(NOW(),INTERVAL 1 HOUR)');
                    $limit->execute([$user['id']]);
                    if ((int)$limit->fetchColumn() < 3) {
                        $token = bin2hex(random_bytes(32));
                        $expiresAt = (new DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s');
                        $pdo->prepare('UPDATE historique_mot_de_passe SET utilise_at=NOW() WHERE utilisateur_id=? AND utilise_at IS NULL')
                            ->execute([$user['id']]);
                        $pdo->prepare('INSERT INTO historique_mot_de_passe(utilisateur_id,jeton_hash,expire_at) VALUES(?,?,?)')
                            ->execute([$user['id'],hash('sha256',$token),$expiresAt]);
                        $delivery = ['email' => $user['email'], 'token' => $token];
                    }
                }
                $pdo->commit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                throw $e;
            }
            if ($delivery !== null) {
                $token = $delivery['token'];
                $from = env_value('MAIL_FROM');
                if ($from && filter_var($from, FILTER_VALIDATE_EMAIL)) {
                    $body = "Votre code de réinitialisation (valide une heure) :\n\n$token\n\nSaisissez ce code dans l’application Tourisme Ivoire.";
                    $sent = mail($delivery['email'], 'Code de réinitialisation — Tourisme Ivoire', $body, "From: $from");
                    if (!$sent) error_log('Password reset email could not be sent.');
                } else {
                    error_log('Password reset requested but MAIL_FROM is not configured.');
                }
            }
        }
        json_response(['data' => ['message' => 'Si un compte correspond à cette adresse, un code de réinitialisation sera envoyé.']]);
    }

    public static function resetPassword(): never {
        $data = request_json();
        $email = strtolower(trim((string)($data['email'] ?? '')));
        $token = (string)($data['token'] ?? '');
        $password = (string)($data['mot_de_passe'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190 || strlen($token) !== 64 || strlen($password) < 10) {
            api_error('validation_error', 'Adresse, jeton ou nouveau mot de passe invalide (10 caractères minimum).', 422);
        }
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT h.id,h.utilisateur_id FROM historique_mot_de_passe h JOIN utilisateurs u ON u.id=h.utilisateur_id WHERE u.email=? AND u.deleted_at IS NULL AND h.jeton_hash=? AND h.utilise_at IS NULL AND h.expire_at>NOW() LIMIT 1 FOR UPDATE");
            $stmt->execute([$email,hash('sha256',$token)]);
            $reset = $stmt->fetch();
            if (!$reset) {
                $pdo->rollBack();
                api_error('invalid_reset_token', 'Le lien de réinitialisation est invalide ou expiré.', 400);
            }
            $pdo->prepare('UPDATE utilisateurs SET mot_de_passe_hash=? WHERE id=?')->execute([password_hash($password,PASSWORD_DEFAULT),$reset['utilisateur_id']]);
            $pdo->prepare('UPDATE historique_mot_de_passe SET utilise_at=NOW() WHERE utilisateur_id=? AND utilise_at IS NULL')->execute([$reset['utilisateur_id']]);
            $pdo->prepare('UPDATE jetons_auth SET revoque_at=NOW() WHERE utilisateur_id=? AND revoque_at IS NULL')->execute([$reset['utilisateur_id']]);
            $pdo->commit();
            json_response(['data' => ['message' => 'Mot de passe réinitialisé. Connectez-vous avec votre nouveau mot de passe.']]);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public static function logout(): never {
        $userId = AuthMiddleware::userId();
        $claims = AuthMiddleware::claims();
        db()->prepare('UPDATE jetons_auth SET revoque_at=NOW() WHERE utilisateur_id=? AND jti=? AND revoque_at IS NULL')->execute([$userId, $claims['jti']]);
        json_response(['data' => ['message' => 'Déconnexion effectuée.']]);
    }

    private static function issueToken(int $userId): string {
        $token = JwtService::issue(['sub' => $userId]);
        $claims = JwtService::verify($token);
        if (!$claims || empty($claims['jti'])) throw new RuntimeException('Impossible de créer le jeton de session.');
        $stmt = db()->prepare('INSERT INTO jetons_auth(utilisateur_id,jti,expire_at) VALUES(?,?,FROM_UNIXTIME(?))');
        $stmt->execute([$userId, $claims['jti'], $claims['exp']]);
        return $token;
    }

    private static function roleCode(int $userId): string {
        $stmt = db()->prepare('SELECT `role` FROM utilisateurs WHERE id=? AND deleted_at IS NULL');
        $stmt->execute([$userId]);
        $role = $stmt->fetchColumn();
        if (!in_array($role, ['Sadmin','Client'], true)) {
            api_error('invalid_role', 'Le rôle du compte est invalide. Contactez un administrateur.', 403);
        }
        return (string)$role;
    }
}
