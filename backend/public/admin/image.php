<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/admin/bootstrap.php';
$admin = admin_require_module('images', true);
$pdo = db();
$adminId = (int)$admin['id'];
$categories = [
    'logo'=>'Logo','bannieres'=>'Bannières','destinations'=>'Destinations','sites_touristiques'=>'Sites touristiques',
    'hotels'=>'Hôtels','restaurants'=>'Restaurants','patrimoine'=>'Patrimoine','evenements'=>'Événements',
    'transports'=>'Transports','profils'=>'Profils','articles'=>'Articles','videos'=>'Vidéos',
    'galerie'=>'Galerie','interface'=>'Interface','autres'=>'Autres',
];
$maxFileSize = 8 * 1024 * 1024;
$maxBatch = 4;

function media_public_base(): string {
    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/admin/image.php'));
    $base = dirname(dirname($script));
    return $base === '/' || $base === '.' ? '' : rtrim($base, '/');
}
function media_public_url(string $relativePath): string {
    $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
    return media_public_base() . '/' . $relativePath;
}
function media_known_urls(string $relativePath): array {
    $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
    return array_values(array_unique([media_public_url($relativePath), '/' . $relativePath, $relativePath]));
}
function media_disk_path(string $relativePath): ?string {
    $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
    if (!preg_match('~^uploads/(?:images|logo|bannieres|destinations|sites_touristiques|hotels|restaurants|patrimoine|evenements|transports|profils|articles|videos|galerie|interface|autres)/([A-Za-z0-9._-]{1,120})$~D', $relativePath, $match)) return null;
    if (str_contains($match[1], '..')) return null;
    return dirname(__DIR__) . '/' . $relativePath;
}
function media_flash_redirect(string $type, string $key, string $suffix = ''): never {
    admin_flash('media:' . $key, $type);
    header('Location: image.php' . $suffix);
    exit;
}
function media_json(int $status, array $payload): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
function media_request_is_ajax(): bool {
    return strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
}
function media_bytes(string $value): int {
    $value = trim($value);
    if ($value === '') return 0;
    $unit = strtolower($value[strlen($value) - 1]);
    $number = (float)$value;
    return (int)match ($unit) { 'g' => $number * 1024 ** 3, 'm' => $number * 1024 ** 2, 'k' => $number * 1024, default => $number };
}
function media_upload_error(int $code): string {
    return match ($code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'La taille dépasse la limite PHP configurée.',
        UPLOAD_ERR_PARTIAL => 'Le fichier n’a été reçu que partiellement. Réessayez.',
        UPLOAD_ERR_NO_TMP_DIR => 'Le dossier temporaire PHP est absent.',
        UPLOAD_ERR_CANT_WRITE => 'PHP ne peut pas écrire le fichier temporaire.',
        UPLOAD_ERR_EXTENSION => 'Une extension PHP a interrompu le téléversement.',
        default => 'Sélectionnez au moins une image valide.',
    };
}
function media_validate_category(string $category, array $categories): bool {
    return array_key_exists($category, $categories);
}
function media_sync_destination(PDO $pdo, int $destinationId, string $relativePath, string $publicUrl, string $status, bool $isMain): void {
    if ($isMain && $status === 'actif') {
        $pdo->prepare('UPDATE medias SET est_principale=0 WHERE destination_id=? AND chemin_fichier<>?')->execute([$destinationId,$relativePath]);
        $pdo->prepare('UPDATE destinations SET image_principale=? WHERE id=?')->execute([$publicUrl, $destinationId]);
    } elseif ($status !== 'actif') {
        $pdo->prepare('UPDATE destinations SET image_principale=NULL WHERE id=? AND image_principale=?')->execute([$destinationId, $publicUrl]);
    } elseif (!$isMain) {
        $pdo->prepare('UPDATE destinations SET image_principale=NULL WHERE id=? AND image_principale=?')->execute([$destinationId, $publicUrl]);
    }
    $pdo->prepare('INSERT INTO images_destinations(destination_id,url,texte_alternatif,credit,licence,est_principale,statut) VALUES(?,?,?,?,?,?,?)')
        ->execute([$destinationId, $publicUrl, $alt, $credit, $licence, $isMain && $status === 'actif' ? 1 : 0, $status]);
}

