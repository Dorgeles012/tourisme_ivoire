<?php
declare(strict_types=1);
final class TransportController {
    public static function transports(): never {
        $stmt = db()->query("SELECT id,code,nom,description FROM transports WHERE statut='actif' ORDER BY nom");
        json_response(['data' => $stmt->fetchAll()]);
    }

    public static function itineraries(): never {
        $where = ["i.statut='actif'", "i.disponibilite<>'indisponible'"];
        $params = [];
        $from = trim((string)($_GET['depart'] ?? ''));
        $to = trim((string)($_GET['arrivee'] ?? ''));
        if ($from !== '') { $where[] = 'i.depart_libelle LIKE ?'; $params[] = '%' . $from . '%'; }
        if ($to !== '') { $where[] = 'i.arrivee_libelle LIKE ?'; $params[] = '%' . $to . '%'; }
        $stmt = db()->prepare('SELECT i.id,i.depart_libelle,i.arrivee_libelle,i.duree_minutes,i.tarif,i.devise,i.depart_at,i.disponibilite,i.verified_at,t.nom AS transport,op.nom AS transporteur FROM itineraires i JOIN transports t ON t.id=i.transport_id LEFT JOIN transporteurs op ON op.id=i.transporteur_id WHERE ' . implode(' AND ', $where) . ' ORDER BY i.depart_at IS NULL,i.depart_at LIMIT 100');
        $stmt->execute($params);
        json_response(['data' => $stmt->fetchAll()]);
    }

    public static function search(): never {
        $data = request_json();
        $from = trim((string)($data['depart'] ?? ''));
        $to = trim((string)($data['arrivee'] ?? ''));
        if ($from === '' || $to === '' || mb_strlen($from) > 180 || mb_strlen($to) > 180) {
            api_error('validation_error', 'Un départ et une arrivée valides sont requis.', 422);
        }
        $_GET['depart'] = $from;
        $_GET['arrivee'] = $to;
        self::itineraries();
    }

    public static function reserve(): never {
        $userId = AuthMiddleware::userId();
        $data = request_json();
        $itineraryId = filter_var($data['itineraire_id'] ?? null, FILTER_VALIDATE_INT);
        $quantity = filter_var($data['quantite'] ?? 1, FILTER_VALIDATE_INT);
        if (!$itineraryId || $itineraryId < 1 || !$quantity || $quantity < 1 || $quantity > 20) {
            api_error('validation_error', 'Un itinéraire et une quantité comprise entre 1 et 20 sont requis.', 422);
        }
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT id,depart_libelle,arrivee_libelle,tarif,devise,depart_at,disponibilite FROM itineraires WHERE id=? AND statut='actif' FOR UPDATE");
            $stmt->execute([$itineraryId]);
            $route = $stmt->fetch();
            if (!$route || $route['disponibilite'] === 'indisponible') {
                $pdo->rollBack();
                api_error('not_found', 'Itinéraire indisponible ou introuvable.', 404);
            }
            $reference = 'TI-' . strtoupper(bin2hex(random_bytes(6)));
            $total = $route['tarif'] === null ? null : (float)$route['tarif'] * $quantity;
            $booking = $pdo->prepare("INSERT INTO reservations(utilisateur_id,reference,statut,montant_total,devise,notes) VALUES(?,?,'demande',?,?,?)");
            $booking->execute([$userId,$reference,$total,$total === null ? null : $route['devise'],'Demande enregistrée; la disponibilité et les détails seront confirmés par le transporteur.']);
            $reservationId = (int)$pdo->lastInsertId();
            $detail = $pdo->prepare("INSERT INTO details_reservations(reservation_id,itineraire_id,quantite,tarif_unitaire,devise,depart_at,statut,libelle_snapshot) VALUES(?,?,?,?,?,?,'demande',?)");
            $detail->execute([$reservationId,$itineraryId,$quantity,$route['tarif'],$route['devise'],$route['depart_at'],$route['depart_libelle'] . ' → ' . $route['arrivee_libelle']]);
            $pdo->commit();
            json_response(['data' => ['id' => $reservationId, 'reference' => $reference, 'statut' => 'demande', 'montant_total' => $total, 'devise' => $total === null ? null : $route['devise']]], 201);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public static function reservations(): never {
        $stmt = db()->prepare('SELECT r.id,r.reference,r.statut,r.montant_total,r.devise,r.created_at,dr.libelle_snapshot,dr.quantite,dr.depart_at,dr.statut AS detail_statut FROM reservations r LEFT JOIN details_reservations dr ON dr.reservation_id=r.id WHERE r.utilisateur_id=? ORDER BY r.created_at DESC LIMIT 100');
        $stmt->execute([AuthMiddleware::userId()]);
        json_response(['data' => $stmt->fetchAll()]);
    }
}
