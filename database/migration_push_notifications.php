<?php
require_once __DIR__ . '/../app/bootstrap.php';
use App\Core\Database\Connection;

$db = Connection::getInstance();
echo "Running Migration: Push Notifications & Account Settings...\n";

// 1. Ensure notification_preferences table exists with push_notifications column
$db->execute("
    CREATE TABLE IF NOT EXISTS notification_preferences (
        user_id INT PRIMARY KEY,
        email_notifications TINYINT(1) DEFAULT 1,
        sms_notifications TINYINT(1) DEFAULT 1,
        push_notifications TINYINT(1) DEFAULT 1,
        fcm_token VARCHAR(255) NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Check if push_notifications column exists if table existed previously
try {
    $row = $db->selectOne("SHOW COLUMNS FROM notification_preferences LIKE 'push_notifications'");
    if (!$row) {
        $db->execute("ALTER TABLE notification_preferences ADD COLUMN push_notifications TINYINT(1) DEFAULT 1 AFTER sms_notifications");
        echo "Added push_notifications column to notification_preferences.\n";
    }
} catch (\Throwable $e) {}

try {
    $row = $db->selectOne("SHOW COLUMNS FROM notification_preferences LIKE 'fcm_token'");
    if (!$row) {
        $db->execute("ALTER TABLE notification_preferences ADD COLUMN fcm_token VARCHAR(255) NULL AFTER push_notifications");
        echo "Added fcm_token column to notification_preferences.\n";
    }
} catch (\Throwable $e) {}

// 2. Ensure notifications table exists
$db->execute("
    CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(150) NOT NULL,
        message TEXT NOT NULL,
        type VARCHAR(50) DEFAULT 'push',
        is_read TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id),
        INDEX (is_read),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

try {
    $row = $db->selectOne("SHOW COLUMNS FROM notifications LIKE 'type'");
    if (!$row) {
        $db->execute("ALTER TABLE notifications ADD COLUMN type VARCHAR(50) DEFAULT 'push' AFTER message");
    }
} catch (\Throwable $e) {}

echo "Migration completed successfully!\n";
