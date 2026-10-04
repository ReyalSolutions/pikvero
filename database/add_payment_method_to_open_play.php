<?php
require_once __DIR__ . '/../app/bootstrap.php';
use App\Core\Database\Connection;

$db = Connection::getInstance();

try {
    $db->execute("ALTER TABLE open_play_registrations ADD COLUMN payment_method VARCHAR(50) DEFAULT 'cash' AFTER amount_paid");
    echo "Column payment_method added to open_play_registrations successfully.\n";
} catch (\Throwable $e) {
    echo "Notice: " . $e->getMessage() . "\n";
}
