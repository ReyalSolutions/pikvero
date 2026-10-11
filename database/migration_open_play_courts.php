<?php
require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Database\Connection;

$db = Connection::getInstance();
if (!$db->selectOne("SHOW COLUMNS FROM open_play_sessions LIKE 'court_id'")) {
    // Existing sessions retain NULL until an administrator assigns their court.
    $db->execute("ALTER TABLE open_play_sessions ADD COLUMN court_id INT NULL AFTER facility_id, ADD INDEX idx_open_play_court_date (court_id, session_date, status)");
}
echo "Open Play court migration completed.\n";
