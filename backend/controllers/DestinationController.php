<?php
declare(strict_types=1);
final class DestinationController {
    private const SELECT = "SELECT d.id,d.nom,d.slug,d.description,d.histoire,d.latitude,d.longitude,d.adresse,d.conseils_visite,d.informations_pratiques,d.image_principale,d.published_at,l.nom AS localite,c.nom AS categorie FROM destinations d JOIN localites l ON l.id=d.localite_id JOIN departements dep ON dep.id=l.departement_id JOIN categories c ON c.id=d.categorie_id WHERE d.statut_publication='publie' AND d.deleted_at IS NULL";
    public static function index(): never {
        $page = max(1, (int)($_GET['page'] ?? 1)); $perPage = min(50, max(1, (int)($_GET['per_page'] ?? 12)));
        $where = ''; $params = [];
        $q = trim((string)($_GET['q'] ?? $_GET['search'] ?? ''));
        if ($q !== '') { $where = ' AND (d.nom LIKE ? OR d.description LIKE ? OR l.nom LIKE ? OR c.nom LIKE ?)'; $like = '%' . $q . '%'; $params = [$like,$like,$like,$like]; }
        $category = trim((string)($_GET['categorie'] ?? ''));
        if ($category !== '') { $where .= ' AND (c.slug=? OR c.id=?)'; $params[] = $category; $params[] = ctype_digit($category) ? (int)$category : 0; }
        foreach (['region_id' => 'dep.region_id', 'district_id' => 'dep.district_id'] as $key => $column) {
            if (isset($_GET[$key]) && $_GET[$key] !== '') {
                $value = filter_var($_GET[$key], FILTER_VALIDATE_INT);
                if (!$value || $value < 1) api_error('validation_error', "$key invalide.", 422);
                $where .= " AND $column=?";
                $params[] = $value;
            }
        }
        $from = ' FROM destinations d JOIN localites l ON l.id=d.localite_id JOIN departements dep ON dep.id=l.departement_id JOIN categories c ON c.id=d.categorie_id WHERE d.statut_publication=\'publie\' AND d.deleted_at IS NULL' . $where;
        $count = db()->prepare('SELECT COUNT(*)' . $from);
        $count->execute($params); $total = (int)$count->fetchColumn();
        $stmt = db()->prepare(self::SELECT . $where . ' ORDER BY d.published_at DESC,d.id DESC LIMIT ? OFFSET ?');
        foreach ($params as $i => $value) $stmt->bindValue($i + 1, $value);
        $stmt->bindValue(count($params) + 1, $perPage, PDO::PARAM_INT); $stmt->bindValue(count($params) + 2, ($page - 1) * $perPage, PDO::PARAM_INT);
        $stmt->execute();
        json_response(['data' => ['items' => $stmt->fetchAll(), 'pagination' => ['page' => $page, 'per_page' => $perPage, 'total' => $total, 'pages' => (int)ceil($total / max(1,$perPage))]]]);
    }
    public static function show(int $id): never {
        $stmt = db()->prepare(self::SELECT . ' AND d.id=? LIMIT 1'); $stmt->execute([$id]); $row = $stmt->fetch();
        if (!$row) api_error('not_found', 'Destination introuvable.', 404);
        $images = db()->prepare("SELECT url,texte_alternatif,credit,licence FROM images_destinations WHERE destination_id=? AND statut='actif' ORDER BY est_principale DESC,ordre");
        $images->execute([$id]); $row['images'] = $images->fetchAll();
        json_response(['data' => $row]);
    }
    public static function featured(): never {
        $stmt = db()->query(self::SELECT . ' ORDER BY d.published_at DESC,d.id DESC LIMIT 8');
        json_response(['data' => $stmt->fetchAll()]);
    }
}
