<?php
declare(strict_types=1);
final class ReferenceController {
    public static function categories(): never { json_response(['data' => db()->query("SELECT id,nom,slug,description,icone FROM categories WHERE statut='actif' ORDER BY ordre,nom")->fetchAll()]); }
    public static function regions(): never {
        $sql = "SELECT id,district_id,nom,slug,code,code_officiel FROM regions WHERE statut='actif'";
        $params = [];
        if (isset($_GET['district_id']) && $_GET['district_id'] !== '') {
            $id = filter_var($_GET['district_id'], FILTER_VALIDATE_INT);
            if ($id === false || $id < 1) api_error('validation_error', 'district_id invalide.', 422);
            $sql .= ' AND district_id=?';
            $params[] = $id;
        }
        $sql .= ' ORDER BY nom';
        $stmt = db()->prepare($sql); $stmt->execute($params);
        json_response(['data' => $stmt->fetchAll()]);
    }
    public static function districts(): never { json_response(['data' => db()->query("SELECT id,nom,slug,code,type,code_officiel FROM districts WHERE statut='actif' ORDER BY nom")->fetchAll()]); }
    public static function departements(): never {
        $regionRaw = $_GET['region_id'] ?? null;
        $districtRaw = $_GET['district_id'] ?? null;
        if (($regionRaw === null || $regionRaw === '') === ($districtRaw === null || $districtRaw === '')) api_error('validation_error', 'Indiquez exactement un region_id ou un district_id.', 422);
        $key = ($regionRaw !== null && $regionRaw !== '') ? 'region_id' : 'district_id';
        $id = filter_var($key === 'region_id' ? $regionRaw : $districtRaw, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) api_error('validation_error', "$key invalide.", 422);
        $column = $key === 'region_id' ? 'region_id' : 'district_id';
        $stmt = db()->prepare("SELECT id,region_id,district_id,nom,code,code_officiel FROM departements WHERE statut='actif' AND $column=? ORDER BY nom");
        $stmt->execute([$id]);
        json_response(['data' => $stmt->fetchAll()]);
    }
    public static function languages(): never { json_response(['data' => db()->query("SELECT id,code,nom FROM langues WHERE actif=1 ORDER BY nom")->fetchAll()]); }
    public static function localites(): never {
        $where = ["l.statut='actif'"];
        $params = [];
        foreach (['region_id' => 'd.region_id', 'district_id' => 'd.district_id'] as $key => $column) {
            if (isset($_GET[$key]) && $_GET[$key] !== '') {
                $value = filter_var($_GET[$key], FILTER_VALIDATE_INT);
                if (!$value || $value < 1) api_error('validation_error', "$key invalide.", 422);
                $where[] = "$column=?";
                $params[] = $value;
            }
        }
        $stmt = db()->prepare('SELECT l.id,l.nom,l.slug,l.latitude,l.longitude,dep.nom AS departement,COALESCE(r.nom,di.nom) AS region,di.nom AS district FROM localites l JOIN departements dep ON dep.id=l.departement_id LEFT JOIN regions r ON r.id=dep.region_id LEFT JOIN districts di ON di.id=dep.district_id WHERE ' . implode(' AND ',$where) . ' ORDER BY l.nom LIMIT 500');
        $stmt->execute($params);
        json_response(['data' => $stmt->fetchAll()]);
    }
}
