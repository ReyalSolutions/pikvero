<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../app/bootstrap.php';
$db = \App\Core\Database\Connection::getInstance();
if (!in_array('theme_mode', array_column($db->select('SHOW COLUMNS FROM organizations'), 'Field'), true)) {
    $db->execute("ALTER TABLE organizations ADD COLUMN theme_mode ENUM('light','dark') NOT NULL DEFAULT 'light'");
}
echo "Organization light/dark theme preference ready.\n";
