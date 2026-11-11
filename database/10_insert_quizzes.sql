USE tourisme_ivoire;
SET NAMES utf8mb4;

-- A shared, explicitly unreviewed demonstration bank. All quizzes remain drafts
-- until an editor replaces/reviews the questions and confirms each explanation.
INSERT INTO quiz(titre,slug,description,theme,difficulte,statut,is_demo)
VALUES
('Découverte de la Côte d’Ivoire — Série 1','decouverte-ci-serie-1','Brouillon de démonstration; banque transversale à revoir avant publication.','histoire','debutant','brouillon',TRUE),
('Découverte de la Côte d’Ivoire — Série 2','decouverte-ci-serie-2','Brouillon de démonstration; banque transversale à revoir avant publication.','geographie','debutant','brouillon',TRUE),
('Découverte de la Côte d’Ivoire — Série 3','decouverte-ci-serie-3','Brouillon de démonstration; banque transversale à revoir avant publication.','gastronomie','debutant','brouillon',TRUE),
('Découverte de la Côte d’Ivoire — Série 4','decouverte-ci-serie-4','Brouillon de démonstration; banque transversale à revoir avant publication.','traditions','debutant','brouillon',TRUE),
('Découverte de la Côte d’Ivoire — Série 5','decouverte-ci-serie-5','Brouillon de démonstration; banque transversale à revoir avant publication.','musique','debutant','brouillon',TRUE),
('Découverte de la Côte d’Ivoire — Série 6','decouverte-ci-serie-6','Brouillon de démonstration; banque transversale à revoir avant publication.','patrimoine','debutant','brouillon',TRUE),
('Découverte de la Côte d’Ivoire — Série 7','decouverte-ci-serie-7','Brouillon de démonstration; banque transversale à revoir avant publication.','faune_flore','debutant','brouillon',TRUE),
('Découverte de la Côte d’Ivoire — Série 8','decouverte-ci-serie-8','Brouillon de démonstration; banque transversale à revoir avant publication.','tourisme','debutant','brouillon',TRUE),
('Découverte de la Côte d’Ivoire — Série 9','decouverte-ci-serie-9','Brouillon de démonstration; banque transversale à revoir avant publication.','culture','intermediaire','brouillon',TRUE),
('Découverte de la Côte d’Ivoire — Série 10','decouverte-ci-serie-10','Brouillon de démonstration; banque transversale à revoir avant publication.','culture','intermediaire','brouillon',TRUE)
ON DUPLICATE KEY UPDATE titre=VALUES(titre),description=VALUES(description),statut=VALUES(statut),is_demo=VALUES(is_demo);

CREATE TEMPORARY TABLE quiz_seed_questions (
 ordre SMALLINT UNSIGNED PRIMARY KEY,
 enonce TEXT NOT NULL,
 explication TEXT NOT NULL,
 bonne_reponse VARCHAR(220) NOT NULL,
 choix_2 VARCHAR(220) NOT NULL,
 choix_3 VARCHAR(220) NOT NULL,
 choix_4 VARCHAR(220) NOT NULL
);
INSERT INTO quiz_seed_questions VALUES
(1,'Quelle ville est la capitale politique de la Côte d’Ivoire ?','Yamoussoukro est la capitale politique; Abidjan est la capitale économique.','Yamoussoukro','Abidjan','Bouaké','Korhogo'),
(2,'En quelle année la Côte d’Ivoire a-t-elle accédé à l’indépendance ?','La proclamation de l’indépendance date du 7 août 1960.','1960','1945','1950','1975'),
(3,'Quelle est la monnaie utilisée en Côte d’Ivoire ?','La Côte d’Ivoire utilise le franc CFA émis par la BCEAO.','Franc CFA','Cedi','Naira','Dalasi'),
(4,'Quelle est la langue officielle de la Côte d’Ivoire ?','Le français est la langue officielle; de nombreuses langues nationales sont également parlées.','Français','Anglais','Portugais','Espagnol'),
(5,'Quel site ivoirien est inscrit au patrimoine mondial de l’UNESCO ?','Le quartier historique de Grand-Bassam est inscrit sur la Liste du patrimoine mondial.','Quartier historique de Grand-Bassam','Marché de Bouaké','Port de San-Pédro','Gare de Daloa'),
(6,'Dans quelle région se trouve Korhogo ?','Korhogo est le chef-lieu de la région du Poro.','Poro','Tonkpi','Gbêkê','Nawa'),
(7,'Quel parc national ivoirien est inscrit au patrimoine mondial de l’UNESCO ?','Le parc national de Taï est inscrit au patrimoine mondial de l’UNESCO.','Parc national de Taï','Parc du Banco','Parc d’Azagny','Parc du Mont Sangbé'),
(8,'L’attiéké est principalement préparé à partir de quel ingrédient ?','L’attiéké est un mets à base de manioc fermenté.','Manioc','Mil','Riz','Igname'),
(9,'Dans quel pays se trouve la mosquée historique de Kong ?','Kong est une ville du nord de la Côte d’Ivoire.','Côte d’Ivoire','Ghana','Bénin','Burkina Faso'),
(10,'Quelle ville côtière abrite le quartier historique inscrit à l’UNESCO ?','Le quartier historique inscrit au patrimoine mondial se trouve à Grand-Bassam.','Grand-Bassam','Assinie-Mafia','Sassandra','Fresco');

