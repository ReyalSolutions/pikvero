<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../app/bootstrap.php';

$db = \App\Core\Database\Connection::getInstance();
$columns = array_column($db->select('SHOW COLUMNS FROM product_sales'), 'Field');
$definitions = [
    'transaction_type' => "ENUM('sale','rental') NULL DEFAULT NULL",
    'rental_status' => "ENUM('rented','returned') NULL DEFAULT NULL",
    'rental_stock_deducted' => 'TINYINT(1) NOT NULL DEFAULT 0',
    'returned_at' => 'DATETIME NULL DEFAULT NULL',
    'returned_by_user_id' => 'INT NULL DEFAULT NULL',
];
foreach ($definitions as $name => $definition) {
    if (!in_array($name, $columns, true)) $db->execute("ALTER TABLE product_sales ADD COLUMN {$name} {$definition}");
}
$db->execute("UPDATE product_sales s JOIN products p ON p.id = s.product_id
    SET s.transaction_type = p.type, s.rental_status = CASE WHEN p.type = 'rental' THEN 'rented' ELSE NULL END
    WHERE s.transaction_type IS NULL");
echo "Rental return fields ready. Existing inventory was preserved.\n";
