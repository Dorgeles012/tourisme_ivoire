USE tourisme_ivoire;
SET NAMES utf8mb4;

-- These 50 catalogue records are review-ready examples, not verified visitor
-- guidance. They remain drafts until an editor confirms descriptions and sources.
INSERT INTO destinations(localite_id,categorie_id,nom,slug,description,histoire,latitude,longitude,adresse,conseils_visite,informations_pratiques,image_principale,statut_publication,is_demo)
SELECT l.id,c.id,x.nom,x.slug,x.description,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'brouillon',TRUE
FROM (
 SELECT 'Basilique Notre-Dame de la Paix' nom,'basilique-notre-dame-paix' slug,'Yamoussoukro' localite,'monuments' categorie,'Site monumental de Yamoussoukro. Les horaires, conditions de visite et tarifs doivent être confirmés auprès du gestionnaire avant publication.' description UNION ALL
 SELECT 'Fondation Félix Houphouët-Boigny','fondation-felix-houphouet-boigny','Yamoussoukro','monuments','Centre culturel et de conférences de Yamoussoukro. Les conditions d’accès sont à vérifier auprès de la Fondation.' UNION ALL
 SELECT 'Yamoussoukro, capitale politique','yamoussoukro-capitale-politique','Yamoussoukro','sites-culturels','Découverte urbaine de Yamoussoukro et de son patrimoine. Les lieux et informations de visite doivent être précisés par un éditeur.' UNION ALL
 SELECT 'Quartier historique de Grand-Bassam','quartier-historique-grand-bassam','Grand-Bassam','sites-historiques','Ensemble urbain historique de Grand-Bassam, inscrit au patrimoine mondial de l’UNESCO. Vérifier les accès et lieux ouverts avant visite.' UNION ALL
 SELECT 'Musée national du costume','musee-national-costume-grand-bassam','Grand-Bassam','musees','Musée consacré aux costumes et aux cultures de Côte d’Ivoire. Vérifier les horaires et les conditions d’accès auprès du musée.' UNION ALL
 SELECT 'Plage de Grand-Bassam','plage-grand-bassam','Grand-Bassam','plages','Littoral de Grand-Bassam. Respecter les consignes locales de sécurité et vérifier les conditions de baignade.' UNION ALL
 SELECT 'Plages d’Assinie-Mafia','plages-assinie-mafia','Assinie-Mafia','plages','Destination balnéaire du Sud-Comoé. Les conditions de baignade et les services disponibles varient; se renseigner localement.' UNION ALL
 SELECT 'Lagune d’Assinie','lagune-assinie','Assinie-Mafia','sites-culturels','Découverte des paysages lagunaires d’Assinie. Les opérateurs et conditions de navigation doivent être vérifiés avant toute activité.' UNION ALL
 SELECT 'Parc national du Banco','parc-national-banco','Abidjan','parcs-nationaux','Aire protégée située dans l’agglomération d’Abidjan. Confirmer les entrées, autorisations et circuits auprès de l’OIPR.' UNION ALL
 SELECT 'Cathédrale Saint-Paul du Plateau','cathedrale-saint-paul-abidjan','Abidjan','sites-religieux','Édifice religieux emblématique du Plateau à Abidjan. Respecter les horaires des offices et les règles de visite.' UNION ALL
 SELECT 'Musée des civilisations de Côte d’Ivoire','musee-civilisations-cote-ivoire','Abidjan','musees','Musée national présentant des collections liées aux cultures ivoiriennes. Vérifier le programme et les horaires avant déplacement.' UNION ALL
 SELECT 'Cocody, découverte urbaine','cocody-decouverte-urbaine','Cocody','sites-culturels','Parcours de découverte du quartier de Cocody. Les lieux précis et leur accessibilité doivent être confirmés.' UNION ALL
 SELECT 'Parc national de Taï','parc-national-tai','Taï','parcs-nationaux','Aire protégée de forêt tropicale inscrite au patrimoine mondial de l’UNESCO. Toute visite doit être préparée avec les autorités et guides autorisés.' UNION ALL
 SELECT 'Parc national de la Comoé','parc-national-comoe','Bouna','parcs-nationaux','Aire protégée du nord-est inscrite au patrimoine mondial de l’UNESCO. Vérifier les conditions d’accès et l’ouverture auprès de l’OIPR.' UNION ALL
 SELECT 'Cascades de Man','cascades-man','Man','cascades','Site naturel de la région du Tonkpi. Accès, sécurité et éventuels frais sont à confirmer auprès des autorités locales.' UNION ALL
 SELECT 'Mont Tonkoui','mont-tonkoui','Man','montagnes','Relief de la région du Tonkpi offrant un paysage montagneux. Vérifier l’itinéraire et les conditions locales avant l’ascension.' UNION ALL
 SELECT 'Pont de lianes de Man','pont-lianes-man','Man','sites-culturels','Ouvrage traditionnel associé à la région de Man. Les modalités de visite et les consignes de sécurité sont à confirmer localement.' UNION ALL
 SELECT 'Korhogo et le pays sénoufo','korhogo-pays-senoufo','Korhogo','sites-culturels','Découverte de Korhogo et des patrimoines sénoufo. Respecter les usages locaux et solliciter un guide pour les sites communautaires.' UNION ALL
 SELECT 'Mosquée de Kong','mosquee-kong','Kong','sites-religieux','Mosquée historique de Kong, inscrite au patrimoine mondial de l’UNESCO. Respecter les règles d’accès à un lieu de culte.' UNION ALL
 SELECT 'Musée Péléforo Gbon Coulibaly','musee-peleforo-gbon-coulibaly','Korhogo','musees','Musée de Korhogo consacré à l’histoire et aux cultures locales. Vérifier les horaires et l’ouverture auprès du musée.' UNION ALL
 SELECT 'Littoral de Sassandra','littoral-sassandra','Sassandra','plages','Découverte du littoral de Sassandra. Les conditions de baignade et de sécurité en mer doivent être vérifiées sur place.' UNION ALL
 SELECT 'Plages de San-Pédro','plages-san-pedro','San-Pédro','plages','Littoral de San-Pédro. Se conformer aux consignes locales et vérifier les conditions de baignade.' UNION ALL
 SELECT 'Littoral de Grand-Béréby','littoral-grand-bereby','Grand-Béréby','plages','Découverte côtière dans le département de San-Pédro. Accès, services et sécurité sont à confirmer localement.' UNION ALL
 SELECT 'San-Pédro, découverte du front de mer','san-pedro-front-mer','San-Pédro','sites-culturels','Parcours de découverte de San-Pédro et de son environnement côtier. Les lieux précis restent à documenter.' UNION ALL
 SELECT 'Aboisso, découverte du Sud-Comoé','aboisso-decouverte','Aboisso','sites-culturels','Découverte urbaine d’Aboisso et du Sud-Comoé. Les sites et informations pratiques doivent être documentés avant publication.' UNION ALL
 SELECT 'Bondoukou, patrimoine du Gontougo','bondoukou-patrimoine','Bondoukou','sites-culturels','Découverte du patrimoine de Bondoukou. Les lieux de visite et les règles d’accès doivent être confirmés avec les acteurs locaux.' UNION ALL
 SELECT 'Bouaké, découverte urbaine','bouake-decouverte','Bouaké','sites-culturels','Parcours de découverte de Bouaké, au cœur de la région du Gbêkê. Itinéraire et informations pratiques à compléter.' UNION ALL
 SELECT 'Daloa et le Haut-Sassandra','daloa-haut-sassandra','Daloa','sites-culturels','Découverte de Daloa et de son environnement régional. Les informations touristiques sont à compléter et vérifier.' UNION ALL
 SELECT 'Odienné et le Kabadougou','odienne-kabadougou','Odienné','sites-culturels','Découverte d’Odienné et du Kabadougou. Respecter les usages locaux et vérifier les conditions d’accès aux sites.' UNION ALL
 SELECT 'Ferkessédougou et le Tchologo','ferkessedougou-tchologo','Ferkessédougou','sites-culturels','Découverte urbaine de Ferkessédougou et du Tchologo. Les sites et conseils de déplacement restent à documenter.' UNION ALL
 SELECT 'Dabou et les Grands-Ponts','dabou-grands-ponts','Dabou','sites-culturels','Découverte de Dabou et de la région des Grands-Ponts. Les étapes du parcours sont à confirmer localement.' UNION ALL
 SELECT 'Lagune de Grand-Lahou','lagune-grand-lahou','Grand-Lahou','sites-culturels','Paysage lagunaire de Grand-Lahou. Activités nautiques et conditions de navigation à vérifier auprès d’opérateurs autorisés.' UNION ALL
 SELECT 'Parc national d’Azagny','parc-national-azagny','Grand-Lahou','parcs-nationaux','Aire protégée de la région des Grands-Ponts. Vérifier les modalités d’accès auprès de l’OIPR avant tout déplacement.' UNION ALL
 SELECT 'Abengourou et l’Indénié-Djuablin','abengourou-indenie-djuablin','Abengourou','sites-culturels','Découverte d’Abengourou et du patrimoine de l’Indénié-Djuablin. Sites précis et modalités à confirmer avec les acteurs locaux.' UNION ALL
 SELECT 'Touba et le Bafing','touba-bafing','Touba','sites-culturels','Découverte de Touba et du Bafing. Les informations d’accès et les sites à visiter sont à documenter localement.' UNION ALL
 SELECT 'Boundiali et la Bagoué','boundiali-bagoue','Boundiali','sites-culturels','Découverte de Boundiali et de la Bagoué. Respecter les usages locaux et vérifier les conditions de visite.' UNION ALL
 SELECT 'Man et les montagnes du Tonkpi','man-montagnes-tonkpi','Man','montagnes','Découverte des paysages montagneux du Tonkpi. Préparer les déplacements avec des guides et opérateurs locaux.' UNION ALL
 SELECT 'Gagnoa et le Gôh','gagnoa-goh','Gagnoa','sites-culturels','Découverte de Gagnoa et de la région du Gôh. Les contenus historiques et informations de visite doivent être vérifiés.' UNION ALL
 SELECT 'Lac aux Caïmans de Yamoussoukro','lac-caimans-yamoussoukro','Yamoussoukro','sites-culturels','Site urbain de Yamoussoukro. Vérifier les règles d’accès, les horaires et les consignes de sécurité avant la visite.' UNION ALL
 SELECT 'Bouna et le Bounkani','bouna-bounkani','Bouna','sites-culturels','Découverte de Bouna et du Bounkani. Les conditions de déplacement et les sites ouverts sont à vérifier.' UNION ALL
 SELECT 'Guiglo et le Cavally','guiglo-cavally','Guiglo','sites-culturels','Découverte de Guiglo et du Cavally. Les informations de visite sont à compléter avec des sources locales fiables.' UNION ALL
 SELECT 'Kong, ville historique','kong-ville-historique','Kong','sites-historiques','Découverte de la ville historique de Kong et de son patrimoine. Respecter les règles locales et confirmer les visites sur place.' UNION ALL
 SELECT 'Réserve de biosphère de Taï','reserve-biosphere-tai','Taï','reserves-naturelles','Territoire de biodiversité associé au parc national de Taï. Les activités doivent être organisées selon les règles de conservation en vigueur.' UNION ALL
 SELECT 'Îles Ehotilé','iles-ehotile','Adiaké','parcs-nationaux','Aire protégée insulaire du Sud-Comoé. Les circuits nautiques et conditions d’accès doivent être confirmés auprès des autorités compétentes.' UNION ALL
 SELECT 'Plage de Fresco','plage-fresco','Fresco','plages','Littoral de Fresco. Vérifier l’état de la mer, les consignes de baignade et les possibilités d’accès localement.' UNION ALL
 SELECT 'Adzopé et la région du Mé','adzope-region-me','Adzopé','sites-culturels','Découverte d’Adzopé et de la région du Mé. Les étapes touristiques sont à documenter avant publication.' UNION ALL
 SELECT 'Séguéla et le Worodougou','seguela-worodougou','Séguéla','sites-culturels','Découverte de Séguéla et du Worodougou. Les sites et informations pratiques doivent être confirmés auprès des acteurs locaux.' UNION ALL
 SELECT 'Bouaflé et la Marahoué','bouafle-marahoue','Bouaflé','sites-culturels','Découverte de Bouaflé et de la Marahoué. Vérifier les lieux de visite et les conditions d’accès avant déplacement.' UNION ALL
 SELECT 'Dimbokro et le N’Zi','dimbokro-nzi','Dimbokro','sites-culturels','Découverte de Dimbokro et du N’Zi. Les contenus touristiques détaillés restent à documenter avec des sources fiables.' UNION ALL
 SELECT 'Taï, porte d’entrée du parc','tai-parc-national','Taï','parcs-nationaux','Point de départ potentiel pour préparer une visite du parc national de Taï. Confirmer les accès et autorisations auprès des autorités.' UNION ALL
 SELECT 'Grand-Bassam, patrimoine et littoral','grand-bassam-patrimoine-littoral','Grand-Bassam','sites-culturels','Parcours de découverte du patrimoine urbain et du littoral de Grand-Bassam. Respecter les consignes de sécurité et les usages locaux.' UNION ALL
 SELECT 'Assinie-Mafia, village et lagune','assinie-mafia-village-lagune','Assinie-Mafia','villages-traditionnels','Découverte du territoire d’Assinie-Mafia. Organiser toute visite communautaire avec l’accord des habitants et des guides locaux.'
) x
JOIN localites l ON l.slug=CASE x.localite
 WHEN 'Yamoussoukro' THEN 'yamoussoukro'
 WHEN 'Grand-Bassam' THEN 'grand-bassam'
 WHEN 'Assinie-Mafia' THEN 'assinie-mafia'
 WHEN 'Abidjan' THEN 'abidjan'
 WHEN 'Cocody' THEN 'cocody'
 WHEN 'Taï' THEN 'tai'
 WHEN 'Bouna' THEN 'bouna'
 WHEN 'Man' THEN 'man'
 WHEN 'Kong' THEN 'kong'
 WHEN 'Korhogo' THEN 'korhogo'
 WHEN 'Sassandra' THEN 'sassandra'
 WHEN 'San-Pédro' THEN 'san-pedro'
 WHEN 'Grand-Béréby' THEN 'grand-bereby'
 WHEN 'Aboisso' THEN 'aboisso'
 WHEN 'Bondoukou' THEN 'bondoukou'
 WHEN 'Bouaké' THEN 'bouake'
 WHEN 'Daloa' THEN 'daloa'
 WHEN 'Odienné' THEN 'odienne'
 WHEN 'Ferkessédougou' THEN 'ferkessedougou'
 WHEN 'Dabou' THEN 'dabou'
 WHEN 'Grand-Lahou' THEN 'grand-lahou'
 WHEN 'Abengourou' THEN 'abengourou'
 WHEN 'Touba' THEN 'touba'
 WHEN 'Boundiali' THEN 'boundiali'
 WHEN 'Gagnoa' THEN 'gagnoa'
 WHEN 'Guiglo' THEN 'guiglo'
 WHEN 'Adiaké' THEN 'adiake'
 WHEN 'Fresco' THEN 'fresco'
 WHEN 'Adzopé' THEN 'adzope'
 WHEN 'Séguéla' THEN 'seguela'
 WHEN 'Bouaflé' THEN 'bouafle'
 WHEN 'Dimbokro' THEN 'dimbokro'
END
JOIN categories c ON c.slug=x.categorie
ON DUPLICATE KEY UPDATE
 localite_id=VALUES(localite_id),
 categorie_id=VALUES(categorie_id),
 nom=VALUES(nom),
 description=VALUES(description),
 is_demo=VALUES(is_demo);