CREATE TEMPORARY TABLE quiz_seed_answers (
 question_ordre SMALLINT UNSIGNED NOT NULL,
 reponse_ordre TINYINT UNSIGNED NOT NULL,
 libelle VARCHAR(220) NOT NULL,
 est_correcte BOOLEAN NOT NULL,
 PRIMARY KEY(question_ordre,reponse_ordre)
);
INSERT INTO quiz_seed_answers VALUES
(1,1,'Yamoussoukro',TRUE),(1,2,'Abidjan',FALSE),(1,3,'Bouaké',FALSE),(1,4,'Korhogo',FALSE),
(2,1,'1960',TRUE),(2,2,'1945',FALSE),(2,3,'1950',FALSE),(2,4,'1975',FALSE),
(3,1,'Franc CFA',TRUE),(3,2,'Cedi',FALSE),(3,3,'Naira',FALSE),(3,4,'Dalasi',FALSE),
(4,1,'Français',TRUE),(4,2,'Anglais',FALSE),(4,3,'Portugais',FALSE),(4,4,'Espagnol',FALSE),
(5,1,'Quartier historique de Grand-Bassam',TRUE),(5,2,'Marché de Bouaké',FALSE),(5,3,'Port de San-Pédro',FALSE),(5,4,'Gare de Daloa',FALSE),
(6,1,'Poro',TRUE),(6,2,'Tonkpi',FALSE),(6,3,'Gbêkê',FALSE),(6,4,'Nawa',FALSE),
(7,1,'Parc national de Taï',TRUE),(7,2,'Parc du Banco',FALSE),(7,3,'Parc d’Azagny',FALSE),(7,4,'Parc du Mont Sangbé',FALSE),
(8,1,'Manioc',TRUE),(8,2,'Mil',FALSE),(8,3,'Riz',FALSE),(8,4,'Igname',FALSE),
(9,1,'Côte d’Ivoire',TRUE),(9,2,'Ghana',FALSE),(9,3,'Bénin',FALSE),(9,4,'Burkina Faso',FALSE),
(10,1,'Grand-Bassam',TRUE),(10,2,'Assinie-Mafia',FALSE),(10,3,'Sassandra',FALSE),(10,4,'Fresco',FALSE);

INSERT INTO questions(quiz_id,enonce,explication,ordre,points,statut)
SELECT q.id,s.enonce,s.explication,s.ordre,1,'actif'
FROM quiz q CROSS JOIN quiz_seed_questions s
WHERE q.slug LIKE 'decouverte-ci-serie-%'
ON DUPLICATE KEY UPDATE enonce=VALUES(enonce),explication=VALUES(explication),statut='actif';

INSERT INTO reponses(question_id,libelle,est_correcte,ordre)
SELECT question.id,a.libelle,a.est_correcte,a.reponse_ordre
FROM questions question
JOIN quiz q ON q.id=question.quiz_id
JOIN quiz_seed_answers a ON a.question_ordre=question.ordre
WHERE q.slug LIKE 'decouverte-ci-serie-%'
ON DUPLICATE KEY UPDATE libelle=VALUES(libelle),est_correcte=VALUES(est_correcte);

DROP TEMPORARY TABLE quiz_seed_questions;
DROP TEMPORARY TABLE quiz_seed_answers;
