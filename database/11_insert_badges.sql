USE tourisme_ivoire;
INSERT INTO badges(code,nom,description,condition_type,condition_valeur,points) VALUES
('premier_voyage','Premier voyage','Enregistrez votre première visite.','visites',1,10),
('explorateur','Explorateur','Visitez plusieurs destinations.','visites',5,25),
('aventurier','Aventurier','Visitez dix destinations.','visites',10,50),
('passionne_culture','Passionné de culture','Découvrez des contenus culturels.','contenus_culture',5,20),
('expert_geographie','Expert en géographie','Réussissez des quiz de géographie.','quiz_geographie',5,30),
('maitre_quiz','Maître des quiz','Complétez dix quiz.','quiz_termines',10,50),
('photographe','Photographe','Partagez vos découvertes.','videos_publiees',3,25),
('ambassadeur','Ambassadeur du tourisme','Partagez plusieurs publications.','partages',10,40),
('patrimoine','Découvreur du patrimoine','Découvrez plusieurs sites patrimoniaux.','visites_patrimoine',5,30),
('voyageur_confirme','Voyageur confirmé','Visitez vingt destinations.','visites',20,100),
('grand_bassam','Mémoire de Grand-Bassam','Découvrez le patrimoine de Grand-Bassam.','destination_slug',1,15),
('nature_ivoirienne','Nature ivoirienne','Visitez un site naturel.','visites_nature',3,25),
('culture_gourmande','Culture gourmande','Explorez les contenus de gastronomie.','contenus_gastronomie',3,15),
('quiz_parfait','Sans faute','Obtenez un score parfait à un quiz.','quiz_score_parfait',1,20),
('passeport_actif','Passeport actif','Complétez votre profil voyageur.','profil_complet',1,10)
ON DUPLICATE KEY UPDATE nom=VALUES(nom),description=VALUES(description),condition_type=VALUES(condition_type),condition_valeur=VALUES(condition_valeur),points=VALUES(points);
