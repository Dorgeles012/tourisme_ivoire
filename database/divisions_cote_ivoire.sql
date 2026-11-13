-- Tourisme Ivoire : divisions administratives de Côte d’Ivoire
-- Sources : DGAT (2 districts autonomes, 31 régions, 111 départements); SIGADT (données
-- territoriales ministérielles, mises à jour janvier 2025); annuaire statistique du Ministère
-- de l’Intérieur 2024; carte administrative publiée par le CNTIG et sourcée DGDDL.
-- 111 départements = 108 rattachés à une région + 3 rattachés aux districts autonomes:
-- Abidjan au district autonome d’Abidjan; Attiégouakro et Yamoussoukro au district autonome
-- de Yamoussoukro. Les 31 régions ne sont rattachées à aucun district autonome.
-- Les colonnes de code officiel restent NULL: aucun code officiel stable n’est fourni par ces
-- sources de référence. Les identifiants sont générés par MySQL et les slugs restent stables.
-- Script additif et réexécutable. Aucune table ni donnée existante n’est supprimée.

USE tourisme_ivoire;
SET NAMES utf8mb4;
START TRANSACTION;

INSERT INTO districts (nom, slug, type, statut) VALUES
('District autonome d’Abidjan','district-autonome-abidjan','autonome','actif'),
('District autonome de Yamoussoukro','district-autonome-yamoussoukro','autonome','actif')
ON DUPLICATE KEY UPDATE nom = VALUES(nom);

INSERT INTO regions (nom, slug, statut) VALUES
('Agneby-Tiassa','agneby-tiassa','actif'),
('Bafing','bafing','actif'),
('Bagoué','bagoue','actif'),
('Bélier','belier','actif'),
('Béré','bere','actif'),
('Bounkani','bounkani','actif'),
('Cavally','cavally','actif'),
('Folon','folon','actif'),
('Gbêkê','gbeke','actif'),
('Gbôklé','gbokle','actif'),
('Gôh','goh','actif'),
('Gontougo','gontougo','actif'),
('Grands-Ponts','grands-ponts','actif'),
('Guémon','guemon','actif'),
('Haut-Sassandra','haut-sassandra','actif'),
('Iffou','iffou','actif'),
('Indénié-Djuablin','indenie-djuablin','actif'),
('Kabadougou','kabadougou','actif'),
('La Mé','la-me','actif'),
('Lôh-Djiboua','loh-djiboua','actif'),
('Marahoué','marahoue','actif'),
('Hambol','hambol','actif'),
('Moronou','moronou','actif'),
('Nawa','nawa','actif'),
('N’Zi','nzi','actif'),
('Poro','poro','actif'),
('San-Pédro','san-pedro','actif'),
('Sud-Comoé','sud-comoe','actif'),
('Tchologo','tchologo','actif'),
('Tonkpi','tonkpi','actif'),
('Worodougou','worodougou','actif')
ON DUPLICATE KEY UPDATE nom = VALUES(nom);

