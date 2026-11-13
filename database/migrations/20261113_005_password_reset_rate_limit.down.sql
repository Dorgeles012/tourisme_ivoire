-- Reverses the index supporting the per-account reset limit.
DROP INDEX idx_password_reset_user_created ON historique_mot_de_passe;
DELETE FROM schema_migrations WHERE migration='20261113_005_password_reset_rate_limit.php';
