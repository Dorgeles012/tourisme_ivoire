-- Rollback for 20261113_001_user_role_and_demo_cleanup.php on databases upgraded from the previous schema.
-- Rebuild user-role links from the current role value so post-migration role changes are preserved.
DELETE FROM utilisateurs_roles_pre_role_column;
INSERT INTO utilisateurs_roles_pre_role_column(utilisateur_id,role_id)
SELECT u.id,r.id FROM utilisateurs u JOIN roles r ON r.code=u.`role`;
RENAME TABLE utilisateurs_roles_pre_role_column TO utilisateurs_roles;
ALTER TABLE utilisateurs ADD COLUMN is_demo BOOLEAN NOT NULL DEFAULT FALSE AFTER last_login_at;
UPDATE utilisateurs u JOIN utilisateurs_is_demo_pre_role_column backup ON backup.utilisateur_id=u.id SET u.is_demo=backup.is_demo;
ALTER TABLE utilisateurs DROP CHECK chk_utilisateurs_role;
ALTER TABLE utilisateurs DROP FOREIGN KEY fk_utilisateurs_role;
DROP INDEX idx_utilisateurs_role ON utilisateurs;
ALTER TABLE utilisateurs DROP COLUMN `role`;
DELETE FROM schema_migrations WHERE migration='20261113_001_user_role_and_demo_cleanup.php';
