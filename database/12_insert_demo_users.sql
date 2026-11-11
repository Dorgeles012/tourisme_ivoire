USE tourisme_ivoire;
SET NAMES utf8mb4;

-- Local-only demonstration identities. They are marked is_demo and share a
-- password hash; never import this file into production.
INSERT INTO utilisateurs(nom,prenom,email,mot_de_passe_hash,statut,is_demo)
VALUES
('Administrateur','Démo','admin.demo@tourisme-ivoire.local','$2y$12$BlGXqcTE6AWiaApyl1EMjO3F8P7XYVkNQSuWVPvmv.n.M1lecesNu','actif',TRUE),
('Guide','Démo','guide.demo@tourisme-ivoire.local','$2y$12$BlGXqcTE6AWiaApyl1EMjO3F8P7XYVkNQSuWVPvmv.n.M1lecesNu','actif',TRUE),
('Voyageur','Démo','voyageur.demo@tourisme-ivoire.local','$2y$12$BlGXqcTE6AWiaApyl1EMjO3F8P7XYVkNQSuWVPvmv.n.M1lecesNu','actif',TRUE)
ON DUPLICATE KEY UPDATE nom=VALUES(nom),prenom=VALUES(prenom),is_demo=TRUE;

INSERT INTO utilisateurs_roles(utilisateur_id,role_id)
SELECT u.id,r.id
FROM utilisateurs u
JOIN roles r ON r.code=CASE u.email
 WHEN 'admin.demo@tourisme-ivoire.local' THEN 'administrateur'
 WHEN 'guide.demo@tourisme-ivoire.local' THEN 'guide'
 ELSE 'voyageur'
END
WHERE u.email IN ('admin.demo@tourisme-ivoire.local','guide.demo@tourisme-ivoire.local','voyageur.demo@tourisme-ivoire.local')
ON DUPLICATE KEY UPDATE role_id=VALUES(role_id);

INSERT INTO passeports(utilisateur_id,numero,date_emission)
SELECT u.id,CONCAT('TI-DEMO-',LPAD(u.id,8,'0')),CURRENT_DATE
FROM utilisateurs u
WHERE u.email IN ('admin.demo@tourisme-ivoire.local','guide.demo@tourisme-ivoire.local','voyageur.demo@tourisme-ivoire.local')
ON DUPLICATE KEY UPDATE numero=VALUES(numero);

INSERT INTO parametres_utilisateurs(utilisateur_id)
SELECT u.id FROM utilisateurs u
WHERE u.email IN ('admin.demo@tourisme-ivoire.local','guide.demo@tourisme-ivoire.local','voyageur.demo@tourisme-ivoire.local')
ON DUPLICATE KEY UPDATE utilisateur_id=VALUES(utilisateur_id);
