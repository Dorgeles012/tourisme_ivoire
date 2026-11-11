<?php
declare(strict_types=1);
final class VideoController {
    public static function index(): never {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(30, max(1, (int)($_GET['per_page'] ?? 12)));
        $stmt = db()->prepare("SELECT v.id,v.titre,v.description,v.media_url,v.miniature_url,v.duree_secondes,v.created_at,u.id AS auteur_id,u.nom AS auteur,d.nom AS destination,(SELECT COUNT(*) FROM likes lk WHERE lk.video_id=v.id) AS likes,(SELECT COUNT(*) FROM commentaires co WHERE co.video_id=v.id AND co.statut='publie' AND co.deleted_at IS NULL) AS commentaires FROM videos v JOIN utilisateurs u ON u.id=v.utilisateur_id LEFT JOIN destinations d ON d.id=v.destination_id WHERE v.statut='publie' AND v.deleted_at IS NULL ORDER BY v.created_at DESC LIMIT ? OFFSET ?");
        $stmt->bindValue(1, $perPage, PDO::PARAM_INT);
        $stmt->bindValue(2, ($page - 1) * $perPage, PDO::PARAM_INT);
        $stmt->execute();
        json_response(['data' => ['items' => $stmt->fetchAll(), 'pagination' => ['page' => $page, 'per_page' => $perPage]]]);
    }

    public static function show(int $id): never {
        $stmt = db()->prepare("SELECT v.id,v.titre,v.description,v.media_url,v.miniature_url,v.duree_secondes,v.created_at,u.id AS auteur_id,u.nom AS auteur,d.nom AS destination,(SELECT COUNT(*) FROM likes lk WHERE lk.video_id=v.id) AS likes FROM videos v JOIN utilisateurs u ON u.id=v.utilisateur_id LEFT JOIN destinations d ON d.id=v.destination_id WHERE v.id=? AND v.statut='publie' AND v.deleted_at IS NULL LIMIT 1");
        $stmt->execute([$id]);
        $video = $stmt->fetch();
        if (!$video) api_error('not_found', 'Vidéo introuvable.', 404);
        json_response(['data' => $video]);
    }

    public static function upload(): never {
        $userId = AuthMiddleware::userId();
        $title = trim((string)($_POST['titre'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        if ($title === '' || mb_strlen($title) > 180) api_error('validation_error', 'Un titre de 1 à 180 caractères est requis.', 422);
        if (($_POST['autorisation_diffusion'] ?? '') !== '1') api_error('validation_error', 'Confirmez que vous avez les droits nécessaires pour diffuser cette vidéo.', 422);
        $file = $_FILES['video'] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) api_error('upload_error', 'Sélectionnez un fichier vidéo valide.', 422);
        if (($file['size'] ?? 0) < 1 || $file['size'] > 104857600 || !is_uploaded_file($file['tmp_name'])) api_error('upload_error', 'La vidéo doit faire au maximum 100 Mo.', 422);
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $extensions = ['video/mp4' => 'mp4', 'video/webm' => 'webm', 'video/quicktime' => 'mov'];
        if (!isset($extensions[$mime])) api_error('upload_error', 'Format accepté : MP4, WebM ou MOV.', 415);
        $destinationId = filter_var($_POST['destination_id'] ?? null, FILTER_VALIDATE_INT);
        if ($destinationId) {
            $check = db()->prepare("SELECT id FROM destinations WHERE id=? AND statut_publication='publie' AND deleted_at IS NULL");
            $check->execute([$destinationId]);
            if (!$check->fetchColumn()) api_error('not_found', 'Destination introuvable.', 404);
        }

        $directory = APP_ROOT . '/public/uploads/videos';
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new RuntimeException('Impossible de créer le répertoire de stockage vidéo.');
        }
        $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
        $path = $directory . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $path)) throw new RuntimeException('Échec du stockage de la vidéo téléversée.');
        try {
            $url = '/uploads/videos/' . $filename;
            $stmt = db()->prepare("INSERT INTO videos(utilisateur_id,destination_id,titre,description,media_url,autorisation_diffusion,statut) VALUES(?,?,?,?,?,1,'en_attente')");
            $stmt->execute([$userId,$destinationId ?: null,$title,$description === '' ? null : $description,$url]);
            json_response(['data' => ['id' => (int)db()->lastInsertId(), 'statut' => 'en_attente', 'media_url' => $url]], 201);
        } catch (Throwable $e) {
            if (is_file($path)) unlink($path);
            throw $e;
        }
    }

    public static function toggleLike(int $videoId): never {
        $userId = AuthMiddleware::userId();
        $pdo = db();
        $check = $pdo->prepare("SELECT id FROM videos WHERE id=? AND statut='publie' AND deleted_at IS NULL");
        $check->execute([$videoId]);
        if (!$check->fetchColumn()) api_error('not_found', 'Vidéo introuvable.', 404);
        $existing = $pdo->prepare('SELECT id FROM likes WHERE utilisateur_id=? AND video_id=?');
        $existing->execute([$userId,$videoId]);
        $likeId = $existing->fetchColumn();
        if ($likeId) {
            $pdo->prepare('DELETE FROM likes WHERE id=?')->execute([$likeId]);
            json_response(['data' => ['liked' => false]]);
        }
        $pdo->prepare('INSERT INTO likes(utilisateur_id,video_id) VALUES(?,?)')->execute([$userId,$videoId]);
        json_response(['data' => ['liked' => true]], 201);
    }

    public static function comments(int $videoId): never {
        $check = db()->prepare("SELECT id FROM videos WHERE id=? AND statut='publie' AND deleted_at IS NULL");
        $check->execute([$videoId]);
        if (!$check->fetchColumn()) api_error('not_found', 'Vidéo introuvable.', 404);
        $stmt = db()->prepare("SELECT c.id,c.contenu,c.created_at,u.id AS auteur_id,u.nom AS auteur FROM commentaires c JOIN utilisateurs u ON u.id=c.utilisateur_id WHERE c.video_id=? AND c.statut='publie' AND c.deleted_at IS NULL ORDER BY c.created_at DESC LIMIT 100");
        $stmt->execute([$videoId]);
        json_response(['data' => $stmt->fetchAll()]);
    }

    public static function comment(int $videoId): never {
        $userId = AuthMiddleware::userId();
        $data = request_json();
        $content = trim((string)($data['contenu'] ?? ''));
        if ($content === '' || mb_strlen($content) > 2000) api_error('validation_error', 'Un commentaire de 1 à 2 000 caractères est requis.', 422);
        $check = db()->prepare("SELECT id FROM videos WHERE id=? AND statut='publie' AND deleted_at IS NULL");
        $check->execute([$videoId]);
        if (!$check->fetchColumn()) api_error('not_found', 'Vidéo introuvable.', 404);
        $stmt = db()->prepare("INSERT INTO commentaires(utilisateur_id,video_id,contenu,statut) VALUES(?,?,?,'publie')");
        $stmt->execute([$userId,$videoId,$content]);
        json_response(['data' => ['id' => (int)db()->lastInsertId(), 'contenu' => $content]], 201);
    }
}
