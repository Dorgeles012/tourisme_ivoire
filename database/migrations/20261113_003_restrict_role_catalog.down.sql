-- Reverses only the role-catalog restriction.
ALTER TABLE roles DROP CHECK chk_roles_code;
DELETE FROM schema_migrations WHERE migration='20261113_003_restrict_role_catalog.php';
