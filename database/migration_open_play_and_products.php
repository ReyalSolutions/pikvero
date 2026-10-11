<?php
require_once __DIR__ . '/../app/bootstrap.php';
use App\Core\Database\Connection;
use App\Config\PermissionConfig;

$db = Connection::getInstance();
echo "Starting Migration: Open Play & Products Management System...\n";

// 1. Create open_play_sessions table
$db->execute("
    CREATE TABLE IF NOT EXISTS open_play_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        facility_id INT NOT NULL,
        court_id INT NULL,
        title VARCHAR(100) NOT NULL,
        session_date DATE NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        fee_per_player DECIMAL(10,2) NOT NULL DEFAULT 70.00,
        max_players INT NOT NULL DEFAULT 16,
        status ENUM('open', 'full', 'completed', 'cancelled') NOT NULL DEFAULT 'open',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (facility_id),
        INDEX idx_open_play_court_date (court_id, session_date, status),
        INDEX (session_date),
        INDEX (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
echo "Table open_play_sessions created or verified.\n";

// 2. Create open_play_registrations table
$db->execute("
    CREATE TABLE IF NOT EXISTS open_play_registrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        session_id INT NOT NULL,
        user_id INT NULL,
        player_name VARCHAR(100) NOT NULL,
        player_phone VARCHAR(30) NULL,
        payment_status ENUM('paid', 'pending', 'refunded') NOT NULL DEFAULT 'paid',
        amount_paid DECIMAL(10,2) NOT NULL DEFAULT 70.00,
        checkin_status ENUM('registered', 'checked_in', 'no_show') NOT NULL DEFAULT 'registered',
        checked_in_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (session_id),
        INDEX (user_id),
        INDEX (checkin_status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
echo "Table open_play_registrations created or verified.\n";

// 3. Create products table
$db->execute("
    CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        facility_id INT NULL,
        name VARCHAR(100) NOT NULL,
        category VARCHAR(50) NOT NULL DEFAULT 'Equipment',
        type ENUM('sale', 'rental') NOT NULL DEFAULT 'sale',
        price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        stock_quantity INT NOT NULL DEFAULT 0,
        description TEXT NULL,
        image_url VARCHAR(255) NULL,
        status ENUM('active', 'out_of_stock', 'archived') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (facility_id),
        INDEX (category),
        INDEX (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
echo "Table products created or verified.\n";

// 4. Create product_sales table
$db->execute("
    CREATE TABLE IF NOT EXISTS product_sales (
        id INT AUTO_INCREMENT PRIMARY KEY,
        facility_id INT NULL,
        product_id INT NOT NULL,
        user_id INT NULL,
        customer_name VARCHAR(100) NOT NULL DEFAULT 'Walk-in Customer',
        quantity INT NOT NULL DEFAULT 1,
        unit_price DECIMAL(10,2) NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        payment_method VARCHAR(30) NOT NULL DEFAULT 'cash',
        sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (facility_id),
        INDEX (product_id),
        INDEX (sale_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
echo "Table product_sales created or verified.\n";

// 5. Seed sample Open Play Sessions if table is empty
$existingSessions = $db->selectOne("SELECT COUNT(*) AS cnt FROM open_play_sessions");
if (empty($existingSessions['cnt'])) {
    $facilities = $db->select("SELECT id FROM facilities LIMIT 2");
    foreach ($facilities as $fac) {
        $facId = (int)$fac['id'];
        $db->execute("
            INSERT INTO open_play_sessions (facility_id, title, session_date, start_time, end_time, fee_per_player, max_players, status)
            VALUES 
            (?, 'Friday Evening Open Play (70/head)', CURDATE(), '17:00:00', '20:00:00', 70.00, 16, 'open'),
            (?, 'Saturday Morning Drop-In Social', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '07:00:00', '10:00:00', 80.00, 20, 'open')
        ", [$facId, $facId], 'ii');
    }
    echo "Sample open play sessions seeded.\n";
}

// 6. Seed sample Products if table is empty
$existingProducts = $db->selectOne("SELECT COUNT(*) AS cnt FROM products");
if (empty($existingProducts['cnt'])) {
    $db->execute("
        INSERT INTO products (name, category, type, price, stock_quantity, description, status)
        VALUES 
        ('Pikvero Carbon Fiber Paddle', 'Paddle', 'sale', 1450.00, 15, 'Lightweight 16mm raw carbon fiber pickleball paddle.', 'active'),
        ('Pro Tour Pickleballs (3-Pack)', 'Ball', 'sale', 250.00, 40, 'USA Pickleball approved 40-hole outdoor pickleballs.', 'active'),
        ('Comfort Cushion Replacement Grip', 'Grip', 'sale', 120.00, 50, 'Non-slip sweat absorbent tacky handle grip.', 'active'),
        ('Pickleball Paddle Rental', 'Rental', 'rental', 50.00, 10, 'Hourly rental paddle for drop-in players.', 'active'),
        ('Pikvero Courtside Duffel Bag', 'Bag', 'sale', 1890.00, 8, 'Heavy-duty multi-pocket pickleball gear bag.', 'active')
    ");
    echo "Sample product catalog seeded.\n";
}

echo "Migration completed successfully!\n";
