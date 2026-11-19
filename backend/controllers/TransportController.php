<?php
declare(strict_types=1);
final class TransportController {
    public static function transports(): never {
        $stmt = db()->query("SELECT id,code,nom,description FROM transports WHERE statut='actif' ORDER BY nom");
        json_response(['data' => $stmt->fetchAll()]);
    }

    public static function itineraries(): never {
        $where = ["i.statut='actif'", "i.disponibilite<>'indisponible'", "i.is_demo=0", "(op.is_demo=0 OR op.id IS NULL)", "(op.statut='actif' OR op.id IS NULL)"];
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
            $stmt = $pdo->prepare("SELECT id,depart_libelle,arrivee_libelle,tarif,devise,depart_at,disponibilite FROM itineraires WHERE id=? AND statut='actif' AND is_demo=0 AND (transporteur_id IS NULL OR transporteur_id IN (SELECT id FROM transporteurs WHERE is_demo=0 AND statut='actif')) FOR UPDATE");
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
            $pdo->prepare('INSERT INTO notifications(utilisateur_id,titre,contenu,type,cible_url) VALUES(?,?,?,?,?)')->execute([$userId, 'Demande de réservation reçue', 'Votre demande ' . $reference . ' est en attente de confirmation.', 'reservation', 'reservations']);
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

    public static function reservationDetails(int $id): never {
        $stmt = db()->prepare('SELECT r.id,r.reference,r.statut,r.montant_total,r.devise,r.created_at,r.updated_at,r.notes,dr.libelle_snapshot,dr.quantite,dr.tarif_unitaire,dr.depart_at,dr.statut AS detail_statut FROM reservations r LEFT JOIN details_reservations dr ON dr.reservation_id=r.id WHERE r.id=? AND r.utilisateur_id=? LIMIT 1');
        $stmt->execute([$id, AuthMiddleware::requireRole(['Client'])]);
        $reservation = $stmt->fetch();
        if (!$reservation) api_error('not_found', 'Réservation introuvable.', 404);
        json_response(['data' => $reservation]);
    }

    public static function cancelReservation(int $id): never {
        $userId = AuthMiddleware::requireRole(['Client']);
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT id,reference,statut FROM reservations WHERE id=? AND utilisateur_id=? FOR UPDATE');
            $stmt->execute([$id, $userId]);
            $reservation = $stmt->fetch();
            if (!$reservation) { $pdo->rollBack(); api_error('not_found', 'Réservation introuvable.', 404); }
            if ($reservation['statut'] !== 'demande') { $pdo->rollBack(); api_error('reservation_not_cancellable', 'Seules les demandes en attente peuvent être annulées depuis votre espace.', 409); }
            $pdo->prepare("UPDATE reservations SET statut='annulee' WHERE id=? AND utilisateur_id=? AND statut='demande'")->execute([$id, $userId]);
            $pdo->prepare("UPDATE details_reservations SET statut='annulee' WHERE reservation_id=?")->execute([$id]);
            $pdo->prepare('INSERT INTO notifications(utilisateur_id,titre,contenu,type,cible_url) VALUES(?,?,?,?,?)')->execute([$userId, 'Réservation annulée', 'Votre demande ' . $reservation['reference'] . ' a été annulée.', 'reservation', 'reservations']);
            $pdo->commit();
            json_response(['data' => ['id' => $id, 'statut' => 'annulee']]);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}
