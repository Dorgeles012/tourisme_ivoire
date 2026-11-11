<?php
declare(strict_types=1);
final class PassportController {
    public static function show(): never {
        $userId = AuthMiddleware::userId();
        $stmt = db()->prepare("SELECT p.numero,p.points_total,p.niveau,p.date_emission,p.statut,(SELECT COUNT(*) FROM visites v WHERE v.utilisateur_id=p.utilisateur_id) AS visites,(SELECT COUNT(*) FROM utilisateurs_badges b WHERE b.utilisateur_id=p.utilisateur_id) AS badges,(SELECT COUNT(*) FROM participations_quiz q WHERE q.utilisateur_id=p.utilisateur_id AND q.termine_at IS NOT NULL) AS quiz_termines FROM passeports p WHERE p.utilisateur_id=?");
        $stmt->execute([$userId]);
        $passport = $stmt->fetch();
        if (!$passport) api_error('not_found', 'Passeport numérique introuvable.', 404);
        json_response(['data' => $passport]);
    }

    public static function badges(): never {
        $stmt = db()->prepare("SELECT b.id,b.code,b.nom,b.description,b.icone_url,b.condition_type,b.condition_valeur,b.points,ub.obtenu_at,(ub.id IS NOT NULL) AS obtenu FROM badges b LEFT JOIN utilisateurs_badges ub ON ub.badge_id=b.id AND ub.utilisateur_id=? WHERE b.statut='actif' ORDER BY obtenu DESC,b.points,b.nom");
        $stmt->execute([AuthMiddleware::userId()]);
        json_response(['data' => $stmt->fetchAll()]);
    }

    public static function visits(): never {
        $stmt = db()->prepare("SELECT v.id,v.visite_le,v.source,d.id AS destination_id,d.nom,d.slug,d.image_principale,l.nom AS localite FROM visites v JOIN destinations d ON d.id=v.destination_id JOIN localites l ON l.id=d.localite_id WHERE v.utilisateur_id=? ORDER BY v.visite_le DESC LIMIT 200");
        $stmt->execute([AuthMiddleware::userId()]);
        json_response(['data' => $stmt->fetchAll()]);
    }

    public static function recordVisit(): never {
        $userId = AuthMiddleware::userId();
        $data = request_json();
        $destinationId = filter_var($data['destination_id'] ?? null,FILTER_VALIDATE_INT);
        if (!$destinationId || $destinationId < 1) api_error('validation_error', 'destination_id doit être un entier positif.', 422);
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $destination = $pdo->prepare("SELECT id FROM destinations WHERE id=? AND statut_publication='publie' AND deleted_at IS NULL FOR UPDATE");
            $destination->execute([$destinationId]);
            if (!$destination->fetchColumn()) {
                $pdo->rollBack();
                api_error('not_found', 'Destination introuvable.', 404);
            }
            $pdo->prepare("INSERT INTO visites(utilisateur_id,destination_id,visite_le,source) VALUES(?,?,NOW(),'declaration')")->execute([$userId,$destinationId]);
            $visitCount = $pdo->prepare('SELECT COUNT(*) FROM visites WHERE utilisateur_id=?');
            $visitCount->execute([$userId]);
            $count = (int)$visitCount->fetchColumn();
            BadgeService::evaluate($pdo,$userId);
            $pdo->commit();
            json_response(['data' => ['destination_id' => $destinationId,'visites' => $count]], 201);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}
