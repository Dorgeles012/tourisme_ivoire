USE tourisme_ivoire;
SET NAMES utf8mb4;

-- Editorial drafts only. Each record must be sourced and reviewed before publication.
INSERT INTO patrimoines(localite_id,categorie_id,titre,slug,contenu,periode,statut,is_demo)
VALUES
(NULL,NULL,'Langues parlées en Côte d’Ivoire','langues-cote-ivoire','Dossier de démonstration à compléter avec des sources linguistiques et des contributions relues par les communautés concernées.','Contemporain','brouillon',TRUE),
(NULL,NULL,'Danses traditionnelles ivoiriennes','danses-traditionnelles','Dossier de démonstration sur les danses et leurs contextes culturels. Documenter les communautés, les variantes et les règles de représentation avant publication.','À documenter','brouillon',TRUE),
(NULL,NULL,'Instruments de musique','instruments-musique','Dossier de démonstration sur les instruments de musique. Les appellations, usages et crédits doivent être validés avec des sources fiables.','À documenter','brouillon',TRUE),
(NULL,NULL,'Gastronomie ivoirienne','gastronomie-ivoirienne','Dossier de démonstration sur les spécialités culinaires. Vérifier les recettes, les variantes régionales et les crédits des contributeurs.','Contemporain','brouillon',TRUE),
(NULL,NULL,'Fêtes et événements culturels','fetes-evenements-culturels','Calendrier éditorial de démonstration. Dates, lieux et conditions d’accès sont à vérifier auprès des organisateurs.','Annuel','brouillon',TRUE),
(NULL,NULL,'Vêtements et textiles traditionnels','vetements-textiles-traditionnels','Dossier de démonstration sur les vêtements et textiles. Toute attribution culturelle doit être vérifiée et contextualisée.','À documenter','brouillon',TRUE),
(NULL,NULL,'Artisanat et savoir-faire','artisanat-savoir-faire','Dossier de démonstration sur les savoir-faire artisanaux. Ajouter les noms des artisans avec leur accord et documenter les techniques avec des sources.','À documenter','brouillon',TRUE),
(NULL,NULL,'Patrimoines vivants et communautés','patrimoines-vivants-communautes','Dossier de démonstration à co-construire avec les communautés concernées. Ne pas publier de contenu culturel sensible sans autorisation.','Contemporain','brouillon',TRUE),
(NULL,NULL,'Histoire de Grand-Bassam','histoire-grand-bassam','Notice de démonstration sur le quartier historique de Grand-Bassam, inscrit au patrimoine mondial de l’UNESCO. Ajouter des références historiques et des crédits avant publication.','À sourcer','brouillon',TRUE),
(NULL,NULL,'Aires protégées et biodiversité','aires-protegees-biodiversite','Dossier de démonstration sur les espaces protégés. Vérifier les informations auprès de l’OIPR et respecter les consignes de conservation.','Contemporain','brouillon',TRUE),
(NULL,NULL,'Patrimoine architectural de Kong','patrimoine-architectural-kong','Notice de démonstration à documenter avec des sources patrimoniales et des interlocuteurs locaux avant publication.','À sourcer','brouillon',TRUE),
(NULL,NULL,'Traditions orales et transmission','traditions-orales-transmission','Dossier de démonstration à élaborer avec des détenteurs de savoirs, selon leurs conditions de consentement et de diffusion.','Contemporain','brouillon',TRUE)
ON DUPLICATE KEY UPDATE titre=VALUES(titre),contenu=VALUES(contenu),statut=VALUES(statut),is_demo=VALUES(is_demo);
