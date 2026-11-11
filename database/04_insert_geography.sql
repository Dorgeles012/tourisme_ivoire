USE tourisme_ivoire;
-- Référentiel du Ministère de l’Intérieur. Les districts autonomes sont distincts des régions.
INSERT INTO districts(nom,slug) VALUES ('District autonome d’Abidjan','district-autonome-abidjan'),('District autonome de Yamoussoukro','district-autonome-yamoussoukro') ON DUPLICATE KEY UPDATE nom=VALUES(nom);
INSERT INTO regions(nom,slug) VALUES
('Agnéby-Tiassa','agneby-tiassa'),('Bafing','bafing'),('Bagoué','bagoue'),('Bélier','belier'),('Béré','bere'),('Bounkani','bounkani'),('Cavally','cavally'),('Folon','folon'),('Gbêkê','gbeke'),('Gbôklè','gbokle'),('Gôh','goh'),('Gontougo','gontougo'),('Grands-Ponts','grands-ponts'),('Guémon','guemon'),('Hambol','hambol'),('Haut-Sassandra','haut-sassandra'),('Iffou','iffou'),('Indénié-Djuablin','indenie-djuablin'),('Kabadougou','kabadougou'),('Lôh-Djiboua','loh-djiboua'),('Marahoué','marahoue'),('Mé','me'),('Moronou','moronou'),('Nawa','nawa'),('N’Zi','nzi'),('Poro','poro'),('San-Pédro','san-pedro'),('Sud-Comoé','sud-comoe'),('Tchologo','tchologo'),('Tonkpi','tonkpi'),('Worodougou','worodougou')
ON DUPLICATE KEY UPDATE nom=VALUES(nom);

-- Only add the departments and localities needed by the initial tourism catalogue.
-- Do not infer a district/region overlap: populate districts_regions only from an
-- authoritative source when that administrative relationship is verified.
INSERT INTO departements(region_id,district_id,nom)
SELECT r.id,NULL,x.nom
FROM (
 SELECT 'Agnéby-Tiassa' region,'Agboville' nom UNION ALL
 SELECT 'Bafing','Touba' UNION ALL
 SELECT 'Bagoué','Boundiali' UNION ALL
 SELECT 'Bélier','Toumodi' UNION ALL
 SELECT 'Béré','Mankono' UNION ALL
 SELECT 'Bounkani','Bouna' UNION ALL
 SELECT 'Cavally','Guiglo' UNION ALL
 SELECT 'Folon','Minignan' UNION ALL
 SELECT 'Gbêkê','Bouaké' UNION ALL
 SELECT 'Gbôklè','Sassandra' UNION ALL
 SELECT 'Gôh','Gagnoa' UNION ALL
 SELECT 'Gontougo','Bondoukou' UNION ALL
 SELECT 'Grands-Ponts','Dabou' UNION ALL
 SELECT 'Guémon','Duékoué' UNION ALL
 SELECT 'Hambol','Katiola' UNION ALL
 SELECT 'Haut-Sassandra','Daloa' UNION ALL
 SELECT 'Iffou','Daoukro' UNION ALL
 SELECT 'Indénié-Djuablin','Abengourou' UNION ALL
 SELECT 'Kabadougou','Odienné' UNION ALL
 SELECT 'Lôh-Djiboua','Divo' UNION ALL
 SELECT 'Marahoué','Bouaflé' UNION ALL
 SELECT 'Mé','Adzopé' UNION ALL
 SELECT 'Moronou','Bongouanou' UNION ALL
 SELECT 'Nawa','Soubré' UNION ALL
 SELECT 'N’Zi','Dimbokro' UNION ALL
 SELECT 'Poro','Korhogo' UNION ALL
 SELECT 'San-Pédro','San-Pédro' UNION ALL
 SELECT 'Sud-Comoé','Aboisso' UNION ALL
 SELECT 'Tchologo','Ferkessédougou' UNION ALL
 SELECT 'Tonkpi','Man' UNION ALL
 SELECT 'Worodougou','Séguéla'
) x
JOIN regions r ON r.nom=x.region
WHERE NOT EXISTS (
 SELECT 1 FROM departements d WHERE d.region_id=r.id AND d.nom=x.nom
);

