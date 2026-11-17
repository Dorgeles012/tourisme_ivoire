<?php
declare(strict_types=1);

require_once APP_ROOT . '/models/Database.php';

// Keep the existing controller API while the connection lifecycle lives in
// the model layer.
function db(): PDO {
    return Database::connection();
}