INSERT INTO departements (region_id, district_id, nom, statut)
SELECT r.id, NULL, v.nom, 'actif'
FROM (
SELECT 'Agneby-Tiassa' AS region_nom, 'Agboville' AS nom
UNION ALL
SELECT 'Agneby-Tiassa' AS region_nom, 'Sikensi' AS nom
UNION ALL
SELECT 'Agneby-Tiassa' AS region_nom, 'Taabo' AS nom
UNION ALL
SELECT 'Agneby-Tiassa' AS region_nom, 'Tiassalé' AS nom
UNION ALL
SELECT 'Bafing' AS region_nom, 'Koro' AS nom
UNION ALL
SELECT 'Bafing' AS region_nom, 'Ouaninou' AS nom
UNION ALL
SELECT 'Bafing' AS region_nom, 'Touba' AS nom
UNION ALL
SELECT 'Bagoué' AS region_nom, 'Boundiali' AS nom
UNION ALL
SELECT 'Bagoué' AS region_nom, 'Kouto' AS nom
UNION ALL
SELECT 'Bagoué' AS region_nom, 'Tengréla' AS nom
UNION ALL
SELECT 'Bélier' AS region_nom, 'Didiévi' AS nom
UNION ALL
SELECT 'Bélier' AS region_nom, 'Djékanou' AS nom
UNION ALL
SELECT 'Bélier' AS region_nom, 'Tiébissou' AS nom
UNION ALL
SELECT 'Bélier' AS region_nom, 'Toumodi' AS nom
UNION ALL
SELECT 'Béré' AS region_nom, 'Dianra' AS nom
UNION ALL
SELECT 'Béré' AS region_nom, 'Kounahiri' AS nom
UNION ALL
SELECT 'Béré' AS region_nom, 'Mankono' AS nom
UNION ALL
SELECT 'Bounkani' AS region_nom, 'Bouna' AS nom
UNION ALL
SELECT 'Bounkani' AS region_nom, 'Doropo' AS nom
UNION ALL
SELECT 'Bounkani' AS region_nom, 'Nassian' AS nom
UNION ALL
SELECT 'Bounkani' AS region_nom, 'Téhini' AS nom
UNION ALL
SELECT 'Cavally' AS region_nom, 'Bloléquin' AS nom
UNION ALL
SELECT 'Cavally' AS region_nom, 'Guiglo' AS nom
UNION ALL
SELECT 'Cavally' AS region_nom, 'Taï' AS nom
UNION ALL
SELECT 'Cavally' AS region_nom, 'Toulépleu' AS nom
UNION ALL
SELECT 'Folon' AS region_nom, 'Kaniasso' AS nom
UNION ALL
SELECT 'Folon' AS region_nom, 'Minignan' AS nom
UNION ALL
SELECT 'Gbêkê' AS region_nom, 'Béoumi' AS nom
UNION ALL
SELECT 'Gbêkê' AS region_nom, 'Botro' AS nom
UNION ALL
SELECT 'Gbêkê' AS region_nom, 'Bouaké' AS nom
UNION ALL
SELECT 'Gbêkê' AS region_nom, 'Sakassou' AS nom
UNION ALL
SELECT 'Gbôklé' AS region_nom, 'Fresco' AS nom
UNION ALL
SELECT 'Gbôklé' AS region_nom, 'Sassandra' AS nom
UNION ALL
SELECT 'Gôh' AS region_nom, 'Gagnoa' AS nom
UNION ALL
SELECT 'Gôh' AS region_nom, 'Oumé' AS nom
UNION ALL
SELECT 'Gontougo' AS region_nom, 'Bondoukou' AS nom
UNION ALL
SELECT 'Gontougo' AS region_nom, 'Koun-Fao' AS nom
UNION ALL
SELECT 'Gontougo' AS region_nom, 'Sandégué' AS nom
UNION ALL
SELECT 'Gontougo' AS region_nom, 'Tanda' AS nom
UNION ALL
SELECT 'Gontougo' AS region_nom, 'Transua' AS nom
UNION ALL
SELECT 'Grands-Ponts' AS region_nom, 'Dabou' AS nom
UNION ALL
SELECT 'Grands-Ponts' AS region_nom, 'Grand-Lahou' AS nom
UNION ALL
SELECT 'Grands-Ponts' AS region_nom, 'Jacqueville' AS nom
UNION ALL
SELECT 'Guémon' AS region_nom, 'Bangolo' AS nom
UNION ALL
SELECT 'Guémon' AS region_nom, 'Duékoué' AS nom
UNION ALL
SELECT 'Guémon' AS region_nom, 'Facobly' AS nom
UNION ALL
SELECT 'Guémon' AS region_nom, 'Kouibly' AS nom
UNION ALL
SELECT 'Haut-Sassandra' AS region_nom, 'Daloa' AS nom
UNION ALL
SELECT 'Haut-Sassandra' AS region_nom, 'Issia' AS nom
UNION ALL
SELECT 'Haut-Sassandra' AS region_nom, 'Vavoua' AS nom
UNION ALL
SELECT 'Haut-Sassandra' AS region_nom, 'Zoukougbeu' AS nom
UNION ALL
SELECT 'Iffou' AS region_nom, 'Daoukro' AS nom
UNION ALL
SELECT 'Iffou' AS region_nom, 'M’Bahiakro' AS nom
UNION ALL
SELECT 'Iffou' AS region_nom, 'Ouellé' AS nom
UNION ALL
SELECT 'Iffou' AS region_nom, 'Prikro' AS nom
UNION ALL
SELECT 'Indénié-Djuablin' AS region_nom, 'Abengourou' AS nom
UNION ALL
SELECT 'Indénié-Djuablin' AS region_nom, 'Agnibilékrou' AS nom
UNION ALL
SELECT 'Indénié-Djuablin' AS region_nom, 'Bettié' AS nom
UNION ALL
SELECT 'Kabadougou' AS region_nom, 'Gbéléban' AS nom
UNION ALL
SELECT 'Kabadougou' AS region_nom, 'Madinani' AS nom
UNION ALL
SELECT 'Kabadougou' AS region_nom, 'Odienné' AS nom
UNION ALL
SELECT 'Kabadougou' AS region_nom, 'Samatiguila' AS nom
UNION ALL
SELECT 'Kabadougou' AS region_nom, 'Séguélon' AS nom
UNION ALL
SELECT 'La Mé' AS region_nom, 'Adzopé' AS nom
UNION ALL
SELECT 'La Mé' AS region_nom, 'Akoupé' AS nom
UNION ALL
SELECT 'La Mé' AS region_nom, 'Alépé' AS nom
UNION ALL
SELECT 'La Mé' AS region_nom, 'Yakassé-Attobrou' AS nom
UNION ALL
SELECT 'Lôh-Djiboua' AS region_nom, 'Divo' AS nom
UNION ALL
SELECT 'Lôh-Djiboua' AS region_nom, 'Guitry' AS nom
UNION ALL
SELECT 'Lôh-Djiboua' AS region_nom, 'Lakota' AS nom
UNION ALL
SELECT 'Marahoué' AS region_nom, 'Bonon' AS nom
UNION ALL
SELECT 'Marahoué' AS region_nom, 'Bouaflé' AS nom
UNION ALL
SELECT 'Marahoué' AS region_nom, 'Gohitafla' AS nom
UNION ALL
SELECT 'Marahoué' AS region_nom, 'Sinfra' AS nom
UNION ALL
SELECT 'Marahoué' AS region_nom, 'Zuénoula' AS nom
UNION ALL
SELECT 'Hambol' AS region_nom, 'Dabakala' AS nom
UNION ALL
SELECT 'Hambol' AS region_nom, 'Katiola' AS nom
UNION ALL
SELECT 'Hambol' AS region_nom, 'Niakaramadougou' AS nom
UNION ALL
SELECT 'Moronou' AS region_nom, 'Arrah' AS nom
UNION ALL
SELECT 'Moronou' AS region_nom, 'Bongouanou' AS nom
UNION ALL
SELECT 'Moronou' AS region_nom, 'M’Batto' AS nom
UNION ALL
SELECT 'Nawa' AS region_nom, 'Buyo' AS nom
UNION ALL
SELECT 'Nawa' AS region_nom, 'Guéyo' AS nom
UNION ALL
SELECT 'Nawa' AS region_nom, 'Méagui' AS nom
UNION ALL
SELECT 'Nawa' AS region_nom, 'Soubré' AS nom
UNION ALL
SELECT 'N’Zi' AS region_nom, 'Bocanda' AS nom
UNION ALL
SELECT 'N’Zi' AS region_nom, 'Dimbokro' AS nom
UNION ALL
SELECT 'N’Zi' AS region_nom, 'Kouassi-Kouassikro' AS nom
UNION ALL
SELECT 'Poro' AS region_nom, 'Dikodougou' AS nom
UNION ALL
SELECT 'Poro' AS region_nom, 'Korhogo' AS nom
UNION ALL
SELECT 'Poro' AS region_nom, 'M’Bengué' AS nom
UNION ALL
SELECT 'Poro' AS region_nom, 'Sinématiali' AS nom
UNION ALL
SELECT 'San-Pédro' AS region_nom, 'San-Pédro' AS nom
UNION ALL
SELECT 'San-Pédro' AS region_nom, 'Tabou' AS nom
UNION ALL
SELECT 'Sud-Comoé' AS region_nom, 'Aboisso' AS nom
UNION ALL
SELECT 'Sud-Comoé' AS region_nom, 'Adiaké' AS nom
UNION ALL
SELECT 'Sud-Comoé' AS region_nom, 'Grand-Bassam' AS nom
UNION ALL
SELECT 'Sud-Comoé' AS region_nom, 'Tiapoum' AS nom
UNION ALL
SELECT 'Tchologo' AS region_nom, 'Ferkessédougou' AS nom
UNION ALL
SELECT 'Tchologo' AS region_nom, 'Kong' AS nom
UNION ALL
SELECT 'Tchologo' AS region_nom, 'Ouangolodougou' AS nom
UNION ALL
SELECT 'Tonkpi' AS region_nom, 'Biankouma' AS nom
UNION ALL
SELECT 'Tonkpi' AS region_nom, 'Danané' AS nom
UNION ALL
SELECT 'Tonkpi' AS region_nom, 'Man' AS nom
UNION ALL
SELECT 'Tonkpi' AS region_nom, 'Sipilou' AS nom
UNION ALL
SELECT 'Tonkpi' AS region_nom, 'Zouan-Hounien' AS nom
UNION ALL
SELECT 'Worodougou' AS region_nom, 'Kani' AS nom
UNION ALL
SELECT 'Worodougou' AS region_nom, 'Séguéla' AS nom
) AS v
JOIN regions AS r ON r.nom = v.region_nom
ON DUPLICATE KEY UPDATE nom = VALUES(nom);

