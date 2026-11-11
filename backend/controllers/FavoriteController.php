<?php
declare(strict_types=1);
final class FavoriteController {
    public static function index(): never {
        $userId = AuthMiddleware::userId();
        $stmt = db()->prepare("SELECT f.id AS favori_id,f.created_at,d.id,d.nom,d.slug,d.description,d.image_principale,l.nom AS localite,c.nom AS categorie FROM favoris f JOIN destinations d ON d.id=f.destination_id JOIN localites l ON l.id=d.localite_id JOIN categories c ON c.id=d.categorie_id WHERE f.utilisateur_id=? AND d.statut_publication='publie' AND d.deleted_at IS NULL ORDER BY f.created_at DESC");
        $stmt->execute([$userId]);
        json_response(['data' => $stmt->fetchAll()]);
    }

    public static function store(): never {
        $userId = AuthMiddleware::userId();
        $data = request_json();
        $destinationId = filter_var($data['destination_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$destinationId || $destinationId < 1) api_error('validation_error', 'destination_id doit être un entier positif.', 422);
        $check = db()->prepare("SELECT id FROM destinations WHERE id=? AND statut_publication='publie' AND deleted_at IS NULL");
        $check->execute([$destinationId]);
        if (!$check->fetchColumn()) api_error('not_found', 'Destination introuvable.', 404);
        $stmt = db()->prepare('INSERT INTO favoris(utilisateur_id,destination_id) VALUES(?,?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)');
        $stmt->execute([$userId, $destinationId]);
        json_response(['data' => ['id' => (int)db()->lastInsertId(), 'destination_id' => $destinationId]], 201);
    }

    public static function destroy(int $id): never {
        $stmt = db()->prepare('DELETE FROM favoris WHERE id=? AND utilisateur_id=?');
        $stmt->execute([$id, AuthMiddleware::userId()]);
        if ($stmt->rowCount() === 0) api_error('not_found', 'Favori introuvable.', 404);
        json_response(['data' => ['deleted' => true]]);
    }
}
