USE tourisme_ivoire;
CREATE OR REPLACE VIEW v_destinations_publiees AS
SELECT d.id,d.nom,d.slug,d.description,d.image_principale,d.latitude,d.longitude,
 l.nom AS localite,COALESCE(r.nom,di.nom) AS region,c.nom AS categorie,d.published_at
FROM destinations d
JOIN localites l ON l.id=d.localite_id
JOIN departements dep ON dep.id=l.departement_id
LEFT JOIN regions r ON r.id=dep.region_id
LEFT JOIN districts di ON di.id=dep.district_id
JOIN categories c ON c.id=d.categorie_id
WHERE d.statut_publication='publie' AND d.deleted_at IS NULL;

CREATE OR REPLACE VIEW v_classement_quiz AS
SELECT p.quiz_id,q.titre AS quiz,u.id AS utilisateur_id,u.nom,p.score,p.bonnes_reponses,p.total_questions,p.termine_at
FROM participations_quiz p
JOIN quiz q ON q.id=p.quiz_id
JOIN utilisateurs u ON u.id=p.utilisateur_id
WHERE p.termine_at IS NOT NULL AND q.statut='publie';