INSERT INTO departements(region_id,district_id,nom)
SELECT NULL,di.id,x.nom
FROM (
 SELECT 'District autonome d’Abidjan' district,'Abidjan' nom UNION ALL
 SELECT 'District autonome de Yamoussoukro','Yamoussoukro'
) x
JOIN districts di ON di.nom=x.district
WHERE NOT EXISTS (
 SELECT 1 FROM departements d WHERE d.district_id=di.id AND d.nom=x.nom
);

INSERT INTO departements(region_id,district_id,nom)
SELECT r.id,NULL,x.nom
FROM (
 SELECT 'Sud-Comoé' region,'Adiaké' nom UNION ALL
 SELECT 'Sud-Comoé','Grand-Bassam' UNION ALL
 SELECT 'Gbôklè','Fresco' UNION ALL
 SELECT 'Cavally','Taï'
) x
JOIN regions r ON r.nom=x.region
WHERE NOT EXISTS (
 SELECT 1 FROM departements d WHERE d.region_id=r.id AND d.nom=x.nom
);

INSERT INTO localites(departement_id,nom,slug)
SELECT d.id,x.nom,x.slug
FROM (
 SELECT 'Agboville' departement,'Agboville' nom,'agboville' slug UNION ALL
 SELECT 'Touba','Touba','touba' UNION ALL
 SELECT 'Boundiali','Boundiali','boundiali' UNION ALL
 SELECT 'Toumodi','Toumodi','toumodi' UNION ALL
 SELECT 'Mankono','Mankono','mankono' UNION ALL
 SELECT 'Bouna','Bouna','bouna' UNION ALL
 SELECT 'Guiglo','Guiglo','guiglo' UNION ALL
 SELECT 'Minignan','Minignan','minignan' UNION ALL
 SELECT 'Bouaké','Bouaké','bouake' UNION ALL
 SELECT 'Sassandra','Sassandra','sassandra' UNION ALL
 SELECT 'Gagnoa','Gagnoa','gagnoa' UNION ALL
 SELECT 'Bondoukou','Bondoukou','bondoukou' UNION ALL
 SELECT 'Dabou','Dabou','dabou' UNION ALL
 SELECT 'Duékoué','Duékoué','duekoue' UNION ALL
 SELECT 'Katiola','Katiola','katiola' UNION ALL
 SELECT 'Daloa','Daloa','daloa' UNION ALL
 SELECT 'Daoukro','Daoukro','daoukro' UNION ALL
 SELECT 'Abengourou','Abengourou','abengourou' UNION ALL
 SELECT 'Odienné','Odienné','odienne' UNION ALL
 SELECT 'Divo','Divo','divo' UNION ALL
 SELECT 'Bouaflé','Bouaflé','bouafle' UNION ALL
 SELECT 'Adzopé','Adzopé','adzope' UNION ALL
 SELECT 'Bongouanou','Bongouanou','bongouanou' UNION ALL
 SELECT 'Soubré','Soubré','soubre' UNION ALL
 SELECT 'Dimbokro','Dimbokro','dimbokro' UNION ALL
 SELECT 'Korhogo','Korhogo','korhogo' UNION ALL
 SELECT 'San-Pédro','San-Pédro','san-pedro' UNION ALL
 SELECT 'Aboisso','Aboisso','aboisso' UNION ALL
 SELECT 'Ferkessédougou','Ferkessédougou','ferkessedougou' UNION ALL
 SELECT 'Man','Man','man' UNION ALL
 SELECT 'Séguéla','Séguéla','seguela' UNION ALL
 SELECT 'Abidjan','Abidjan','abidjan' UNION ALL
 SELECT 'Yamoussoukro','Yamoussoukro','yamoussoukro' UNION ALL
 SELECT 'Adiaké','Assinie-Mafia','assinie-mafia' UNION ALL
 SELECT 'Sassandra','Grand-Béréby','grand-bereby' UNION ALL
 SELECT 'Dabou','Grand-Lahou','grand-lahou' UNION ALL
 SELECT 'Dabou','Jacqueville','jacqueville' UNION ALL
 SELECT 'Grand-Bassam','Grand-Bassam','grand-bassam' UNION ALL
 SELECT 'Abidjan','Cocody','cocody' UNION ALL
 SELECT 'Bouna','Kong','kong' UNION ALL
 SELECT 'Taï','Taï','tai' UNION ALL
 SELECT 'Fresco','Fresco','fresco' UNION ALL
 SELECT 'Adiaké','Adiaké','adiake'
) x
JOIN departements d ON d.nom=x.departement
WHERE NOT EXISTS (SELECT 1 FROM localites l WHERE l.slug=x.slug);
