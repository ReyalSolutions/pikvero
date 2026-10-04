<?php
require_once __DIR__ . '/../app/bootstrap.php';
use App\Core\Database\Connection;

$db = Connection::getInstance();
echo "Updating Database for Court Owner & Admin: alvin100golosino@gmail.com...\n";

// 1. Find user alvin100golosino@gmail.com
$alvin = $db->selectOne("SELECT id, email, role_id FROM users WHERE email = 'alvin100golosino@gmail.com' LIMIT 1");

if ($alvin) {
    $alvinId = (int)$alvin['id'];
    echo "Found user alvin100golosino@gmail.com (User ID #{$alvinId}).\n";

    // Update organizations so alvin100golosino@gmail.com owns Organization 1 and Organization 2
    $db->execute("UPDATE organizations SET owner_id = ? WHERE id IN (1, 2)", [$alvinId], 'i');
    echo "Assigned Organization 1 & 2 to owner ID #{$alvinId} (alvin100golosino@gmail.com).\n";

    // Ensure notification_preferences exists for alvin100golosino@gmail.com with email_notifications = 1
    $db->execute("
        INSERT INTO notification_preferences (user_id, email_notifications, push_notifications)
        VALUES (?, 1, 1)
        ON DUPLICATE KEY UPDATE email_notifications = 1, push_notifications = 1
    ", [$alvinId], 'i');
    echo "Ensured notification_preferences enabled for User ID #{$alvinId}.\n";

    // Also update super admin user ID 1 email to include alvin100golosino@gmail.com or add alvin as admin
    $db->execute("UPDATE users SET role_id = 1 WHERE id = ?", [$alvinId], 'i');
    echo "Updated User ID #{$alvinId} role to Super Admin & Court Owner.\n";
} else {
    echo "User alvin100golosino@gmail.com not found. Creating user...\n";
    $db->execute("
        INSERT INTO users (role_id, username, email, password, first_name, last_name, phone, status, email_verified_at, created_at)
        VALUES (1, 'alvin100', 'alvin100golosino@gmail.com', '$2y$10$abcdefghijklmnopqrstuu', 'Alvin', 'Golosino', '09170000000', 'active', NOW(), NOW())
    ");
    $alvinId = $db->getLastInsertId();
    $db->execute("UPDATE organizations SET owner_id = ? WHERE id IN (1, 2)", [$alvinId], 'i');
}

echo "Database update complete successfully!\n";
