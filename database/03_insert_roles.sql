USE tourisme_ivoire;
INSERT INTO langues(code,nom) VALUES ('fr','Français'),('en','English') ON DUPLICATE KEY UPDATE nom=VALUES(nom);
INSERT INTO roles(code,nom,description) VALUES ('voyageur','Voyageur','Compte utilisateur standard'),('guide','Guide touristique','Guide touristique'),('editeur','Éditeur','Gestion des contenus'),('administrateur','Administrateur','Administration complète') ON DUPLICATE KEY UPDATE nom=VALUES(nom);
