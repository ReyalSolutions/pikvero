<?php
/**
 * PickleHub Database Migration & Seeder Runner
 */
require_once __DIR__ . '/../app/bootstrap.php';

use App\Config\DatabaseConfig;
use App\Config\PermissionConfig;
use App\Core\Database\Connection;

header('Content-Type: text/plain; charset=utf-8');

echo "========================================================\n";
echo "           PICKLEHUB DATABASE MIGRATION & SEEDER        \n";
echo "========================================================\n\n";

try {
    // Connect without selecting DB to create it if needed
    $rawConn = new mysqli(DatabaseConfig::$host, DatabaseConfig::$username, DatabaseConfig::$password, '', DatabaseConfig::$port);
    if ($rawConn->connect_error) {
        die("MySQL Connection failed: " . $rawConn->connect_error . "\n");
    }

    $dbname = DatabaseConfig::$dbname;
    echo "[1/5] Creating database `{$dbname}` if not exists...\n";
    $rawConn->query("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $rawConn->select_db($dbname);

    // Read and run schema.sql
    echo "[2/5] Running schema migration (`database/schema.sql`)...\n";
    $schemaSql = file_get_contents(__DIR__ . '/schema.sql');
    if ($rawConn->multi_query($schemaSql)) {
        do {
            if ($result = $rawConn->store_result()) {
                $result->free();
            }
        } while ($rawConn->more_results() && $rawConn->next_result());
    }
    echo " -> Schema loaded successfully.\n";

    $db = Connection::getInstance();

    // Seed Roles (Active + Future Architecture Roles)
    echo "[3/5] Seeding System Roles & Permissions...\n";
    $rolesMap = [
        'super_admin' => 'Super Administrator',
        'platform_admin' => 'Platform Administrator',
        'finance_admin' => 'Finance Administrator',
        'support_staff' => 'Support Administrator',
        'court_owner' => 'Court Owner',
        'facility_manager' => 'Facility Manager',
        'receptionist' => 'Receptionist',
        'court_staff' => 'Court Staff',
        'customer' => 'Customer / Player',
        
        // Future Architecture Roles
        'coach' => 'Pickleball Coach',
        'tournament_organizer' => 'Tournament Organizer',
        'league_organizer' => 'League Organizer',
        'equipment_vendor' => 'Equipment Vendor',
        'event_organizer' => 'Event Organizer'
    ];

    $roleIds = [];
    foreach ($rolesMap as $name => $displayName) {
        $existing = $db->selectOne("SELECT id FROM roles WHERE name = ?", [$name]);
        if (!$existing) {
            $db->execute("INSERT INTO roles (name, display_name) VALUES (?, ?)", [$name, $displayName]);
            $roleIds[$name] = $db->getLastInsertId();
        } else {
            $roleIds[$name] = (int)$existing['id'];
        }
    }

    // Seed Permissions
    $permIds = [];
    foreach (PermissionConfig::$permissions as $permName => $desc) {
        $existing = $db->selectOne("SELECT id FROM permissions WHERE name = ?", [$permName]);
        if (!$existing) {
            $db->execute("INSERT INTO permissions (name, description) VALUES (?, ?)", [$permName, $desc]);
            $permIds[$permName] = $db->getLastInsertId();
        } else {
            $permIds[$permName] = (int)$existing['id'];
        }
    }

    // Attach Role Permissions
    foreach (PermissionConfig::$rolePermissions as $roleName => $perms) {
        $rId = $roleIds[$roleName] ?? null;
        if (!$rId) continue;
        foreach ($perms as $pName) {
            $pId = $permIds[$pName] ?? null;
            if ($pId) {
                $db->execute("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)", [$rId, $pId], 'ii');
            }
        }
    }

    // Seed Default Amenities
    echo "[4/5] Seeding Default Amenities...\n";
    $amenities = [
        ['Lighting & Night Play', 'bi-brightness-high-fill'],
        ['Pro Shop & Gear Rental', 'bi-bag-check-fill'],
        ['Air-Conditioned Lounge', 'bi-snow'],
        ['Shower & Locker Rooms', 'bi-water'],
        ['Free High-Speed Wi-Fi', 'bi-wifi'],
        ['On-Site Parking', 'bi-p-square-fill'],
        ['Refreshment Bar', 'bi-cup-hot-fill']
    ];
    $amenityIds = [];
    foreach ($amenities as $item) {
        $existing = $db->selectOne("SELECT id FROM amenities WHERE name = ?", [$item[0]]);
        if (!$existing) {
            $db->execute("INSERT INTO amenities (name, icon) VALUES (?, ?)", [$item[0], $item[1]]);
            $amenityIds[] = $db->getLastInsertId();
        } else {
            $amenityIds[] = (int)$existing['id'];
        }
    }

    // Seed Subscription Plans
    echo "[4.5/5] Seeding Platform Subscription Plans...\n";
    try {
        $db->execute("ALTER TABLE subscription_plans ADD COLUMN slug VARCHAR(50) DEFAULT NULL UNIQUE AFTER name");
    } catch (\Throwable $e) {}
    try {
        $db->execute("ALTER TABLE subscription_plans ADD COLUMN description TEXT DEFAULT NULL AFTER max_staff");
    } catch (\Throwable $e) {}

    $plans = [
        ['Starter Plan', 'starter', 499.00, 1, 5, 3, 'Ideal for boutique single-venue court owners'],
        ['Pro Plan', 'pro', 999.00, 5, 50, 15, 'Designed for growing multi-court clubs & venues'],
        ['Enterprise Plan', 'enterprise', 2499.00, 99, 999, 99, 'Unlimited court networks with dedicated priority support']
    ];

    $planInclusions = [
        'starter' => [
            '1 Facility Location',
            'Up to 5 Courts',
            'Basic Booking Engine & Calendar',
            'Real-Time Double Booking Protection',
            'Standard Email Support'
        ],
        'pro' => [
            'Up to 5 Facility Locations',
            'Up to 50 Courts',
            'Advanced Analytics & Revenue Reports',
            'Staff Management & RBAC Controls',
            'Custom Promotions & Membership Packages',
            'Priority Support'
        ],
        'enterprise' => [
            'Unlimited Facility Locations',
            'Unlimited Courts & Networks',
            'Multi-tenant Organization Control',
            'Custom Domain & Brand Whitelabeling',
            'Dedicated Account Manager',
            '24/7 Phone & Email Support'
        ]
    ];

    foreach ($plans as $p) {
        $existing = $db->selectOne("SELECT id FROM subscription_plans WHERE name = ? OR slug = ?", [$p[0], $p[1]]);
        if (!$existing) {
            $db->execute("INSERT INTO subscription_plans (name, slug, monthly_price, max_facilities, max_courts, max_staff, description) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6]],
                'ssdiiis'
            );
            $planId = $db->getLastInsertId();
        } else {
            $db->execute("UPDATE subscription_plans SET monthly_price = ?, max_facilities = ?, max_courts = ?, max_staff = ?, description = ? WHERE id = ?",
                [$p[2], $p[3], $p[4], $p[5], $p[6], (int)$existing['id']],
                'diiisi'
            );
            $planId = (int)$existing['id'];
        }

        // Seed Inclusions
        if (isset($planInclusions[$p[1]])) {
            foreach ($planInclusions[$p[1]] as $inc) {
                $hasInc = $db->selectOne("SELECT id FROM subscription_plan_features WHERE plan_id = ? AND feature = ?", [$planId, $inc], 'is');
                if (!$hasInc) {
                    $db->execute("INSERT INTO subscription_plan_features (plan_id, feature) VALUES (?, ?)", [$planId, $inc], 'is');
                }
            }
        }
    }

    // Seed Users & Organizations
    echo "[5/5] Seeding Users, Organizations, Facilities, Courts & Bookings...\n";
    try {
        $db->execute("ALTER TABLE users ADD COLUMN username VARCHAR(50) DEFAULT NULL UNIQUE AFTER id");
    } catch (\Throwable $e) { /* column exists */ }

    try {
        $db->execute("ALTER TABLE organizations ADD COLUMN tax_id VARCHAR(100) DEFAULT NULL UNIQUE AFTER name");
    } catch (\Throwable $e) { /* column exists */ }

    $defaultPassHash = password_hash('Password123!', PASSWORD_DEFAULT);

    // 1. Super Admin
    $admin = $db->selectOne("SELECT id FROM users WHERE email = 'admin@picklehub.com' OR username = 'admin'");
    if (!$admin) {
        $db->execute("INSERT INTO users (username, first_name, last_name, email, password_hash, phone, role_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')",
            ['admin', 'Alex', 'Administrator', 'admin@picklehub.com', $defaultPassHash, '+63 917 000 0001', $roleIds['super_admin']],
            'ssssssi'
        );
    } else {
        $db->execute("UPDATE users SET username = 'admin' WHERE email = 'admin@picklehub.com'");
    }

    // 2. Court Owner 1
    $owner1 = $db->selectOne("SELECT id FROM users WHERE email = 'owner@smashzone.com' OR username = 'owner'");
    if (!$owner1) {
        $db->execute("INSERT INTO users (username, first_name, last_name, email, password_hash, phone, role_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')",
            ['owner', 'Marcus', 'Vance', 'owner@smashzone.com', $defaultPassHash, '+63 918 111 2222', $roleIds['court_owner']],
            'ssssssi'
        );
        $owner1Id = $db->getLastInsertId();
    } else {
        $db->execute("UPDATE users SET username = 'owner' WHERE email = 'owner@smashzone.com'");
        $owner1Id = (int)$owner1['id'];
    }

    // Organization 1
    $org1 = $db->selectOne("SELECT id FROM organizations WHERE owner_id = ?", [$owner1Id]);
    if (!$org1) {
        $db->execute("INSERT INTO organizations (name, owner_id, status) VALUES (?, ?, 'active')", ['SmashZone Pickleball Club', $owner1Id], 'si');
        $org1Id = $db->getLastInsertId();
    } else {
        $org1Id = (int)$org1['id'];
    }

    // Facility 1
    $fac1 = $db->selectOne("SELECT id FROM facilities WHERE organization_id = ?", [$org1Id]);
    if (!$fac1) {
        $db->execute("INSERT INTO facilities (organization_id, name, description, address, city, province, phone, email, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')",
            [$org1Id, 'SmashZone Pickleball Center Tagbilaran', 'Premier indoor & outdoor pickleball venue with tournament-grade cushioned acrylic courts, night lighting, and full amenities.', 'CPG North Avenue, Cogon', 'Tagbilaran City', 'Bohol', '+63 38 411 9988', 'info@smashzone.com'],
            'isssssss'
        );
        $fac1Id = $db->getLastInsertId();

        // Attach amenities to Facility 1
        foreach ($amenityIds as $aId) {
            $db->execute("INSERT IGNORE INTO facility_amenities (facility_id, amenity_id) VALUES (?, ?)", [$fac1Id, $aId], 'ii');
        }
    } else {
        $fac1Id = (int)$fac1['id'];
    }

    // Courts for Facility 1
    $courtsData = [
        ['Court 1 - Pro Championship', 1, 'indoor', 'cushioned_acrylic', 450.00],
        ['Court 2 - Sunset Outdoor', 2, 'outdoor', 'cushioned_acrylic', 350.00],
        ['Court 3 - Covered Air Flex', 3, 'covered', 'polyurethane', 400.00]
    ];
    $courtIds = [];
    foreach ($courtsData as $c) {
        $existingCourt = $db->selectOne("SELECT id FROM courts WHERE facility_id = ? AND name = ?", [$fac1Id, $c[0]]);
        if (!$existingCourt) {
            $db->execute("INSERT INTO courts (facility_id, name, court_number, court_type, surface_type, base_price_per_hour, status) VALUES (?, ?, ?, ?, ?, ?, 'active')",
                [$fac1Id, $c[0], $c[1], $c[2], $c[3], $c[4]],
                'isissd'
            );
            $cId = $db->getLastInsertId();
        } else {
            $cId = (int)$existingCourt['id'];
        }
        $courtIds[] = $cId;

        // Seed Operating Hours (Sun-Sat: 06:00 to 22:00)
        for ($day = 0; $day <= 6; $day++) {
            $existingOp = $db->selectOne("SELECT id FROM court_operating_hours WHERE court_id = ? AND day_of_week = ?", [$cId, $day]);
            if (!$existingOp) {
                $db->execute("INSERT INTO court_operating_hours (court_id, day_of_week, open_time, close_time) VALUES (?, ?, '06:00:00', '22:00:00')", [$cId, $day], 'ii');
            }
        }
    }

    // 3. Court Owner 2 (Alvin Golosino)
    $owner2 = $db->selectOne("SELECT id FROM users WHERE email = 'alvin100golosino@gmail.com' OR username = 'alvingolosino'");
    if (!$owner2) {
        $db->execute("INSERT INTO users (username, first_name, last_name, email, password_hash, phone, role_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')",
            ['alvingolosino', 'Alvin', 'Golosino', 'alvin100golosino@gmail.com', $defaultPassHash, '+63 919 888 7777', $roleIds['court_owner']],
            'ssssssi'
        );
        $owner2Id = $db->getLastInsertId();
    } else {
        $owner2Id = (int)$owner2['id'];
    }

    // Organization 2 for Alvin Golosino
    $org2 = $db->selectOne("SELECT id FROM organizations WHERE owner_id = ?", [$owner2Id]);
    if (!$org2) {
        $db->execute("INSERT INTO organizations (name, owner_id, status) VALUES (?, ?, 'active')", ["Alvin Golosino's Court Club", $owner2Id], 'si');
        $org2Id = $db->getLastInsertId();
    } else {
        $org2Id = (int)$org2['id'];
    }

    // Seed Active Starter Plan Subscription for Alvin Golosino
    $starterPlan = $db->selectOne("SELECT id, monthly_price FROM subscription_plans WHERE slug = 'starter' OR name LIKE '%Starter%' ORDER BY id ASC LIMIT 1");
    if ($starterPlan) {
        $subPlanId = (int)$starterPlan['id'];
        $subPrice  = (float)$starterPlan['monthly_price'];
        $periodEnd = date('Y-m-d', strtotime('+1 month'));

        $sub2 = $db->selectOne("SELECT id FROM subscriptions WHERE organization_id = ?", [$org2Id]);
        if (!$sub2) {
            $db->execute("INSERT INTO subscriptions (organization_id, plan_id, status, current_period_end) VALUES (?, ?, 'active', ?)",
                [$org2Id, $subPlanId, $periodEnd],
                'iis'
            );
            $sub2Id = $db->getLastInsertId();
        } else {
            $sub2Id = (int)$sub2['id'];
            $db->execute("UPDATE subscriptions SET plan_id = ?, status = 'active', current_period_end = ? WHERE id = ?",
                [$subPlanId, $periodEnd, $sub2Id],
                'isi'
            );
        }

        // Seed Subscription Payment Receipt
        $hasPay = $db->selectOne("SELECT id FROM subscription_payments WHERE subscription_id = ?", [$sub2Id]);
        if (!$hasPay) {
            $db->execute("INSERT INTO subscription_payments (subscription_id, amount, payment_method, payment_status) VALUES (?, ?, 'Card/PayMongo', 'paid')",
                [$sub2Id, $subPrice],
                'id'
            );
        }
    }

    // 4. Customer 1 & 2
    $cust1 = $db->selectOne("SELECT id FROM users WHERE email = 'player@gmail.com' OR username = 'player'");
    if (!$cust1) {
        $db->execute("INSERT INTO users (username, first_name, last_name, email, password_hash, phone, role_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')",
            ['player', 'Juan', 'Dela Cruz', 'player@gmail.com', $defaultPassHash, '+63 920 333 4444', $roleIds['customer']],
            'ssssssi'
        );
        $cust1Id = $db->getLastInsertId();
    } else {
        $db->execute("UPDATE users SET username = 'player' WHERE email = 'player@gmail.com'");
        $cust1Id = (int)$cust1['id'];
    }

    $cust2 = $db->selectOne("SELECT id FROM users WHERE email = 'maria@gmail.com' OR username = 'maria'");
    if (!$cust2) {
        $db->execute("INSERT INTO users (username, first_name, last_name, email, password_hash, phone, role_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')",
            ['maria', 'Maria', 'Santos', 'maria@gmail.com', $defaultPassHash, '+63 921 555 6666', $roleIds['customer']],
            'ssssssi'
        );
        $cust2Id = $db->getLastInsertId();
    } else {
        $db->execute("UPDATE users SET username = 'maria' WHERE email = 'maria@gmail.com'");
        $cust2Id = (int)$cust2['id'];
    }

    // Seed Sample Bookings
    $today = date('Y-m-d');
    $sampleBookings = [
        ['PB-20260815-0001', $cust1Id, $courtIds[0], $fac1Id, $org1Id, $today, '08:00:00', '10:00:00', 2.00, 450.00, 900.00, 'paid', 'confirmed'],
        ['PB-20260815-0002', $cust2Id, $courtIds[1], $fac1Id, $org1Id, $today, '16:00:00', '18:00:00', 2.00, 350.00, 700.00, 'paid', 'confirmed'],
        ['PB-20260816-0003', $cust1Id, $courtIds[0], $fac1Id, $org1Id, date('Y-m-d', strtotime('+1 day')), '10:00:00', '12:00:00', 2.00, 450.00, 900.00, 'unpaid', 'pending']
    ];

    foreach ($sampleBookings as $b) {
        $existingB = $db->selectOne("SELECT id FROM bookings WHERE booking_reference = ?", [$b[0]]);
        if (!$existingB) {
            $db->execute("INSERT INTO bookings (booking_reference, customer_id, court_id, facility_id, organization_id, booking_date, start_time, end_time, duration_hours, rate_per_hour, total_amount, payment_status, booking_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$b[0], $b[1], $b[2], $b[3], $b[4], $b[5], $b[6], $b[7], $b[8], $b[9], $b[10], $b[11], $b[12]],
                'siiiisssdddss'
            );
            $bId = $db->getLastInsertId();

            if ($b[11] === 'paid') {
                $db->execute("INSERT INTO payments (booking_id, amount, payment_method, transaction_reference, status) VALUES (?, ?, 'gcash', ?, 'completed')",
                    [$bId, $b[10], 'GCASH-' . rand(100000, 999999)],
                    'ids'
                );
            }
        }
    }

    echo "\n========================================================\n";
    echo "SUCCESS! Database migration and seed data completed.\n";
    echo "Default Accounts Created:\n";
    echo "  - Super Admin:  username: admin         | email: admin@picklehub.com  / Password123!\n";
    echo "  - Court Owner:  username: owner         | email: owner@smashzone.com  / Password123!\n";
    echo "  - Court Owner:  username: alvingolosino | email: alvin100golosino@gmail.com / Password123!\n";
    echo "  - Customer:     username: player        | email: player@gmail.com     / Password123!\n";
    echo "========================================================\n";

} catch (Exception $e) {
    echo "ERROR during setup: " . $e->getMessage() . "\n";
    exit(1);
}
