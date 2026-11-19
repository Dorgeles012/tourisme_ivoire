<?php
declare(strict_types=1);
final class HeritageController {
    public static function index(): never {
        $stmt = db()->prepare("SELECT p.id,p.titre,p.slug,p.contenu,p.periode,l.nom AS localite,c.nom AS categorie FROM patrimoines p LEFT JOIN localites l ON l.id=p.localite_id LEFT JOIN categories c ON c.id=p.categorie_id WHERE p.statut='publie' AND p.is_demo=0 ORDER BY p.titre LIMIT 100");
        $stmt->execute();
        json_response(['data' => $stmt->fetchAll()]);
    }

    public static function virtualVisits(): never {
        $stmt = db()->prepare("SELECT v.id,v.titre,v.type_visite,v.url,v.fournisseur,p.titre AS patrimoine,d.nom AS destination FROM visites_virtuelles v LEFT JOIN patrimoines p ON p.id=v.patrimoine_id LEFT JOIN destinations d ON d.id=v.destination_id WHERE v.statut='actif' AND v.is_demo=0 AND (p.statut='publie' AND p.is_demo=0 OR p.id IS NULL) AND (d.statut_publication='publie' AND d.is_demo=0 OR d.id IS NULL) ORDER BY v.titre LIMIT 100");
        $stmt->execute();
        json_response(['data' => $stmt->fetchAll()]);
    }

    public static function recordView(int $id): never {
        $userId=AuthMiddleware::userId();
        $pdo=db();
        $pdo->beginTransaction();
        try {
            $check=$pdo->prepare("SELECT id FROM patrimoines WHERE id=? AND statut='publie' AND is_demo=0 FOR UPDATE");
            $check->execute([$id]);
            if (!$check->fetchColumn()) {
                $pdo->rollBack();
                api_error('not_found','Contenu culturel introuvable.',404);
            }
            $pdo->prepare('INSERT IGNORE INTO consultations_patrimoine(utilisateur_id,patrimoine_id) VALUES(?,?)')->execute([$userId,$id]);
            BadgeService::evaluate($pdo,$userId);
            $pdo->commit();
            json_response(['data'=>['patrimoine_id'=>$id,'consulte'=>true]]);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}
