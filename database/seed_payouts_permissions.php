<?php
require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Database\Connection;
use App\Config\PermissionConfig;

$db = Connection::getInstance();

echo "Seeding payouts permissions into database...\n";

// 1. Seed Permissions in permissions table
$permIds = [];
$allPerms = $db->select("SELECT id, name FROM permissions");
foreach ($allPerms as $p) {
    $permIds[$p['name']] = (int)$p['id'];
}

$payoutPerms = [
    'payouts.view'    => 'View GCash payout requests and metrics',
    'payouts.request' => 'Submit GCash payout requests for court bookings',
    'payouts.manage'  => 'Approve, reject, or complete GCash payout requests'
];

foreach ($payoutPerms as $permName => $desc) {
    if (!isset($permIds[$permName])) {
        $db->execute("INSERT INTO permissions (name, description, created_at) VALUES (?, ?, NOW())", [$permName, $desc], 'ss');
        $newId = $db->getLastInsertId();
        $permIds[$permName] = $newId;
        echo "  [+] Inserted permission: {$permName} (ID: {$newId})\n";
    } else {
        echo "  [=] Permission already exists: {$permName} (ID: {$permIds[$permName]})\n";
    }
}

// 2. Fetch roles
$roles = $db->select("SELECT id, name FROM roles");
$roleIds = [];
foreach ($roles as $r) {
    $roleIds[$r['name']] = (int)$r['id'];
}

// 3. Attach Role Permissions according to PermissionConfig::$rolePermissions
foreach (PermissionConfig::$rolePermissions as $roleName => $perms) {
    $rId = $roleIds[$roleName] ?? null;
    if (!$rId) continue;

    foreach ($perms as $pName) {
        if (!str_starts_with($pName, 'payouts.')) continue;

        $pId = $permIds[$pName] ?? null;
        if ($pId) {
            $db->execute("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)", [$rId, $pId], 'ii');
            echo "  [+] Attached '{$pName}' to role '{$roleName}'\n";
        }
    }
}

echo "Done seeding payouts permissions!\n";
