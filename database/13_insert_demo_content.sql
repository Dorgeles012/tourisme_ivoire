USE tourisme_ivoire;
SET NAMES utf8mb4;

-- Demo reservations are requests only. No fictional fare or availability is set.
INSERT INTO favoris(utilisateur_id,destination_id)
SELECT u.id,d.id
FROM utilisateurs u
JOIN destinations d ON d.slug IN ('basilique-notre-dame-paix','quartier-historique-grand-bassam','parc-national-banco')
WHERE u.email='voyageur.demo@tourisme-ivoire.local'
ON DUPLICATE KEY UPDATE destination_id=VALUES(destination_id);

INSERT INTO reservations(utilisateur_id,reference,statut,montant_total,devise,is_demo,notes)
SELECT u.id,'DEMO-RES-0001','demande',NULL,NULL,TRUE,'Réservation de démonstration; aucune prestation ni disponibilité confirmée.'
FROM utilisateurs u
WHERE u.email='voyageur.demo@tourisme-ivoire.local'
ON DUPLICATE KEY UPDATE notes=VALUES(notes),is_demo=TRUE;

INSERT INTO details_reservations(reservation_id,itineraire_id,quantite,tarif_unitaire,devise,depart_at,statut,libelle_snapshot,is_demo)
SELECT r.id,NULL,1,NULL,NULL,NULL,'demande','Demande de transport de démonstration — itinéraire à confirmer',TRUE
FROM reservations r WHERE r.reference='DEMO-RES-0001'
ON DUPLICATE KEY UPDATE libelle_snapshot=VALUES(libelle_snapshot),is_demo=TRUE;

INSERT INTO notifications(utilisateur_id,titre,contenu,type,is_demo)
SELECT u.id,'Bienvenue dans Tourisme Ivoire','Votre espace de démonstration est prêt. Les contenus marqués comme brouillons ne sont pas publiés.','information',TRUE
FROM utilisateurs u
WHERE u.email='voyageur.demo@tourisme-ivoire.local'
AND NOT EXISTS (
 SELECT 1 FROM notifications n WHERE n.utilisateur_id=u.id AND n.titre='Bienvenue dans Tourisme Ivoire' AND n.is_demo=TRUE
);

INSERT INTO participations_quiz(utilisateur_id,quiz_id,score,bonnes_reponses,total_questions,commence_at,termine_at,is_demo)
SELECT u.id,q.id,0,0,0,NOW(),NOW(),TRUE
FROM utilisateurs u JOIN quiz q ON q.slug='decouverte-ci-serie-1'
WHERE u.email='voyageur.demo@tourisme-ivoire.local'
AND NOT EXISTS (
 SELECT 1 FROM participations_quiz p WHERE p.utilisateur_id=u.id AND p.quiz_id=q.id AND p.is_demo=TRUE
);

-- Demo video rows are intentionally omitted until real licensed media is uploaded.
