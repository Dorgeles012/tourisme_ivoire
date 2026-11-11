USE tourisme_ivoire;
CREATE FULLTEXT INDEX ft_destinations_recherche ON destinations(nom,description);
CREATE INDEX idx_videos_publication_date ON videos(statut,created_at);
CREATE INDEX idx_quiz_publication_theme ON quiz(statut,theme,difficulte);
CREATE INDEX idx_reservations_utilisateur_statut ON reservations(utilisateur_id,statut,created_at);
CREATE INDEX idx_participations_quiz_score ON participations_quiz(quiz_id,score);
CREATE INDEX idx_historique_points_user_date ON historique_points(utilisateur_id,created_at);