$isAjax = media_request_is_ajax();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($contentLength > 0 && media_bytes((string)ini_get('post_max_size')) > 0 && $contentLength > media_bytes((string)ini_get('post_max_size'))) {
        $message = 'La requête dépasse post_max_size (' . ini_get('post_max_size') . '). Réduisez le nombre ou la taille des images.';
        if ($isAjax) media_json(413, ['success'=>false,'message'=>$message]);
        media_flash_redirect('error', 'post_too_large');
    }
    if (!isset($_SESSION['admin_csrf']) || !hash_equals((string)$_SESSION['admin_csrf'], (string)($_POST['csrf'] ?? ''))) {
        if ($isAjax) media_json(403, ['success'=>false,'message'=>'Jeton CSRF invalide. Rechargez la page et réessayez.']);
        media_flash_redirect('error', 'csrf');
    }
    $action = (string)($_POST['action'] ?? 'upload');
    $requestId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    $redirect = $requestId && $requestId > 0 ? 'image.php?id=' . (int)$requestId : 'image.php';
    try {
        if ($action === 'upload') {
            ignore_user_abort(true);
            $category = trim((string)($_POST['categorie'] ?? ''));
            $description = trim((string)($_POST['description'] ?? ''));
            $alt = trim((string)($_POST['texte_alternatif'] ?? ''));
            $credit = trim((string)($_POST['credit'] ?? ''));
            $licence = trim((string)($_POST['licence'] ?? ''));
            $destinationRaw = trim((string)($_POST['destination_id'] ?? ''));
            $profileUserRaw = trim((string)($_POST['profil_utilisateur_id'] ?? ''));
            $destinationId = null;
            $profileUserId = null;
            $isMain = isset($_POST['principale']);
            if (!media_validate_category($category, $categories)) throw new InvalidArgumentException('Choisissez une catégorie valide.');
            if (strlen($description) > 10000 || strlen($alt) > 255 || strlen($credit) > 190 || strlen($licence) > 190) throw new InvalidArgumentException('Un des champs dépasse la longueur autorisée.');
            if ($destinationRaw !== '') {
                $destinationId = filter_var($destinationRaw, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
                if ($destinationId === false || !in_array($category, ['destinations','sites_touristiques'], true)) throw new InvalidArgumentException('Une destination ne peut être associée qu’aux catégories Destinations ou Sites touristiques.');
                $check = $pdo->prepare('SELECT id FROM destinations WHERE id=? AND deleted_at IS NULL');
                $check->execute([$destinationId]);
                if (!$check->fetchColumn()) throw new InvalidArgumentException('La destination sélectionnée est introuvable.');
                $destinationId = (int)$destinationId;
            }
            if ($profileUserRaw !== '') {
                $profileUserId=filter_var($profileUserRaw,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
                if ($profileUserId===false || $category!=='profils') throw new InvalidArgumentException('Un compte ne peut être associé qu’à une image de catégorie Profils.');
                $check=$pdo->prepare('SELECT id FROM utilisateurs WHERE id=? AND deleted_at IS NULL');$check->execute([$profileUserId]);
                if (!$check->fetchColumn()) throw new InvalidArgumentException('Le compte sélectionné est introuvable.');
                $profileUserId=(int)$profileUserId;
            }
            if ($isMain && $destinationId === null) throw new InvalidArgumentException('Choisissez une destination pour définir une image principale.');

            $fileSet = $_FILES['images'] ?? $_FILES['image'] ?? null;
            if (!is_array($fileSet)) throw new InvalidArgumentException('Sélectionnez des images à téléverser.');
            $files = [];
            if (is_array($fileSet['name'] ?? null)) {
                foreach ($fileSet['name'] as $i => $name) $files[] = ['name'=>$name,'type'=>$fileSet['type'][$i]??'','tmp_name'=>$fileSet['tmp_name'][$i]??'','error'=>$fileSet['error'][$i]??UPLOAD_ERR_NO_FILE,'size'=>$fileSet['size'][$i]??0];
            } else {
                $files[] = $fileSet;
            }
            if (!$files || count($files) > $maxBatch) throw new InvalidArgumentException('Choisissez de 1 à ' . $maxBatch . ' images par envoi.');
            if ($profileUserId!==null && count($files)!==1) throw new InvalidArgumentException('Associez une seule image à un compte à la fois.');
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeExtensions = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
            if (defined('IMAGETYPE_AVIF')) $mimeExtensions['image/avif'] = 'avif';
            $validated = [];
            foreach ($files as $file) {
                $uploadError = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
                if ($uploadError !== UPLOAD_ERR_OK) throw new InvalidArgumentException(media_upload_error($uploadError));
                $tmp = (string)($file['tmp_name'] ?? '');
                $size = (int)($file['size'] ?? 0);
                if (!is_uploaded_file($tmp)) throw new InvalidArgumentException('Le fichier reçu n’est pas un upload PHP valide.');
                if ($size < 1 || $size > $maxFileSize) throw new InvalidArgumentException('Chaque image doit peser au maximum 8 Mo.');
                $mime = $finfo->file($tmp) ?: '';
                if (!isset($mimeExtensions[$mime])) throw new InvalidArgumentException('Format refusé. Utilisez JPG, PNG, WebP ou AVIF si PHP le prend en charge.');
                $info = @getimagesize($tmp);
                if (!$info || ($info['mime'] ?? '') !== $mime) throw new InvalidArgumentException('Le fichier ne contient pas une image décodable par ce serveur.');
                $width = (int)($info[0] ?? 0);
                $height = (int)($info[1] ?? 0);
                if ($width < 1 || $height < 1 || $width > 10000 || $height > 10000 || $width * $height > 40000000) throw new InvalidArgumentException('La résolution de cette image dépasse la limite autorisée.');
                $original = basename(str_replace('\\', '/', (string)($file['name'] ?? 'image')));
                $original = preg_replace('/[\x00-\x1F\x7F]/u', '', $original) ?? 'image';
                $original = substr($original, 0, 255);
                $validated[] = ['tmp'=>$tmp,'original'=>$original,'size'=>$size,'mime'=>$mime,'ext'=>$mimeExtensions[$mime],'width'=>$width,'height'=>$height];
            }

            $directory = dirname(__DIR__) . '/uploads/' . $category;
            if (!is_dir($directory) && !@mkdir($directory, 0750, true) && !is_dir($directory)) throw new RuntimeException('Impossible de créer le dossier de destination uploads/' . $category . '.');
            if (!is_writable($directory)) throw new RuntimeException('Le dossier uploads/' . $category . ' n’est pas accessible en écriture pour Apache/PHP.');
            $moved = [];
            $pdo->beginTransaction();
            try {
                foreach ($validated as $index => $file) {
                    if (connection_aborted()) throw new RuntimeException('Le téléversement a été annulé.');
                    $filename = bin2hex(random_bytes(20)) . '.' . $file['ext'];
                    $diskPath = $directory . DIRECTORY_SEPARATOR . $filename;
                    if (!move_uploaded_file($file['tmp'], $diskPath)) throw new RuntimeException('PHP n’a pas pu déplacer le fichier vers uploads/' . $category . '. Vérifiez les droits d’écriture.');
                    $relative = 'uploads/' . $category . '/' . $filename;
                    $publicUrl = media_public_url($relative);
                    $moved[] = $diskPath;
                    if (connection_aborted()) throw new RuntimeException('Le téléversement a été annulé.');
                    $mainForThis = $isMain && $index === 0;
                    $pdo->prepare('INSERT INTO medias(nom_original,nom_fichier,chemin_fichier,extension,type_mime,taille,largeur,hauteur,categorie,description,texte_alternatif,credit,licence,destination_id,profil_utilisateur_id,utilisateur_id,est_principale,statut) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,\'actif\')')
                        ->execute([$file['original'],$filename,$relative,$file['ext'],$file['mime'],$file['size'],$file['width'],$file['height'],$category,$description ?: null,$alt ?: null,$credit ?: null,$licence ?: null,$destinationId,$profileUserId,$adminId,$mainForThis ? 1 : 0]);
                    if ($destinationId !== null) media_sync_destination($pdo,$destinationId,$relative,$publicUrl,'actif',$mainForThis);
                    if ($profileUserId!==null) $pdo->prepare('UPDATE utilisateurs SET avatar_url=? WHERE id=?')->execute([$relative,$profileUserId]);
                }
                if (connection_aborted()) throw new RuntimeException('Le téléversement a été annulé.');
                $pdo->commit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                foreach ($moved as $path) if (is_file($path)) @unlink($path);
                throw $e;
            }
            if ($isAjax) media_json(201, ['success'=>true,'count'=>count($validated),'message'=>count($validated) . ' image(s) enregistrée(s).']);
            media_flash_redirect('success', 'upload_success');
        }

        if ($action === 'update') {
            if (!$requestId || $requestId < 1) throw new InvalidArgumentException('Média introuvable.');
            $find = $pdo->prepare('SELECT * FROM medias WHERE id=? LIMIT 1');
            $find->execute([$requestId]);
            $media = $find->fetch();
            if (!$media) throw new InvalidArgumentException('Média introuvable.');
            $category = trim((string)($_POST['categorie'] ?? ''));
            $description = trim((string)($_POST['description'] ?? ''));
            $alt = trim((string)($_POST['texte_alternatif'] ?? ''));
            $credit = trim((string)($_POST['credit'] ?? ''));
            $licence = trim((string)($_POST['licence'] ?? ''));
            $destinationRaw = trim((string)($_POST['destination_id'] ?? ''));
            $profileUserRaw = trim((string)($_POST['profil_utilisateur_id'] ?? ''));
            $status = (string)($_POST['statut'] ?? 'actif');
            $isMain = isset($_POST['principale']);
            if (!media_validate_category($category, $categories) || !in_array($status,['actif','inactif'],true)) throw new InvalidArgumentException('Catégorie ou statut invalide.');
            if (strlen($description)>10000 || strlen($alt)>255 || strlen($credit)>190 || strlen($licence)>190) throw new InvalidArgumentException('Un des champs dépasse la longueur autorisée.');
            $destinationId = null;
            if ($destinationRaw !== '') {
                $destinationId = filter_var($destinationRaw,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
                if ($destinationId===false || !in_array($category,['destinations','sites_touristiques'],true)) throw new InvalidArgumentException('Une destination ne peut être associée qu’aux catégories Destinations ou Sites touristiques.');
                $check=$pdo->prepare('SELECT id FROM destinations WHERE id=? AND deleted_at IS NULL'); $check->execute([$destinationId]);
                if (!$check->fetchColumn()) throw new InvalidArgumentException('La destination sélectionnée est introuvable.');
                $destinationId=(int)$destinationId;
            }
            $profileUserId=null;
            if ($profileUserRaw!=='') {
                $profileUserId=filter_var($profileUserRaw,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
                if ($profileUserId===false || $category!=='profils') throw new InvalidArgumentException('Un compte ne peut être associé qu’à une image de catégorie Profils.');
                $check=$pdo->prepare('SELECT id FROM utilisateurs WHERE id=? AND deleted_at IS NULL');$check->execute([$profileUserId]);
                if (!$check->fetchColumn()) throw new InvalidArgumentException('Le compte sélectionné est introuvable.');
                $profileUserId=(int)$profileUserId;
            }
            if ($isMain && ($destinationId===null || $status!=='actif')) throw new InvalidArgumentException('Une image principale doit être active et associée à une destination.');
            $oldRelative = ltrim(str_replace('\\','/',(string)$media['chemin_fichier']),'/');
            $oldDisk = media_disk_path($oldRelative);
            if ($oldDisk===null) throw new RuntimeException('Le chemin enregistré pour ce média est invalide.');
            $filename = basename((string)$media['nom_fichier']);
            $newRelative = 'uploads/' . $category . '/' . $filename;
            $newDisk = media_disk_path($newRelative);
            if ($newDisk===null) throw new RuntimeException('Le nouveau chemin de stockage est invalide.');
            $movedFile = false;
            if ($newRelative!==$oldRelative) {
                if (!is_file($oldDisk)) throw new RuntimeException('Le fichier source est absent du dossier uploads.');
                $newDirectory=dirname($newDisk);
                if (!is_dir($newDirectory) && !@mkdir($newDirectory,0750,true) && !is_dir($newDirectory)) throw new RuntimeException('Impossible de créer le dossier de catégorie.');
                if (!is_writable($newDirectory) || !@rename($oldDisk,$newDisk)) throw new RuntimeException('Impossible de déplacer le fichier vers sa nouvelle catégorie.');
                $movedFile=true;
            }
            $oldDestinationId = $media['destination_id'] !== null ? (int)$media['destination_id'] : null;
            $oldProfileUserId = $media['profil_utilisateur_id'] !== null ? (int)$media['profil_utilisateur_id'] : null;
            $oldUrls = media_known_urls($oldRelative);
            $newPublicUrl = media_public_url($newRelative);
            try {
                $pdo->beginTransaction();
                if ($oldDestinationId!==null) {
                    $placeholders=implode(',',array_fill(0,count($oldUrls),'?'));
                    $pdo->prepare('UPDATE destinations SET image_principale=NULL WHERE id=? AND image_principale IN (' . $placeholders . ')')->execute(array_merge([$oldDestinationId],$oldUrls));
                }
                if ($oldProfileUserId!==null) {
                    $avatarPaths=[$oldRelative,'/'.$oldRelative];$avatarMarks=implode(',',array_fill(0,count($avatarPaths),'?'));
                    $pdo->prepare('UPDATE utilisateurs SET avatar_url=NULL WHERE id=? AND avatar_url IN ('.$avatarMarks.')')->execute(array_merge([$oldProfileUserId],$avatarPaths));
                }
                $pdo->prepare('UPDATE medias SET chemin_fichier=?,categorie=?,description=?,texte_alternatif=?,credit=?,licence=?,destination_id=?,profil_utilisateur_id=?,est_principale=?,statut=? WHERE id=?')
                    ->execute([$newRelative,$category,$description ?: null,$alt ?: null,$credit ?: null,$licence ?: null,$destinationId,$profileUserId,$isMain?1:0,$status,$requestId]);
                if ($destinationId!==null) media_sync_destination($pdo,$destinationId,$newRelative,$newPublicUrl,$status,$isMain);
                if ($profileUserId!==null && $status==='actif') $pdo->prepare('UPDATE utilisateurs SET avatar_url=? WHERE id=?')->execute([$newRelative,$profileUserId]);
                $pdo->commit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                if ($movedFile) @rename($newDisk,$oldDisk);
                throw $e;
            }
            media_flash_redirect('success','update_success');
        }

        if ($action === 'delete') {
            if (!$requestId || $requestId<1) throw new InvalidArgumentException('Média introuvable.');
            $find=$pdo->prepare('SELECT * FROM medias WHERE id=? LIMIT 1'); $find->execute([$requestId]); $media=$find->fetch();
            if (!$media) throw new InvalidArgumentException('Média introuvable.');
            $relative=ltrim(str_replace('\\','/',(string)$media['chemin_fichier']),'/');
            $disk=media_disk_path($relative);
            if ($disk===null) throw new RuntimeException('Le chemin enregistré pour ce média est invalide.');
            $staged=null;
            if (is_file($disk)) {
                $staged=$disk.'.delete-'.bin2hex(random_bytes(8));
                if (!@rename($disk,$staged)) throw new RuntimeException('Impossible de préparer la suppression du fichier.');
            }
            try {
                $pdo->beginTransaction();
                $knownUrls=media_known_urls($relative);
                $placeholders=implode(',',array_fill(0,count($knownUrls),'?'));
                $pdo->prepare('UPDATE destinations SET image_principale=NULL WHERE image_principale IN (' . $placeholders . ')')->execute($knownUrls);
                $pdo->prepare('DELETE FROM images_destinations WHERE url IN (' . $placeholders . ')')->execute($knownUrls);
                if ($media['profil_utilisateur_id']!==null) {
                    $avatarPaths=[$relative,'/'.$relative];$avatarMarks=implode(',',array_fill(0,count($avatarPaths),'?'));
                    $pdo->prepare('UPDATE utilisateurs SET avatar_url=NULL WHERE id=? AND avatar_url IN ('.$avatarMarks.')')->execute(array_merge([(int)$media['profil_utilisateur_id']],$avatarPaths));
                }
                $pdo->prepare('DELETE FROM medias WHERE id=?')->execute([$requestId]);
                $pdo->commit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                if ($staged!==null && is_file($staged)) @rename($staged,$disk);
                throw $e;
            }
            $removed = $staged===null || !is_file($staged) || @unlink($staged);
            media_flash_redirect($removed?'success':'warning',$removed?'delete_success':'delete_file_warning');
        }
        throw new InvalidArgumentException('Action média inconnue.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('[image.php] user=' . $adminId . ' action=' . $action . ' error=' . $e->getMessage());
        $safeMessage = $e instanceof InvalidArgumentException || ($e instanceof RuntimeException && !$e instanceof PDOException) ? $e->getMessage() : 'Le traitement a échoué. Consultez le journal PHP pour le détail.';
        if ($isAjax) media_json(422,['success'=>false,'message'=>$safeMessage]);
        admin_flash($safeMessage,'error');
        header('Location: ' . $redirect);
        exit;
    }
}

$mediaId=filter_var($_GET['id']??null,FILTER_VALIDATE_INT);
$media=null;
if ($mediaId!==false && $mediaId!==null && $mediaId>0) {
    $stmt=$pdo->prepare('SELECT m.*,d.nom AS destination_nom,u.nom AS uploader_nom FROM medias m LEFT JOIN destinations d ON d.id=m.destination_id LEFT JOIN utilisateurs u ON u.id=m.utilisateur_id WHERE m.id=?');
    $stmt->execute([(int)$mediaId]); $media=$stmt->fetch();
    if (!$media) { http_response_code(404); exit('Image introuvable.'); }
}
$categoryFilter=trim((string)($_GET['categorie']??''));
if (!media_validate_category($categoryFilter,$categories)) $categoryFilter='';
$search=trim((string)($_GET['q']??''));
$search=substr($search,0,100);
$page=max(1,(int)($_GET['page']??1));
$perPage=36;
$where=[]; $params=[];
if ($categoryFilter!=='') {$where[]='m.categorie=?';$params[]=$categoryFilter;}
if ($search!=='') {$where[]='(m.nom_original LIKE ? OR m.description LIKE ? OR m.chemin_fichier LIKE ?)';$like='%'.$search.'%';array_push($params,$like,$like,$like);}
$whereSql=$where?' WHERE '.implode(' AND ',$where):'';
$countStmt=$pdo->prepare('SELECT COUNT(*) FROM medias m'.$whereSql);$countStmt->execute($params);$total=(int)$countStmt->fetchColumn();
$pageCount=max(1,(int)ceil($total/$perPage));$page=min($page,$pageCount);
$listStmt=$pdo->prepare('SELECT m.*,d.nom AS destination_nom,u.nom AS uploader_nom FROM medias m LEFT JOIN destinations d ON d.id=m.destination_id LEFT JOIN utilisateurs u ON u.id=m.utilisateur_id'.$whereSql.' ORDER BY m.created_at DESC,m.id DESC LIMIT ? OFFSET ?');
foreach($params as $i=>$value)$listStmt->bindValue($i+1,$value,PDO::PARAM_STR);
$listStmt->bindValue(count($params)+1,$perPage,PDO::PARAM_INT);$listStmt->bindValue(count($params)+2,($page-1)*$perPage,PDO::PARAM_INT);$listStmt->execute();$medias=$listStmt->fetchAll();
$destinations=$pdo->query('SELECT id,nom FROM destinations WHERE deleted_at IS NULL ORDER BY nom')->fetchAll();
$csrf=admin_csrf();
$flash=admin_flash();$flashPayload=null;
if (is_string($flash)) $flashPayload=['type'=>admin_flash_type(),'text'=>$flash];
$users=$pdo->query("SELECT id,nom,prenom FROM utilisateurs WHERE deleted_at IS NULL AND statut='actif' ORDER BY nom,prenom")->fetchAll();
$parseRelative=static function(string $path):string { return media_public_url($path); };
$formatBytes=static function(?int $bytes):string {if($bytes===null)return '—';$units=['o','Ko','Mo','Go'];$i=0;$value=(float)$bytes;while($value>=1024&&$i<count($units)-1){$value/=1024;$i++;}return number_format($value,$i===0?0:1,',',' ').' '.$units[$i];};
$csrfLimit=ini_get('upload_max_filesize');$postLimit=ini_get('post_max_size');
$uploadTempDir=(string)ini_get('upload_tmp_dir');if($uploadTempDir==='')$uploadTempDir=sys_get_temp_dir();
$uploadTempWritable=is_dir($uploadTempDir)&&is_writable($uploadTempDir);
$uploadRoot=dirname(__DIR__).'/uploads';
$uploadRootWritable=is_dir($uploadRoot)?is_writable($uploadRoot):is_writable(dirname($uploadRoot));
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Médiathèque — Tourisme Ivoire</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
<style>
:root{--media-orange:#e67e22;--media-dark:#d35400;--media-bg:#f4f6f9;--media-border:#e9ecef;--media-text:#1a1d29;--media-muted:#778196;--sidebar-width:250px;--topbar-height:62px}
*{box-sizing:border-box;font-family:Inter,Segoe UI,Arial,sans-serif}body{margin:0;background:var(--media-bg);color:var(--media-text)}.sidebar{position:fixed!important;inset:0 auto 0 0!important;height:100vh!important;z-index:1000}.topbar{position:fixed;top:0;left:var(--sidebar-width);right:0;height:var(--topbar-height);padding:0 26px;background:#fff;border-bottom:1px solid var(--media-border);z-index:900;display:flex;align-items:center;justify-content:space-between}.topbar strong{font-size:14px;letter-spacing:.04em}.topbar a{color:#51483f;text-decoration:none}.content{margin-left:var(--sidebar-width);padding:calc(var(--topbar-height) + 26px) 28px 40px;min-height:100vh}.media-shell{max-width:1250px;margin:auto}.media-heading{display:flex;justify-content:space-between;align-items:end;gap:16px;margin-bottom:22px}.media-heading h1{margin:0;font-weight:750;font-size:clamp(25px,3vw,34px)}.media-heading p{color:var(--media-muted);margin:6px 0 0}.media-card{background:#fff;border:1px solid var(--media-border);border-radius:16px;padding:22px;margin:0 0 20px;box-shadow:0 3px 14px rgba(20,25,35,.035)}.media-card h2{font-size:18px;font-weight:700;margin:0 0 16px}.media-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px 18px}.media-form-grid .wide{grid-column:1/-1}.media-card label{display:block;font-size:13px;font-weight:650;margin-bottom:6px}.media-card input:not([type=checkbox]):not([type=file]),.media-card select,.media-card textarea{width:100%;border:1px solid var(--media-border);border-radius:10px;padding:11px 12px;background:#fafbfc;color:#222;font:inherit;font-size:14px}.media-card input:focus,.media-card select:focus,.media-card textarea:focus{outline:none;border-color:var(--media-orange);box-shadow:0 0 0 3px #e67e2226}.drop-zone{border:2px dashed #e3d9ce;border-radius:14px;padding:25px;text-align:center;background:#fffaf5;cursor:pointer;transition:.18s}.drop-zone.dragging,.drop-zone:hover{border-color:var(--media-orange);background:#fff4e7}.drop-zone strong{display:block}.drop-zone small,.media-hint{color:var(--media-muted);font-size:12px}.drop-zone input{position:absolute;width:1px;height:1px;opacity:0}.preview-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:10px;margin-top:12px}.preview-item{border:1px solid var(--media-border);border-radius:10px;overflow:hidden;background:white}.preview-item img{display:block;width:100%;height:92px;object-fit:cover}.preview-item span{display:block;padding:6px;font-size:11px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.media-actions{display:flex;gap:9px;align-items:center;flex-wrap:wrap;margin-top:16px}.media-btn{border:0;border-radius:10px;padding:10px 15px;font-weight:700;cursor:pointer;background:#eee7dd;color:#2d251e}.media-btn.primary{background:linear-gradient(120deg,#f6a333,#e56600);color:white}.media-btn.danger{background:#fff0ef;color:#a52b23}.media-btn:disabled{opacity:.55;cursor:not-allowed}.progress-wrap{display:none;margin-top:14px}.progress-wrap.visible{display:block}.progress-track{height:9px;border-radius:99px;background:#eee7dd;overflow:hidden}.progress-fill{height:100%;width:0;background:#ed7600;transition:width .1s}.progress-label{font-size:12px;color:var(--media-muted);margin-top:6px}.inline-toggle{display:flex;align-items:center;gap:9px;font-size:13px;margin-top:12px}.inline-toggle input{accent-color:#ed7600;width:16px;height:16px}.filter-row{display:grid;grid-template-columns:1fr 240px auto;gap:10px;margin-bottom:18px}.filter-row input,.filter-row select{border:1px solid var(--media-border);padding:10px 12px;border-radius:10px;background:white}.library-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(235px,1fr));gap:15px}.library-item{overflow:hidden;border:1px solid var(--media-border);border-radius:14px;background:#fff;min-width:0}.library-item>img{width:100%;height:165px;object-fit:cover;background:#f0f2f5;display:block}.library-body{padding:13px}.library-title{font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.library-meta{font-size:12px;color:var(--media-muted);line-height:1.6;margin:6px 0 10px}.category-pill{display:inline-block;background:#fff0df;color:#a54c00;border-radius:99px;padding:4px 9px;font-weight:700;font-size:11px}.copy-row{display:flex;gap:5px}.copy-row input{min-width:0;flex:1;border:1px solid var(--media-border);border-radius:8px;padding:7px;font-size:11px;color:#5d554d}.copy-row button{border:0;border-radius:8px;padding:0 9px;background:#f6eee5;color:#8e460c;font-weight:700}.item-actions{display:flex;justify-content:space-between;align-items:center;margin-top:11px}.item-actions a{color:#b55200;font-weight:700;font-size:12px;text-decoration:none}.item-actions form{margin:0}.media-empty{text-align:center;border:1px dashed var(--media-border);border-radius:14px;padding:36px;color:var(--media-muted)}.pagination{display:flex;justify-content:center;align-items:center;gap:6px;margin-top:20px}.pagination a,.pagination span{padding:7px 11px;border-radius:8px;background:#fff;border:1px solid var(--media-border);color:#574c41;text-decoration:none;font-size:13px}.pagination .current{background:#ed7600;color:#fff;border-color:#ed7600}.field-errors{color:#ad2b22}.media-toast{color:var(--media-muted);font-size:12px}.legacy-note{padding:12px 14px;background:#fff8e9;border-radius:10px;color:#765126;font-size:13px}
@media(max-width:992px){.sidebar{position:static!important;width:100%!important;height:auto!important}.topbar{position:relative;left:auto;height:auto;padding:14px 18px}.content{margin:0;padding:20px 14px}.media-card{padding:17px}}
@media(max-width:620px){.media-form-grid{grid-template-columns:1fr}.media-form-grid .wide{grid-column:auto}.filter-row{grid-template-columns:1fr}.media-heading{align-items:flex-start;flex-direction:column}.library-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.library-item>img{height:120px}.library-meta{font-size:11px}}
</style>
</head>
<body class="admin-page">
<?php require __DIR__ . '/_sidebar.php'; ?>
<header class="topbar"><strong>TOURISME IVOIRE · MÉDIATHÈQUE</strong><a href="index.php?module=images">← Retour au tableau de bord</a></header>
<main class="content"><div class="media-shell">
<header class="media-heading"><div><h1><?= $media ? 'Modifier le média' : 'Bibliothèque multimédia' ?></h1><p>Images catégorisées, sécurisées et prêtes à être associées explicitement à un contenu.</p></div><span class="category-pill"><?= (int)$total ?> média(s)</span></header>
<?php if (!$media): ?>
<section class="media-card" aria-labelledby="upload-title"><h2 id="upload-title">Téléverser des images</h2><p class="media-hint">JPG, PNG, WebP<?= defined('IMAGETYPE_AVIF')?' et AVIF':'' ?> · 8 Mo maximum par image · <?= $maxBatch ?> images par envoi. Limites PHP détectées : upload_max_filesize <?= admin_escape($csrfLimit) ?>, post_max_size <?= admin_escape($postLimit) ?>.</p>
<?php if(!$uploadTempWritable): ?><div class="legacy-note">Le dossier temporaire PHP « <?= admin_escape($uploadTempDir) ?> » n’est pas accessible en écriture. Vérifiez upload_tmp_dir dans C:\xampp\php\php.ini et les droits du compte Apache, puis redémarrez Apache.</div><?php endif; ?>
<?php if(!$uploadRootWritable): ?><div class="legacy-note">Le dossier <?= admin_escape($uploadRoot) ?> n’est pas accessible en écriture pour PHP. Corrigez les permissions avant le téléversement.</div><?php endif; ?>
<form id="media-upload-form" method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>"><input type="hidden" name="action" value="upload">
<div class="media-form-grid">
<div class="wide"><label for="media-files">Images à envoyer</label><label class="drop-zone" id="drop-zone" for="media-files"><strong>Déposez vos images ici</strong><small>ou cliquez pour sélectionner jusqu’à <?= $maxBatch ?> fichiers</small><input id="media-files" type="file" name="images[]" accept="image/jpeg,image/png,image/webp<?= defined('IMAGETYPE_AVIF')?',image/avif':'' ?>" multiple required></label><div id="preview-grid" class="preview-grid" aria-live="polite"></div></div>
<div><label for="media-category">Catégorie <span aria-hidden="true">*</span></label><select id="media-category" name="categorie" required><option value="">Choisir une catégorie…</option><?php foreach($categories as $key=>$label): ?><option value="<?= admin_escape($key) ?>" <?= $categoryFilter===$key?'selected':'' ?>><?= admin_escape($label) ?></option><?php endforeach; ?></select></div>
<div><label for="media-destination">Associer à une destination (facultatif)</label><select id="media-destination" name="destination_id" disabled><option value="">Aucune association</option><?php foreach($destinations as $destination): ?><option value="<?= (int)$destination['id'] ?>"><?= admin_escape($destination['nom']) ?></option><?php endforeach; ?></select><small class="media-hint">Disponible pour Destinations et Sites touristiques uniquement.</small></div>
<div><label for="media-profile-user">Associer à un profil utilisateur (facultatif)</label><select id="media-profile-user" name="profil_utilisateur_id" disabled><option value="">Aucun profil lié</option><?php foreach($users as $profileUser): ?><option value="<?= (int)$profileUser['id'] ?>"><?= admin_escape(trim((string)$profileUser['prenom'].' '.(string)$profileUser['nom'])) ?></option><?php endforeach; ?></select><small class="media-hint">Disponible pour la catégorie Profils uniquement.</small></div>
<div class="wide"><label for="media-description">Description</label><textarea id="media-description" name="description" rows="2" maxlength="10000" placeholder="Description interne de ces images"></textarea></div>
<div><label for="media-alt">Texte alternatif</label><input id="media-alt" name="texte_alternatif" maxlength="255" placeholder="Description accessible"></div>
<div><label for="media-credit">Crédit (facultatif)</label><input id="media-credit" name="credit" maxlength="190" placeholder="Auteur ou source"></div>
<div><label for="media-license">Licence (facultatif)</label><input id="media-license" name="licence" maxlength="190" placeholder="Licence ou autorisation"></div>
</div>
<label class="inline-toggle"><input id="media-main" type="checkbox" name="principale" value="1" disabled> Définir la première image comme principale de cette destination</label>
<div class="progress-wrap" id="progress-wrap"><div class="progress-track"><div class="progress-fill" id="progress-fill"></div></div><div class="progress-label" id="progress-label">En attente…</div></div>
<div class="media-actions"><button class="media-btn primary" id="upload-submit" type="submit">Téléverser</button><button class="media-btn" id="upload-cancel" type="button" hidden>Annuler l’envoi</button><span class="media-toast" id="upload-status" aria-live="polite"></span></div></form>
</section>
<?php else: ?>
<section class="media-card"><h2>Modifier les informations</h2><div class="library-item" style="max-width:420px;margin-bottom:18px"><img src="<?= admin_escape($parseRelative((string)$media['chemin_fichier'])) ?>" alt="<?= admin_escape((string)($media['texte_alternatif']??'')) ?>"><div class="library-body"><b><?= admin_escape((string)$media['nom_original']) ?></b><div class="library-meta"><?= admin_escape((string)$media['largeur']) ?> × <?= admin_escape((string)$media['hauteur']) ?> px · <?= admin_escape($formatBytes($media['taille']===null?null:(int)$media['taille'])) ?> · <?= admin_escape((string)$media['type_mime']) ?></div></div></div>
<form id="media-edit-form" method="post"><input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>"><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= (int)$media['id'] ?>">
<div class="media-form-grid"><div><label for="edit-category">Catégorie</label><select id="edit-category" name="categorie" required><?php foreach($categories as $key=>$label): ?><option value="<?= admin_escape($key) ?>" <?= $media['categorie']===$key?'selected':'' ?>><?= admin_escape($label) ?></option><?php endforeach; ?></select></div>
<div><label for="edit-status">État</label><select id="edit-status" name="statut"><option value="actif" <?= $media['statut']==='actif'?'selected':'' ?>>Actif</option><option value="inactif" <?= $media['statut']==='inactif'?'selected':'' ?>>Inactif</option></select></div>
<div><label for="edit-destination">Destination liée (facultatif)</label><select id="edit-destination" name="destination_id" <?= in_array($media['categorie'],['destinations','sites_touristiques'],true)?'':'disabled' ?>><option value="">Aucune association</option><?php foreach($destinations as $destination): ?><option value="<?= (int)$destination['id'] ?>" <?= (int)($media['destination_id']??0)===(int)$destination['id']?'selected':'' ?>><?= admin_escape($destination['nom']) ?></option><?php endforeach; ?></select></div>
<div><label for="edit-profile-user">Profil utilisateur lié (facultatif)</label><select id="edit-profile-user" name="profil_utilisateur_id" <?= $media['categorie']==='profils'?'':'disabled' ?>><option value="">Aucun profil lié</option><?php foreach($users as $profileUser): ?><option value="<?= (int)$profileUser['id'] ?>" <?= (int)($media['profil_utilisateur_id']??0)===(int)$profileUser['id']?'selected':'' ?>><?= admin_escape(trim((string)$profileUser['prenom'].' '.(string)$profileUser['nom'])) ?></option><?php endforeach; ?></select></div>
<div class="wide"><label for="edit-description">Description</label><textarea id="edit-description" name="description" rows="3" maxlength="10000"><?= admin_escape((string)($media['description']??'')) ?></textarea></div>
<div><label for="edit-alt">Texte alternatif</label><input id="edit-alt" name="texte_alternatif" maxlength="255" value="<?= admin_escape((string)($media['texte_alternatif']??'')) ?>"></div>
<div><label for="edit-credit">Crédit (facultatif)</label><input id="edit-credit" name="credit" maxlength="190" value="<?= admin_escape((string)($media['credit']??'')) ?>"></div>
<div><label for="edit-license">Licence (facultatif)</label><input id="edit-license" name="licence" maxlength="190" value="<?= admin_escape((string)($media['licence']??'')) ?>"></div>
</div><label class="inline-toggle"><input type="checkbox" name="principale" value="1" <?= !empty($media['est_principale'])?'checked':'' ?> <?= $media['destination_id']!==null?'':'disabled' ?>> Image principale de la destination liée</label>
<div class="media-actions"><button class="media-btn primary" type="submit">Enregistrer les modifications</button><a class="media-btn" href="image.php">Annuler</a></div></form></section>
<?php endif; ?>
<section class="media-card"><h2>Bibliothèque</h2><form class="filter-row" method="get" action="image.php"><input type="search" name="q" value="<?= admin_escape($search) ?>" placeholder="Rechercher un nom ou une description…"><select name="categorie"><option value="">Toutes les catégories</option><?php foreach($categories as $key=>$label): ?><option value="<?= admin_escape($key) ?>" <?= $categoryFilter===$key?'selected':'' ?>><?= admin_escape($label) ?></option><?php endforeach; ?></select><button class="media-btn primary" type="submit">Filtrer</button></form>
<?php if ($medias): ?><div class="library-grid"><?php foreach($medias as $item): $url=$parseRelative((string)$item['chemin_fichier']); ?><article class="library-item"><img src="<?= admin_escape($url) ?>" alt="<?= admin_escape((string)($item['texte_alternatif']??'')) ?>" loading="lazy" onerror="this.hidden=true"><div class="library-body"><div class="library-title" title="<?= admin_escape((string)$item['nom_original']) ?>"><?= admin_escape((string)$item['nom_original']) ?></div><p class="library-meta"><span class="category-pill"><?= admin_escape($categories[$item['categorie']]??'Autres') ?></span><br><?= admin_escape((string)($item['largeur']??'—')) ?> × <?= admin_escape((string)($item['hauteur']??'—')) ?> px · <?= admin_escape($formatBytes($item['taille']===null?null:(int)$item['taille'])) ?><br><?= $item['destination_nom']?'Destination : '.admin_escape((string)$item['destination_nom']):'Non associée' ?> · <?= admin_escape((string)$item['statut']) ?></p><div class="copy-row"><input readonly value="<?= admin_escape($url) ?>" aria-label="Chemin du média"><button type="button" data-copy="<?= admin_escape($url) ?>">Copier</button></div><div class="item-actions"><a href="image.php?id=<?= (int)$item['id'] ?>">Détails / Modifier</a><form method="post" data-delete-media><input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><button class="media-btn danger" type="submit">Supprimer</button></form></div></div></article><?php endforeach; ?></div>
<?php else: ?><div class="media-empty">Aucune image ne correspond à ces filtres. Téléversez une image ci-dessus pour commencer la médiathèque.</div><?php endif; ?>
<?php if($pageCount>1): ?><nav class="pagination" aria-label="Pagination médias"><?php for($i=1;$i<=$pageCount;$i++): $query=http_build_query(array_filter(['q'=>$search,'categorie'=>$categoryFilter,'page'=>$i],static fn($v)=>$v!=='')); ?><a class="<?= $i===$page?'current':'' ?>" href="image.php?<?= admin_escape($query) ?>"><?= $i ?></a><?php endfor; ?></nav><?php endif; ?>
</section><p class="media-hint">Les images ne sont jamais affichées automatiquement sur le site. Copiez leur chemin ou associez explicitement une image à une destination.</p>
</div></main>
<?php if ($flashPayload!==null): ?><script id="media-flash" type="application/json"><?= json_encode($flashPayload,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR) ?></script><?php endif; ?>
<script src="media-library.js" defer></script>
</body></html>