INSERT INTO departements (region_id, district_id, nom, statut)
SELECT NULL, d.id, v.nom, 'actif'
FROM (
SELECT 'District autonome d’Abidjan' AS district_nom, 'Abidjan' AS nom
UNION ALL
SELECT 'District autonome de Yamoussoukro' AS district_nom, 'Attiégouakro' AS nom
UNION ALL
SELECT 'District autonome de Yamoussoukro' AS district_nom, 'Yamoussoukro' AS nom
) AS v
JOIN districts AS d ON d.nom = v.district_nom
ON DUPLICATE KEY UPDATE nom = VALUES(nom);

COMMIT;

-- Contrôles après import: attendus = 2 districts autonomes, 31 régions, 111 départements.
SELECT 'districts_autonomes' AS controle, COUNT(*) AS total FROM districts WHERE type = 'autonome';
SELECT 'regions' AS controle, COUNT(*) AS total FROM regions;
SELECT 'departements' AS controle, COUNT(*) AS total FROM departements;
SELECT 'doublons_region_nom' AS controle, COUNT(*) AS total FROM (SELECT region_id, nom FROM departements WHERE region_id IS NOT NULL GROUP BY region_id, nom HAVING COUNT(*) > 1) AS doublons;
SELECT 'doublons_district_nom' AS controle, COUNT(*) AS total FROM (SELECT district_id, nom FROM departements WHERE district_id IS NOT NULL GROUP BY district_id, nom HAVING COUNT(*) > 1) AS doublons;
SELECT 'rattachements_invalides' AS controle, COUNT(*) AS total FROM departements d WHERE (d.region_id IS NULL) = (d.district_id IS NULL) OR (d.region_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM regions r WHERE r.id = d.region_id)) OR (d.district_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM districts x WHERE x.id = d.district_id));
