<?php
require_once __DIR__ . '/../app/bootstrap.php';
use App\Core\Database\Connection;
use App\Config\PermissionConfig;

$db = Connection::getInstance();
echo "Seeding Schedule Calendar permissions into MySQL...\n";

foreach (PermissionConfig::$permissions as $permName => $permDesc) {
    if (str_starts_with($permName, 'calendar.')) {
        $db->execute("INSERT IGNORE INTO permissions (name, description) VALUES (?, ?)", [$permName, $permDesc], 'ss');
    }
}

foreach (PermissionConfig::$rolePermissions as $roleName => $perms) {
    $roleRow = $db->selectOne("SELECT id FROM roles WHERE name = ?", [$roleName], 's');
    if (!$roleRow) continue;
    $roleId = (int)$roleRow['id'];

    foreach ($perms as $permName) {
        if (!str_starts_with($permName, 'calendar.')) continue;
        $permRow = $db->selectOne("SELECT id FROM permissions WHERE name = ?", [$permName], 's');
        if (!$permRow) continue;
        $permId = (int)$permRow['id'];

        $db->execute("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)", [$roleId, $permId], 'ii');
    }
}

echo "Calendar permissions seeded successfully!\n";
