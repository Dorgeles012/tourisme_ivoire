-- Reverses the one-time Flutter-to-admin handoff storage.
DROP TABLE jetons_handoff_admin;
DELETE FROM schema_migrations WHERE migration='20261113_002_admin_handoff_tokens.php';
