<?php
declare(strict_types=1);

final class TourismContentController {
    private const MODULES = [
        'actualites' => ['table'=>'actualites','name'=>'titre','fields'=>'id,titre AS nom,slug,resume,contenu,categorie,image_url,statut,published_at,created_at,updated_at'],
        'promotions' => ['table'=>'promotions','name'=>'titre','fields'=>'id,titre AS nom,slug,description,reduction,date_debut,date_fin,image_url,statut,created_at,updated_at'],
        'hebergements' => ['table'=>'hebergements','name'=>'nom','fields'=>'id,nom,slug,type,description,localite,adresse,telephone,site_web,prix_min,devise,image_url,statut,created_at,updated_at'],
        'activites' => ['table'=>'activites_touristiques','name'=>'nom','fields'=>'id,nom,slug,type,description,localite,adresse,duree,prix,devise,image_url,statut,created_at,updated_at'],
    ];

    public static function index(string $module, ?string $slug): never {
        $config = self::MODULES[$module] ?? null;
        if (!$config) api_error('content_type_not_found','Type de contenu inconnu.',404);
        $where = "statut='publie' AND deleted_at IS NULL AND is_demo=0";
        if ($module === 'actualites') $where .= ' AND (published_at IS NULL OR published_at<=NOW())';
        if ($module === 'promotions') $where .= ' AND date_debut<=NOW() AND date_fin>=NOW()';
        $pdo = db();
        if ($slug !== null) {
            $stmt=$pdo->prepare('SELECT '.$config['fields'].' FROM '.$config['table'].' WHERE '.$where.' AND slug=? LIMIT 1');
            $stmt->execute([$slug]); $row=$stmt->fetch();
            if (!$row) api_error('content_not_found','Contenu introuvable.',404);
            json_response(['data'=>$row]);
        }
        $page=max(1,(int)($_GET['page']??1)); $perPage=min(50,max(1,(int)($_GET['per_page']??20)));
        $params=[]; $search=trim((string)($_GET['q']??''));
        if ($search!=='') { $searchColumns = $module==='actualites' ? 'titre LIKE ? OR resume LIKE ?' : ($module==='promotions' ? 'titre LIKE ? OR description LIKE ?' : 'nom LIKE ? OR description LIKE ?'); $where .= ' AND ('.$searchColumns.')'; $params[]='%'.$search.'%'; $params[]='%'.$search.'%'; }
        $count=$pdo->prepare('SELECT COUNT(*) FROM '.$config['table'].' WHERE '.$where); $count->execute($params);
        $stmt=$pdo->prepare('SELECT '.$config['fields'].' FROM '.$config['table'].' WHERE '.$where.' ORDER BY '.($module==='promotions'?'date_debut DESC':($module==='actualites'?'COALESCE(published_at,created_at) DESC':'updated_at DESC')).' LIMIT ? OFFSET ?');
        foreach($params as $i=>$value) $stmt->bindValue($i+1,$value,PDO::PARAM_STR);
        $stmt->bindValue(count($params)+1,$perPage,PDO::PARAM_INT); $stmt->bindValue(count($params)+2,($page-1)*$perPage,PDO::PARAM_INT); $stmt->execute();
        json_response(['data'=>['items'=>$stmt->fetchAll(),'pagination'=>['page'=>$page,'per_page'=>$perPage,'total'=>(int)$count->fetchColumn()]]]);
    }
}
