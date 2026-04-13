<?php
$_server_db = '/var/data/selektiert/selektiert.db';
$_local_db  = __DIR__ . '/../../.db/selektiert.db';
define('DB_PATH', is_dir(dirname($_server_db)) ? $_server_db : $_local_db);
define('UPLOADS_DIR', __DIR__ . '/../uploads/');
define('UPLOADS_URL', '/admin/uploads/');

function get_db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA journal_mode=WAL');
    }
    return $pdo;
}
